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
    
    /** @var \App\Models\GlobalStatesModel */
    private $globalStates;
    
    /** @var \App\Models\IcarusUploadsModel */
    private $icarus;

    /** @var \stdClass */
    protected $reqBody;

    /** @var string */
    protected $reqBodyRaw;

    public function __construct(\Nette\Security\Passwords $passwords, \App\Models\GlobalStatesModel $globalStates, \App\Models\IcarusUploadsModel $icarus) {
        parent::__construct("api");

        $this->passwords = $passwords;
        $this->globalStates = $globalStates;
        $this->icarus = $icarus;
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
            case 'icarusUploadMeta':
            case 'icarusUploadFile':
            case 'icarusScoreboard':
            case 'icarusVersion':
                // Icarus game-related endpoints;
                // also no valid user required
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
        if (!isset($this->reqBody->email) || !isset($this->reqBody->password))
            $this->setResponseAndDie(400);
        
        $usr = $this->userModel->getUserByEmail($this->reqBody->email);
        if ($usr)
            $this->setResponseAndDie(409);
        
        if (strlen($this->reqBody->password) < 6)
            $this->setResponseAndDie(406);
        
        $usr = $this->userModel->addUser($this->reqBody->email, $this->reqBody->password);
        
        $this->setResponseAndDie(201);
    }
    
    public function actionIcarusUploadMeta()
    {
        $requiredFields = ["score", "player_qualification", "disease_code", "nickname", "difficulty", "date"];
        
        foreach ($requiredFields as $fld)
        {
            if (!isset($this->reqBody->{$fld}))
                $this->setResponseAndDie(400);
        }
        
        $d = \DateTime::createFromFormat("Y-m-d H:i:s", $this->reqBody->date);
        if (!$d || $d->format("Y-m-d H:i:s") !== $this->reqBody->date)
            $this->setResponseAndDie(400, [ "error" => "Field 'date' does not contain datetime formatted using 'Y-m-d H:i:s' formatter string" ]);
        
        $uploadId = $this->icarus->createUpload(
                $this->reqBody->score,
                $this->reqBody->player_qualification,
                $this->reqBody->disease_code,
                $this->reqBody->nickname,
                $this->reqBody->difficulty,
                $this->reqBody->date);
        
        if (!$uploadId || strlen($uploadId) <= 0)
            $this->setResponseAndDie(410);
        
        $this->setResponseAndDie(200, array('id' => $uploadId));
    }
    
    public function actionIcarusUploadFile($id)
    {
        if (!$id || strlen($id) <= 0)
            $this->setResponseAndDie(400);
        
        $f = $this->getHttpRequest()->getFile('log');
        if (!$f)
            $this->setResponseAndDie(400);
        
        if (!\App\Models\IcarusUploadsModel::validateUploadId($id))
            $this->setResponseAndDie(401);
        
        $rec = $this->icarus->getUploadByUploadId($id);
        if (!$rec)
            $this->setResponseAndDie(404);

        $dir = $this->icarus->uploadsPath;
        $dir = str_replace('\\', '/', $dir);
        if ($dir[strlen($dir) - 1] != '/')
            $dir .= '/';

        $dstFile = $dir.$rec->id.'.log';
        
        $f->move($dstFile);
        
        $checksum = strtolower(sha1_file($dstFile));
        
        $this->icarus->invalidateUploadId($id);
        
        $this->setResponseAndDie(200);
    }
    
    public function actionIcarusScoreboard()
    {
        $this->setResponseAndDie(200, [ 'scoreboard' => $this->icarus->getScoreboard() ]);
    }
    
    public function actionIcarusVersion()
    {
        $this->setResponseAndDie(200, [
            'current' => $this->globalStates->getState('icarus_current_version'),
            'minimal' => $this->globalStates->getState('icarus_minimal_version'),
        ]);
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
        
        $dir = $this->uploadsModel->uploadsPath;
        $dir = str_replace('\\', '/', $dir);
        if ($dir[strlen($dir) - 1] != '/')
            $dir .= '/';
        
        $dstFile = $dir.$rec->id.'.log';
        
        $f->move($dstFile);
        
        $checksum = strtolower(sha1_file($dstFile));
        
        $this->uploadsModel->eraseUploadId($rec->id);
        $this->uploadsModel->setUploadChecksum($rec->id, $checksum);
        
        $this->updateUploadStatistics($rec);
        
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
        
        $strApp = $f->getContents();
        if ($strApp != null)
        {
            $oldf = fopen($dstFile, "a");
            if (!$oldf)
                $this->setResponseAndDie(413);
            
            if (!flock($oldf, LOCK_EX))
            {
                fclose(oldf);
                $this->setResponseAndDie(416);
            }
            fwrite($oldf, $strApp);
            fflush($oldf);
            flock($oldf, LOCK_UN);
            
            fclose($oldf);
            
            $checksum = strtolower(sha1_file($dstFile));
            $this->uploadsModel->setUploadChecksum($upload->id, $checksum);
            
            $this->updateUploadStatistics($upload);
            
            $this->setResponseAndDie(200);
        }
        else
            $this->setResponseAndDie(413);
    }
    
    protected function updateUploadStatistics($upload)
    {
        $dir = $this->context->parameters['configuration']['uploads_dir'];
        $dir = str_replace('\\', '/', $dir);
        if ($dir[strlen($dir) - 1] != '/')
            $dir .= '/';
        
        $dstFile = $dir.$upload->id.'.log';
        
        $parsedLevels = \Utils\LogParser::parseLog($dstFile);
        if (!$parsedLevels || count($parsedLevels) == 0)
            return;
        
        $igvals = $parsedLevels['ig'];
        if (!$igvals || count($igvals) == 0)
            return;
        
        $totalAvg = 0;
        $dayAvg = 0;
        $dayAvgCnt = 0;
        $totalTirCnt = 0;
        $dayTirCnt = 0;
        $dayTirTotalCnt = 0;
        
        $lastIg = null;
        
        foreach ($igvals as $ig)
        {
            if (!$lastIg || $lastIg->time < $ig->time)
                $lastIg = $ig;
        }
        $lastDayTime = $lastIg->time - 24*60*60;
        
        foreach ($igvals as $ig)
        {
            $totalAvg += $ig->level;
            
            if ($ig->time > $lastDayTime)
            {
                $dayTirTotalCnt++;
                $dayAvg += $ig->level;
                $dayAvgCnt++;
            }
            
            if ($ig->level >= 3.8 && $ig->level < 10)
            {
                $totalTirCnt++;
                if ($ig->time > $lastDayTime)
                    $dayTirCnt++;
            }
        }
        
        $totalAvg /= count($igvals);
        if ($dayAvgCnt > 0)
            $dayAvg /= $dayAvgCnt;
        $totalTir = $totalTirCnt / count($igvals);
        $dayTir = 0;
        if ($dayTirTotalCnt > 0)
            $dayTir = $dayTirCnt / $dayTirTotalCnt;
        
        $this->uploadsModel->updateUploadStatistics($upload->id, $totalAvg, $dayAvg, $totalTir, $dayTir, $lastIg->time, $lastIg->level);
    }
}
