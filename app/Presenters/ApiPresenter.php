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
            case 'uploadMeta':
            case 'uploadFile':
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

        $token = $this->userModel->createApiTokenForUser($usr->id);

        $this->setResponseAndDie(200, array('api-token' => $token));
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
        
        $f->move($dir.$rec->id.'.log');
        
        $this->uploadsModel->eraseUploadId($rec->id);
        
        $this->setResponseAndDie(200);
    }
}
