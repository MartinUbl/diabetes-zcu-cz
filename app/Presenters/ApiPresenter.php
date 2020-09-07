<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

final class ApiPresenter extends BasePresenter
{

    /** @var \App\Models\UserModel @inject */
    public $userModel;
    
    /** @var \App\Models\UploadsModel @inject */
    public $uploadsModel;

    /** @var \Nette\Database\Table\ActiveRow */
    protected $apiUser;

    /** @var \Nette\Security\Passwords */
    private $passwords;

    /** @var \stdClass */
    protected $reqBody;

    /** @var string */
    protected $reqBodyRaw;

    public function __construct(\Nette\Security\Passwords $passwords) {
        parent::__construct("api");

        $this->passwords = $passwords;
    }

    public function startup()
    {
        parent::startup();

        $hdrs = $this->getHttpRequest()->getHeaders();
        if (isset($hdrs['api-token']) && strlen($hdrs['api-token']) > 5)
            $this->apiUser = $this->userModel->getUserByApiToken($hdrs['api-token']);

        $this->reqBodyRaw = $this->getHttpRequest()->getRawBody();
        $this->reqBody = json_decode($this->reqBodyRaw);

        switch ($this->getAction())
        {
            case 'login':
            case 'register':
                // no valid user info required
                break;
            case 'logout':
            case 'uploadMeta':
            case 'uploadFile':
            case 'synchronizeFileMeta':
            case 'synchronizeFile':
                $this->requireValidUser();
                break;
            default:
                $this->setResponseAndDie(404);
                break;
        }
    }

    public function requireValidUser()
    {
        if (!$this->apiUser)
            $this->setResponseAndDie(401);
    }

    public function setResponseAndDie($status, $data = null)
    {
        $httpResponse = $this->getHttpResponse();

        $httpResponse->setCode($status);
        $httpResponse->setContentType('application/json', 'UTF-8');

        if ($data)
            echo json_encode($data);
        exit;
    }

    public function actionLogin()
    {
        if (!isset($this->reqBody->email) || !isset($this->reqBody->password))
            $this->setResponseAndDie(400);

        $usr = $this->userModel->getUserByEmail($this->reqBody->email);
        if (!$usr)
            $this->setResponseAndDie(404);

        if (!$this->passwords->verify($this->reqBody->password, $usr->password))
            $this->setResponseAndDie(403);

        $tokenInfo = $this->userModel->createApiTokenForUser($usr->id);
        
        $respBody = array(
            'token-info' => $tokenInfo,
            'user-info' => array(
                'first-name' => $usr->first_name,
                'last-name' => $usr->last_name,
                'birthdate' => $usr->birthdate,
                'diagnosis' => $usr->diagnosis
            )
        );

        $this->setResponseAndDie(200, $respBody);
    }
    
    public function actionLogout()
    {
        $this->userModel->clearApiTokenForUser($this->apiUser->id);
        $this->setResponseAndDie(200);
    }
    
    public function actionRegister()
    {
        // NYI
    }
    
    public function actionUploadMeta()
    {
        $requiredFields = ["date", "device_info"];
        
        foreach ($requiredFields as $fld)
        {
            if (!isset($this->reqBody->{$fld}))
                $this->setResponseAndDie(400);
        }

        $d = \DateTime::createFromFormat("Y-m-d H:i:s", $this->reqBody->date);
        if (!$d || $d->format("Y-m-d H:i:s") !== $this->reqBody->date)
            $this->setResponseAndDie(400, [ "error" => "Field 'date' does not contain datetime formatted using 'Y-m-d H:i:s' formatter string" ]);

        if (strlen($this->reqBody->device_info) == 0)
            $this->setResponseAndDie(400, [ "error" => "Field 'device_info' is not valid" ]);

        $uploadId = $this->uploadsModel->addUpload($this->reqBody->date, $this->reqBody->device_info, $this->apiUser->id);
        if (!$uploadId || strlen($uploadId) <= 0)
            $this->setResponseAndDie(410);
        
        $this->setResponseAndDie(200, array('id' => $uploadId));
    }
    
    public function actionUploadFile($id = "")
    {
        if (!$id || strlen($id) <= 0)
            $this->setResponseAndDie(400);
        
        $f = $this->getHttpRequest()->getFile('log');
        if (!$f)
            $this->setResponseAndDie(400);
        
        if (!\App\Models\UploadsModel::validateUploadId($id))
            $this->setResponseAndDie(401);
        
        $rec = $this->uploadsModel->getUploadByUploadId($id);
        if (!$rec)
            $this->setResponseAndDie(404);
        
        $dir = $this->context->parameters['configuration']['uploads_dir'];
        $dir = str_replace('\\', '/', $dir);
        if ($dir[strlen($dir) - 1] != '/')
            $dir .= '/';
        
        $dstFile = $dir.$rec->id.'.log';
        
        $f->move($dstFile);
        
        $checksum = strtolower(sha1_file($dstFile));
        
        $this->uploadsModel->eraseUploadId($rec->id);
        $this->uploadsModel->setUploadChecksum($rec->id, $checksum);
        
        $this->setResponseAndDie(200, array(
            'id' => $rec->id
        ));
    }
    
    public function actionSynchronizeFileMeta($id)
    {
        if (!isset($this->reqBody->checksum))
            $this->setResponseAndDie(400);
        
        $upload = $this->uploadsModel->getUploadById($id);
        if (!$upload || $upload->users_id !== $this->apiUser->id)
            $this->setResponseAndDie(406);
        
        if ($upload->checksum !== $this->reqBody->checksum)
            $this->setResponseAndDie(410);
        
        $this->setResponseAndDie(202);
    }
    
    public function actionSynchronizeFile($id)
    {
        $meta = $this->getHttpRequest()->getPost('meta');
        if (!$meta)
            $this->setResponseAndDie(409);
        
        $this->reqBody = json_decode($meta);
        
        if (!isset($this->reqBody->checksum))
            $this->setResponseAndDie(400);
        
        $upload = $this->uploadsModel->getUploadById($id);
        if (!$upload || $upload->users_id !== $this->apiUser->id)
            $this->setResponseAndDie(406);
        
        if ($upload->checksum !== $this->reqBody->checksum)
            $this->setResponseAndDie(410);
        
        $f = $this->getHttpRequest()->getFile('log');
        if (!$f)
            $this->setResponseAndDie(400);
        

        
        $dir = $this->context->parameters['configuration']['uploads_dir'];
        $dir = str_replace('\\', '/', $dir);
        if ($dir[strlen($dir) - 1] != '/')
            $dir .= '/';
        
        $dstFile = $dir.$upload->id.'.log';
        
        //$f->move($dstFile);
        
        
        $tmpFile = $dir.$upload->id.'_filepart.log'; // TMP!!!
        $f->move($tmpFile);
        
        $this->setResponseAndDie(200, array('size' => strlen(file_get_contents($tmpFile))));
    }
}
