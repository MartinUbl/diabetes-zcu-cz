<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

class BaseAdminPresenter extends BasePresenter
{
    protected $presenterName = "base";
    
    public function __construct(string $presenterName = "base-admin") {
        parent::__construct($presenterName);
    }
    
    public function startup() {
        parent::startup();
        
        if (!$this->getUser()->isLoggedIn() || !in_array('admin', $this->getUser()->roles))
        {
            $this->flashMessage("You must be logged in as an administrator to view this page!", 'error');
            $this->redirect("Login:");
        }
    }
}
