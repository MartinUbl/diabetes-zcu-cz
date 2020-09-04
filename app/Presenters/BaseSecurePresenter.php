<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

class BaseSecurePresenter extends BasePresenter
{
    protected $presenterName = "base";
    
    public function __construct(string $presenterName = "base-secure") {
        parent::__construct($presenterName);
    }
    
    public function startup() {
        parent::startup();
        
        if (!$this->getUser()->isLoggedIn())
        {
            $this->flashMessage("You must be logged in to view this page!", 'error');
            $this->redirect("Login:");
        }
    }
}
