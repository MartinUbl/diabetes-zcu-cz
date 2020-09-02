<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette,
    Nette\Application\UI\Form;

final class LoginPresenter extends BasePresenter
{
    public function __construct() {
        parent::__construct("login");
    }
    
    public function actionDefault()
    {
        //
    }
    
    protected function createComponentLoginForm() : Form
    {
        $form = new Form();
        
        $form->addEmail('email', 'E-mail')
             ->setRequired('Zadejte e-mail!');
        
        $form->addPassword('password', 'Heslo')
             ->setRequired('Zadejte heslo!');
        
        $form->addSubmit('submit', 'Přihlásit se');
        
        $form->onSuccess[] = [$this, 'onLoginFormSubmit'];
        
        return $form;
    }
    
    public function onLoginFormSubmit(Form $form)
    {
        try
        {
            $this->getUser()->login($form->values->email, $form->values->password);
            
            $this->redirect("Dashboard:");
        } catch (Nette\Security\AuthenticationException $ex) {
            $this->template->loginFormError = $ex->getMessage();
        }
    }
}
