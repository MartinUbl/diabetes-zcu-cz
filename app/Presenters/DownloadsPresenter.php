<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette,
    Nette\Application\UI\Form;

final class DownloadsPresenter extends BasePresenter
{
    /** @var \App\Models\DownloadTokensModel */
    private $downloadTokens;
    
    /** @var \App\Services\Mailer */
    private $mailer;
    
    /** @var string */
    private $downloadCategory = 'none';
    
    private const CATEGORY_SMARTCGMS = 'smartcgms';
    private const CATEGORY_DATASET = 'dataset';
    private const UNTOKENED_NAME_ICARUS = 'icarus';
    
    private $allowedCategories = [
        self::CATEGORY_SMARTCGMS,
        self::CATEGORY_DATASET,
    ];
    
    private $emailSubjectsForCategory = [
        self::CATEGORY_SMARTCGMS => 'SmartCGMS download',
        self::CATEGORY_DATASET => 'Sample dataset download',
    ];
    
    private $fileSourceForCategory = [
        self::CATEGORY_SMARTCGMS => 'smartcgms.zip',
        self::CATEGORY_DATASET => 'asc2018.sqlite',
    ];
    
    private $fileDownloadNameForCategory = [
        self::CATEGORY_SMARTCGMS => 'smartcgms.zip',
        self::CATEGORY_DATASET => 'asc2018.sqlite',
    ];
    
    private $fileMimeTypeForCategory = [
        self::CATEGORY_SMARTCGMS => 'application/zip',
        self::CATEGORY_DATASET => 'application/x-sqlite3',
    ];
    
    public static $CategoryTranslationTable = [
        self::CATEGORY_SMARTCGMS => 'SmartCGMS',
        self::CATEGORY_DATASET => 'ASC2018 dataset',
    ];
    
    public static $UntokenedNameTranslationTable = [
        self::UNTOKENED_NAME_ICARUS => 'Icarus has diabetes',
    ];
    
    public function __construct(\App\Models\DownloadTokensModel $downloadTokens, \App\Services\Mailer $mailer) {
        parent::__construct("downloads");
        
        $this->downloadTokens = $downloadTokens;
        $this->mailer = $mailer;
    }
    
    public function actionDefault()
    {
        //
    }
    
    public function actionSmartcgms()
    {
        $this->downloadCategory = self::CATEGORY_SMARTCGMS;
    }
    
    public function actionDataset()
    {
        $this->downloadCategory = self::CATEGORY_DATASET;
    }
    
    public function actionConfirm($id, $category)
    {
        $res = $this->downloadTokens->findTokenForCategory($category, $id);
        if (!$res)
        {
            $this->flashMessage('Invalid download token! Please, perform another request.', 'error');
            $this->redirect('Downloads:');
        }
        
        $this->downloadTokens->markUsed($id, $category);
        
        $resp = new \Nette\Application\Responses\FileResponse(
                __DIR__."/../../downloads/".$this->fileSourceForCategory[$category],
                $this->fileDownloadNameForCategory[$category],
                $this->fileMimeTypeForCategory[$category],
                true
        );

        $this->sendResponse($resp);
    }
    
    public function handleDownloadIcarus()
    {
        $guard = $this->getSession()->getSection('download_guard')->get(self::UNTOKENED_NAME_ICARUS);
        if (!$guard || intval($guard) < time())
        {
            $this->downloadTokens->storeUntokenedDownload(self::UNTOKENED_NAME_ICARUS);
            $this->getSession()->getSection('download_guard')->set(self::UNTOKENED_NAME_ICARUS, time() + 5*60); // record next download in 5 minutes
        }
        
        $resp = new \Nette\Application\Responses\FileResponse(__DIR__."/../../downloads/IcarusHasDiabetes64.exe", "IcarusHasDiabetes64.exe", "application/exe", true);
        
        $this->sendResponse($resp);
    }
    
    public function createComponentRequestDownloadForm()
    {
        $form = new Form();
        
        $form->addText('name', 'Name (yours or your institute)')
             ->setRequired('This field is mandatory')
             ->setDefaultValue($this->getUser()->isLoggedIn() ? $this->getUser()->getIdentity()->first_name.' '.$this->getUser()->getIdentity()->last_name : null);
        
        $form->addText('phone', 'Phone')
             ->setRequired('This field is mandatory');
        
        $form->addText('address', 'Address')
             ->setRequired('This field is mandatory');
        
        $form->addEmail('email', 'E-mail')
             ->setRequired('This field is mandatory')
             ->setDefaultValue($this->getUser()->isLoggedIn() ? $this->getUser()->getIdentity()->email : null);
        
        $form->addHidden('category', $this->downloadCategory);
        
        $form->addProtection();
        
        $form->addSubmit('submit', 'Request a download');
        
        $form->onSuccess[] = [$this, 'onRequestDownloadFormSuccess'];
        
        return $form;
    }
    
    public function onRequestDownloadFormSuccess(Form $form)
    {
        $vals = $form->values;
        
        if (!in_array($vals->category, $this->allowedCategories))
        {
            $this->flashMessage('Invalid request category!', 'error');
            $this->redirect('this');
        }
        
        $token = $this->downloadTokens->createToken(
                $vals->category,
                60,
                $vals->name, $vals->phone, $vals->address, $vals->email,
                $this->getHttpRequest()->getRemoteAddress());
        
       
        $this->mailer->sendMail($vals->email, "diabetes.zcu.cz - ".$this->emailSubjectsForCategory[$vals->category], 'download-'.$vals->category, ['link' => $this->link('//Downloads:confirm', ['id' => $token, 'category' => $vals->category])]);
        
        $this->flashMessage('Your request was submitted!', 'success');
        $this->redirect("Downloads:");
    }
}
