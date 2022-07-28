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
        if (!$user)
            $this->redirect('Login:activationFail');
        else
        {
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
        
        $form->addEmail('email', $this->translator->trans('pages.login.form.name'))
             ->setRequired($this->translator->trans('pages.login.form.name_required'));
        
        $form->addPassword('password', $this->translator->trans('pages.login.form.password'))
             ->setRequired($this->translator->trans('pages.login.form.password_required'));
        
        $form->addSubmit('submit', $this->translator->trans('pages.login.form.submit'));
        
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
            if ($ex->getCode() == Nette\Security\Authenticator::NOT_APPROVED)
                $this->redirect("Login:activationNeeded");
            else
                $this->template->loginFormError = $this->translator->trans('pages.login.form.generic_credential_error');
        }
    }
    
    protected function createComponentForgottenForm() : Form
    {
        $form = new Form();

        // force "POST", so the reCAPTCHA works well
        $form->setMethod(Form::POST);
        
        $form->addEmail('email', $this->translator->trans('pages.forgotten.form.name'))
             ->setRequired($this->translator->trans('pages.forgotten.form.name_required'));
        
        $form->addReCaptcha('recaptcha', '', $this->translator->trans('pages.registration.form.recaptcha_required'));
        
        $form->addSubmit('submit', $this->translator->trans('pages.forgotten.form.submit'));
        
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
            
            $this->mailer->sendMail($vals->email, $this->translator->trans('mail.forgotten.subject'), 'forgotten', [
                'link' => $this->link("//Login:passwordReset", ['id' => $data['token']])
            ]);
        }
        else
            usleep(random_int(200, 400)); // emulate the delay, that would otherwise be caused by accessing the database and sending e-mail
        
        $this->flashMessage($this->translator->trans('pages.forgotten.success'), 'success');
        $this->redirect("Login:");
    }
    
    protected function createComponentRegisterForm() : Form
    {
        $form = new Form();
        
        // force "POST", so the reCAPTCHA works well
        $form->setMethod(Form::POST);
        
        $form->addText('first_name', $this->translator->trans('pages.registration.form.first_name'));
        $form->addText('last_name', $this->translator->trans('pages.registration.form.last_name'));
        
        $form->addEmail('email', $this->translator->trans('pages.registration.form.name'))
             ->addRule(\Nette\Application\UI\Form::EMAIL, $this->translator->trans('pages.registration.form.email_format'))
             ->setRequired($this->translator->trans('pages.registration.form.name_required'));
        
        $passField = $form->addPassword('password', $this->translator->trans('pages.registration.form.password'))
             ->addRule(\Nette\Application\UI\Form::MIN_LENGTH, $this->translator->trans('pages.registration.form.pass_min_chars', ['amount' => 8]), 8)
             ->setRequired($this->translator->trans('pages.registration.form.password_required'));
        
        $form->addPassword('password_again', $this->translator->trans('pages.registration.form.password_again'))
             ->addRule(Form::EQUAL, $this->translator->trans('pages.registration.form.passwords_must_match'), $passField)
             ->setRequired($this->translator->trans('pages.registration.form.password_again_required'));
        
        $form->addCheckbox('agree', $this->translator->trans('pages.registration.form.terms'))
             ->setRequired($this->translator->trans('pages.registration.form.agree_required'));
        
        $form->addReCaptcha('recaptcha', '', $this->translator->trans('pages.registration.form.recaptcha_required'));

        $form->addSubmit('submit', $this->translator->trans('pages.registration.form.submit'));
        
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
            $this->template->registerFormError = $this->translator->trans('pages.registration.form.err_already_exists');
            return;
        }
        
        $data = $this->users->registerUser($vals->email, $vals->password, empty($vals->first_name) ? null : $vals->first_name, empty($vals->last_name) ? null : $vals->last_name);
        
        $this->mailer->sendMail($vals->email, $this->translator->trans('mail.registration.subject'), 'registration', [
            'link' => $this->link("//Login:activate", ['id' => $data['activation_token']['token']])
        ]);
        
        $this->redirect("Login:activationNeeded");
    }
    
    protected function createComponentResendActivationForm() : Form
    {
        $form = new Form();
        
        // force "POST", so the reCAPTCHA works well
        $form->setMethod(Form::POST);
        
        $form->addEmail('email', $this->translator->trans('pages.activation.needed.resend_email'))
             ->addRule(\Nette\Application\UI\Form::EMAIL, $this->translator->trans('pages.registration.form.email_format'))
             ->setRequired($this->translator->trans('pages.activation.needed.resend_name_required'));
        
        $form->addReCaptcha('recaptcha', '', $this->translator->trans('pages.registration.form.recaptcha_required'));

        $form->addSubmit('submit', $this->translator->trans('pages.activation.needed.resend_button'));
        
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
            
            $this->mailer->sendMail($vals->email, $this->translator->trans('mail.registration.subject'), 'registration', [
                'link' => $this->link("//Login:activate", ['id' => $data['token']])
            ]);
        }
        else
            usleep(random_int(200, 400)); // emulate the delay, that would otherwise be caused by accessing the database and sending e-mail
        
        $this->flashMessage($this->translator->trans('pages.registration.resend_ok'), 'success');
        $this->redirect("Login:activationNeeded");
    }
    
    protected function createComponentPasswordResetForm() : Form
    {
        $form = new Form();
        
        $passField = $form->addPassword('password', $this->translator->trans('pages.registration.form.password'))
             ->addRule(\Nette\Application\UI\Form::MIN_LENGTH, $this->translator->trans('pages.registration.form.pass_min_chars', ['amount' => 8]), 8)
             ->setRequired($this->translator->trans('pages.registration.form.password_required'));
        
        $form->addPassword('password_again', $this->translator->trans('pages.registration.form.password_again'))
             ->addRule(Form::EQUAL, $this->translator->trans('pages.registration.form.passwords_must_match'), $passField)
             ->setRequired($this->translator->trans('pages.registration.form.password_again_required'));

        $form->addSubmit('submit', $this->translator->trans('pages.passwordreset.form.submit'));
        
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
        else
            usleep(random_int(200, 400)); // emulate the delay, that would otherwise be caused by accessing the database and sending e-mail
        
        $this->flashMessage($this->translator->trans('pages.passwordreset.reset_ok'), 'success');
        $this->redirect("Login:");
    }
    
}
