<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette,
    Nette\Application\UI\Form;

final class ExperimentsPresenter extends BaseSecurePresenter
{
    /** @var \App\Models\ExperimentModel */
    private $experiments;
    
    /** @var \App\Models\UserModel */
    private $users;
    
    private $currentExperiment;
    
    public function __construct(\App\Models\ExperimentModel $experimentModel, \App\Models\UserModel $userModel) {
        parent::__construct("experiments");
        
        $this->experiments = $experimentModel;
        $this->users = $userModel;
    }
    
    public function actionDefault()
    {
        $this->template->experiments = $this->experiments->getAllExperiments();
    }
    
    public function actionNew()
    {
        //
    }
    
    public function actionDetail($id)
    {
        $this->currentExperiment = $this->experiments->getById($id);
        if (!$this->currentExperiment)
        {
            $this->flashMessage('No such experiment found!', 'error');
            $this->redirect('Experiments:');
        }
        
        $this->template->experiment = $this->currentExperiment;
        $this->template->participants = $this->experiments->getExperimentParticipants($id);
    }
    
    public function createComponentNewExperimentForm()
    {
        $form = new Form();
        
        $form->addText('name', 'Experiment name')
             ->setRequired('This field is required!');
        
        $form->addSubmit('submit');
        
        $form->onSuccess[] = [$this, 'newExperimentFormSuccess'];
        
        return $form;
    }
    
    public function newExperimentFormSuccess(Form $form)
    {
        $name = $form->values->name;
        
        if ($this->experiments->getExperimentByName($name))
        {
            $this->template->formError = "This experiment already exists";
        }
        else
        {
            $this->experiments->addExperiment($name);
            $this->flashMessage('Experiment was successfully created', 'success');
            $this->redirect('Experiments:');
        }
    }
    
    public function handleRemoveParticipant($users_id)
    {
        $this->experiments->removeExperimentParticipant($this->currentExperiment->id, $users_id);
        $this->redrawControl('participantsSnippet');
    }
    
    public function handleAddParticipant($users_id)
    {
        $this->experiments->addExperimentParticipant($this->currentExperiment->id, $users_id);
        $this->redrawControl('participantsSnippet');
    }
    
    public function createComponentSearchUserForm()
    {
        $form = new Form();
        
        $form->addText('email', 'E-mail')
             ->setRequired('Enter valid e-mail or its part');
        
        $form->addSubmit('submit');
        
        $form->onSuccess[] = [$this, 'searchUserFormSuccess'];
        
        return $form;
    }
    
    public function searchUserFormSuccess(Form $form)
    {
        $this->template->foundUsers = $this->users->getUserByEmailPart($form->values->email);
        
        $this->redrawControl('userSearchResults');
    }
}
