<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

class BasePresenter extends Nette\Application\UI\Presenter
{
    protected $presenterName = "base";
    
    private static $allowedLocales = ['cs', 'en'];
    
    /** @var \Kdyby\Translation\Translator @inject */
    public $translator;
    
    public function __construct(string $presenterName = "") {
        parent::__construct();
        
        $this->presenterName = $presenterName;
    }
    
    public function startup() {
        parent::startup();
        
        $this->template->presenterName = $this->presenterName;

/*
        $session = $this->getSession();
        $prefs = $session->getSection('preferences');
        if ($prefs) {
            $lang = $prefs->get('lang');
            if (in_array($lang, self::$allowedLocales)) {
                $this->translator->setLocale($lang);
            }
        }
*/
        $this->translator->setLocale('en');
        $this->template->localeName = $this->translator->getLocale();
    }
    
    public function handleLogout() {
        if ($this->getUser()->isLoggedIn())
            $this->getUser()->logout();
        
        if ($this->presenterName == 'login')
            $this->redirect("this");
        else
            $this->redirect("Homepage:");
    }
    
    public function handleChangeLang($lang) {
        if (!in_array($lang, self::$allowedLocales)) {
            $this->redirect('this');
            return;
        }
        
        $session = $this->getSession();
        $prefs = $session->getSection('preferences');
        $prefs->set('lang', $lang);
        
        $this->redirect('this');
    }
}
