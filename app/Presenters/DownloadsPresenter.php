<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

final class DownloadsPresenter extends BasePresenter
{
    public function __construct() {
        parent::__construct("downloads");
    }
    
    public function actionDefault()
    {
        //
    }
    
    public function handleDownloadSmartcgms()
    {
        $resp = new \Nette\Application\Responses\FileResponse(__DIR__."/../../downloads/smartcgms-dist.zip", "smartcgms-dist.zip", "application/zip", true);
        
        $this->sendResponse($resp);
    }
    
    public function handleDownloadDatabase()
    {
        $resp = new \Nette\Application\Responses\FileResponse(__DIR__."/../../downloads/acs2018.zip", "acs2018.zip", "application/zip", true);
        
        $this->sendResponse($resp);
    }
    
    public function handleDownloadIcarus()
    {
        $resp = new \Nette\Application\Responses\FileResponse(__DIR__."/../../downloads/icarus.zip", "icarus.zip", "application/zip", true);
        
        $this->sendResponse($resp);
    }
}
