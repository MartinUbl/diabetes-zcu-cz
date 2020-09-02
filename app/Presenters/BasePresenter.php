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
    }
    
    public function handleLogout() {
        if ($this->getUser()->isLoggedIn())
            $this->getUser()->logout();
        
        $this->redirect("Homepage:");
    }

}
