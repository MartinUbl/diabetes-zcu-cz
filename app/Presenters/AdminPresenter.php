<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette,
    Nette\Application\UI\Form;

final class AdminPresenter extends BaseAdminPresenter
{
    /** @var \App\Models\DownloadTokensModel */
    private $downloadTokens;
    
    /** @var \App\Models\NewsModel */
    private $news;
    
    /** @var string */
    private $stateEdited = null;
    
    /** @var \App\Models\GlobalStatesModel */
    private $globalStates;
    
    /** @var \Nette\Database\Table\ActiveRow */
    private $existingNews = null;

    public function __construct(\App\Models\DownloadTokensModel $downloadTokens, \App\Models\NewsModel $news, \App\Models\GlobalStatesModel $globalStates) {
        parent::__construct("admin");
        
        $this->downloadTokens = $downloadTokens;
        $this->news = $news;
        $this->globalStates = $globalStates;
    }
    
    public function actionDefault()
    {
    }
    
    public function actionNews()
    {
        $this->template->news = $this->news->getNews();
    }
    
    public function actionNewsEdit($id)
    {
        $this->existingNews = $this->news->getById($id);
        if (!$this->existingNews)
        {
            $this->flashMessage('No such news!', 'error');
            $this->redirect('Admin:news');
        }
    }
    
    public function actionGlobalStatesEdit($id)
    {
        $this->stateEdited = $id;
        $this->template->stateKey = $id;
    }
    
    public function actionCounters()
    {
        $this->template->categoryTranslationMap = DownloadsPresenter::$CategoryTranslationTable;
        $this->template->untokenedNameTranslationMap = DownloadsPresenter::$UntokenedNameTranslationTable;
        $this->template->tokenedCounters = $this->downloadTokens->listCategoryCounts();
        $this->template->tokenedStats = $this->downloadTokens->getCategoryStats();
        $this->template->untokenedCounters = $this->downloadTokens->listUntokenedDownloadCounts();
        $this->template->untokenedStats = $this->downloadTokens->getUntokenedDownloadStats();
        $this->template->monthTable = [
            'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'
        ];
    }
    
    public function actionCounterList($category)
    {
        $this->template->category = $category;
        $this->template->categoryTranslationMap = DownloadsPresenter::$CategoryTranslationTable;
        $this->template->tokens = $this->downloadTokens->listDownloadsForCategory($category)->limit(100);
    }
    
    public function actionGlobalStates()
    {
        $this->template->states = $this->globalStates->getAllStates();
    }
    
    public function createComponentNewsForm()
    {
        $form = new Form();
        
        $form->addText('title', 'Title')
             ->addRule(Form::MAX_LENGTH, 'The title field must contain less than 50 characters', 50)
             ->setRequired('This field is mandatory!')
             ->setDefaultValue($this->existingNews ? $this->existingNews->title : null);
        
        $form->addTextArea('short_content', 'Short content')
             ->addRule(Form::MAX_LENGTH, 'The short content field must contain less than 200 characters', 200)
             ->setRequired('This field is mandatory!')
             ->setDefaultValue($this->existingNews ? $this->existingNews->short_content : null);
        
        $form->addTextArea('full_content', 'Full content')
             ->setDefaultValue($this->existingNews ? $this->existingNews->full_content : null);
        
        $form->addCheckbox('post_on_socials', 'Post on social media')
             ->setDisabled($this->existingNews ? $this->existingNews->social_media_posted : false);
        
        $form->addSubmit('submit', $this->existingNews ? 'Edit' : "Create");
        
        $form->onSuccess[] = [$this, 'onNewsFormSuccess'];
        
        return $form;
    }
    
    public function onNewsFormSuccess(Form $form)
    {
        $vals = $form->values;
        
        if ($this->existingNews)
        {
            $this->news->editNews($this->existingNews->id,
                    $vals->title,
                    $vals->short_content,
                    strlen($vals->full_content) > 0 ? $vals->full_content : null
            );
            
            if (!$this->existingNews->social_media_posted && $vals->post_on_socials)
            {
                // disabled for now
            }
            
            $this->flashMessage('News edited successfully', 'success');
        }
        else
        {
            $this->news->addNews(
                    $vals->title,
                    $vals->short_content,
                    $this->getUser()->getId(),
                    null, // use current date
                    strlen($vals->full_content) > 0 ? $vals->full_content : null,
                    $vals->post_on_socials
            );
            
            if ($vals->post_on_socials)
            {
                 // disabled for now
            }
            
            $this->flashMessage('News added successfully', 'success');
        }
        
        $this->redirect('Admin:news');
    }
    
    public function handleNewsDelete($id)
    {
        $this->news->deleteNews($id);
        $this->redirect('Admin:news');
    }
    
    public function createComponentGlobalStatesForm()
    {
        $form = new Form();
        
        $form->addText('key', 'Key')
             ->setDefaultValue($this->stateEdited ? $this->stateEdited : null)
             ->setDisabled($this->stateEdited !== null)
             ->setHtmlAttribute('value', $this->stateEdited ? $this->stateEdited : "")
             ->setRequired('This is a required parameter');
        
        $form->addText('value', 'Value')
             ->setDefaultValue($this->stateEdited ? $this->globalStates->getState($this->stateEdited) : null);
        
        $form->addSubmit('submit', $this->stateEdited ? 'Edit state' : 'Add state');
        
        $form->onSuccess[] = [$this, 'onGlobalStatesFormSuccess'];
        
        return $form;
    }
    
    public function onGlobalStatesFormSuccess(Form $form)
    {
        if ($this->stateEdited) {
            $this->globalStates->updateState($this->stateEdited, $form->values->value);
        } else {
            $this->globalStates->updateState($form->values->key, $form->values->value);
        }
        
        $this->flashMessage('Global state has been stored', 'success');
        $this->redirect("Admin:globalStates");
    }
}
