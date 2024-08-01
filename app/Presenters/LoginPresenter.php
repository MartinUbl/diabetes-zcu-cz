<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette,
    Nette\Application\UI\Form;

final class LoginPresenter extends BasePresenter
{
    /** @var \App\Models\UserModel */
    private $users;
    
    /** @var \App\Services\Mailer */
    private $mailer;
    
    private $passResetId = null;
    
    public function __construct(\App\Models\UserModel $userModel, \App\Services\Mailer $mailer) {
        parent::__construct("login");

        $this->users = $userModel;
        $this->mailer = $mailer;
    }
    
    public function actionDefault()
    {
        //
    }
    
    public function actionActivate($id)
    {
        $user = $this->users->getUserByActivationToken($id);
        if (!$user) {
            $this->redirect('Login:activationFail');
        } else {
            $this->users->clearActivationTokenForUser($user->id);
            $this->redirect('Login:activationSuccess');
        }
    }
    
    public function actionPasswordReset($id)
    {
        $this->passResetId = $id;
    }
    
    protected function createComponentLoginForm() : Form
    {
        $form = new Form();
        
        $form->addEmail('email', 'E-mail')
             ->setRequired("Please, enter you e-mail!");
        
        $form->addPassword('password', "Password")
             ->setRequired("Please, enter your password!");
        
        $form->addSubmit('submit', "Sign in");
        
        $form->addProtection();
        
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
            if ($ex->getCode() == Nette\Security\Authenticator::NOT_APPROVED) {
                $this->redirect("Login:activationNeeded");
            } else {
                $this->template->loginFormError = "Invalid username and/or password!";
            }
        }
    }
    
    protected function createComponentForgottenForm() : Form
    {
        $form = new Form();

        // force "POST", so the reCAPTCHA works well
        $form->setMethod(Form::POST);
        
        $form->addEmail('email', "E-mail")
             ->setRequired("Please, enter you e-mail!");
        
        $form->addReCaptcha('recaptcha', '', "Please, prove that you're not a robot");
        
        $form->addSubmit('submit', "Request");
        
        $form->addProtection();
        
        $form->onSuccess[] = [$this, 'onForgottenFormSubmit'];
        
        return $form;
    }
    
    public function onForgottenFormSubmit(Form $form)
    {
        $vals = $form->values;
        
        // NOTE: the flow tries to not give away the registration status of any e-mail address;
        //       it also waits a random time delay to avoid side-channel detection of user registration status
        
        // TODO: check for frequency of requests, but reCaptcha kinda does this on its own in a bit more efficient way when it comes to robotic requests
        
        $usr = $this->users->getUserByEmail($vals->email);
        if ($usr && empty($usr->activation_token)) // user needs to be active in order to reset password
        {
            $data = $this->users->createPasswordResetTokenForUser($usr->id);
            
            $this->mailer->sendMail($vals->email, "diabetes.zcu.cz - zapomenuté heslo", 'forgotten', [
                'link' => $this->link("//Login:passwordReset", ['id' => $data['token']])
            ]);
        }
        else {
            usleep(random_int(200, 400)); // emulate the delay, that would otherwise be caused by accessing the database and sending e-mail
        }
        
        $this->flashMessage("If the e-mail you entered is in our database, we've just sent a password recovery link to it.", 'success');
        $this->redirect("Login:");
    }
    
    protected function createComponentRegisterForm() : Form
    {
        $form = new Form();
        
        // force "POST", so the reCAPTCHA works well
        $form->setMethod(Form::POST);
        
        $form->addText('first_name', "First name");
        $form->addText('last_name', "Last name");
        
        $form->addEmail('email', "E-mail")
             ->addRule(\Nette\Application\UI\Form::EMAIL, "Invalid e-mail format!")
             ->setRequired("E-mail is required!");
        
        $passField = $form->addPassword('password', "Password")
             ->addRule(\Nette\Application\UI\Form::MIN_LENGTH, "You password must be at least 8 characters long!", 8)
             ->setRequired("Please, enter your password!");
        
        $form->addPassword('password_again', "Password again")
             ->addRule(Form::EQUAL, "Passwords must match!", $passField)
             ->setRequired("Please, repeat your password!");
        
        $form->addCheckbox('agree', "I agree with personal data processing")
             ->setRequired("You must agree");
        
        $form->addReCaptcha('recaptcha', '', "Please, prove that you're not a robot");

        $form->addSubmit('submit', "Sign up");
        
        $form->addProtection();
        
        $form->onSuccess[] = [$this, 'onRegisterFormSubmit'];
        
        return $form;
    }
    
    public function onRegisterFormSubmit(Form $form)
    {
        $vals = $form->values;
        
        $usr = $this->users->getUserByEmail($vals->email);
        if ($usr)
        {
            $this->template->registerFormError = "This e-mail is already in use!";
            return;
        }
        
        $data = $this->users->registerUser($vals->email, $vals->password, empty($vals->first_name) ? null : $vals->first_name, empty($vals->last_name) ? null : $vals->last_name);
        
        $this->mailer->sendMail($vals->email, "diabetes.zcu.cz - registrace", 'registration', [
            'link' => $this->link("//Login:activate", ['id' => $data['activation_token']['token']])
        ]);
        
        $this->redirect("Login:activationNeeded");
    }
    
    protected function createComponentResendActivationForm() : Form
    {
        $form = new Form();
        
        // force "POST", so the reCAPTCHA works well
        $form->setMethod(Form::POST);
        
        $form->addEmail('email', "Your e-mail")
             ->addRule(\Nette\Application\UI\Form::EMAIL, "Invalid e-mail")
             ->setRequired("Please, enter your e-mail");
        
        $form->addReCaptcha('recaptcha', '', "Please, prove that you're not a robot");

        $form->addSubmit('submit', "Resend link");
        
        $form->addProtection();
        
        $form->onSuccess[] = [$this, 'onResendActivationFormSubmit'];
        
        return $form;
    }

    public function onResendActivationFormSubmit(Form $form)
    {
        $vals = $form->values;
        
        // NOTE: the flow tries to not give away the registration status of any e-mail address;
        //       it also waits a random time delay to avoid side-channel detection of user registration status
        
        // TODO: check for frequency of activation link resend requests, but reCaptcha kinda does this on its own in a bit more efficient way when it comes to robotic requests
        
        $usr = $this->users->getUserByEmail($vals->email);
        if ($usr && !empty($usr->activation_token))
        {
            $data = $this->users->createActivationTokenForUser($usr->id);
            
            $this->mailer->sendMail($vals->email, "diabetes.zcu.cz - registrace", 'registration', [
                'link' => $this->link("//Login:activate", ['id' => $data['token']])
            ]);
        }
        else {
            usleep(random_int(200, 400)); // emulate the delay, that would otherwise be caused by accessing the database and sending e-mail
        }
        
        $this->flashMessage("If the e-mail is in our database for an inactive account, an activation link has been sent.", 'success');
        $this->redirect("Login:activationNeeded");
    }
    
    protected function createComponentPasswordResetForm() : Form
    {
        $form = new Form();
        
        $passField = $form->addPassword('password', "Password")
             ->addRule(\Nette\Application\UI\Form::MIN_LENGTH, "You password must be at least 8 characters long!", 8)
             ->setRequired("Please, enter your password!");
        
        $form->addPassword('password_again', "Password again")
             ->addRule(Form::EQUAL, "Passwords must match!", $passField)
             ->setRequired("Please, repeat your password!");

        $form->addSubmit('submit', "Set a new password");
        
        $form->addProtection();
        
        $form->onSuccess[] = [$this, 'onPasswordResetFormSubmit'];
        
        return $form;
    }
    
    public function onPasswordResetFormSubmit(Form $form)
    {
        $vals = $form->values;
        
        // NOTE: the flow tries to not give away the validity status of the reset link
        //       it also waits a random time delay to avoid side-channel detection of link validity
        
        $usr = $this->users->getUserByPasswordResetToken($this->passResetId);
        if ($usr)
        {
            $this->users->setUserPassword($usr->id, $vals->password);
        }
        else {
            usleep(random_int(200, 400)); // emulate the delay, that would otherwise be caused by accessing the database and sending e-mail
        }
        
        $this->flashMessage("If the link was valid, we've just set a new password to an account bound to it.", 'success');
        $this->redirect("Login:");
    }
    
}
