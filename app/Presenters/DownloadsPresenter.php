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
        $resp = new \Nette\Application\Responses\FileResponse(__DIR__."/../../downloads/smartcgms.zip", "smartcgms.zip", "application/zip", true);
        
        $this->sendResponse($resp);
    }
    
    public function handleDownloadDatabase()
    {
        $resp = new \Nette\Application\Responses\FileResponse(__DIR__."/../../downloads/asc2018.sqlite", "asc2018.sqlite", "application/x-sqlite3", true);
        
        $this->sendResponse($resp);
    }
    
    public function handleDownloadIcarus()
    {
        $resp = new \Nette\Application\Responses\FileResponse(__DIR__."/../../downloads/IcarusHasDiabetes64.exe", "IcarusHasDiabetes64.exe", "application/exe", true);
        
        $this->sendResponse($resp);
    }
}
