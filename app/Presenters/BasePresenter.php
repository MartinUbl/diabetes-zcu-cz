<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

class BasePresenter extends Nette\Application\UI\Presenter
{
    protected $presenterName = "base";
    
    public function __construct(string $presenterName = "") {
        parent::__construct();
        
        $this->presenterName = $presenterName;
    }
    
    public function startup() {
        parent::startup();
        
        $this->template->presenterName = $this->presenterName;

        $this->template->localeName = "en";
    }
    
    public function handleLogout() {
        if ($this->getUser()->isLoggedIn()) {
            $this->getUser()->logout();
        }
        
        if ($this->presenterName == 'login') {
            $this->redirect("this");
        }
        else {
            $this->redirect("Homepage:");
        }
    }
}
