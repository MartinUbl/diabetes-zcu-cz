<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette,
    Nette\Application\UI\Form;

final class AdminPresenter extends BaseAdminPresenter
{
    /** @var \App\Services\TwitterService */
    private $twitterService;

    /** @var \App\Services\FacebookService */
    private $facebookService;
    
    /** @var \App\Models\NewsModel */
    private $news;
    
    /** @var \Nette\Database\Table\ActiveRow */
    private $existingNews = null;
    
    public function __construct(\App\Services\TwitterService $twitterService, \App\Services\FacebookService $facebookService, \App\Models\NewsModel $news) {
        parent::__construct("admin");
        
        $this->twitterService = $twitterService;
        $this->facebookService = $facebookService;
        $this->news = $news;
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
                $this->twitterService->tweet($vals->short_content);
                // TODO: post on Facebook
                //$this->facebookService->postToPage($vals->short_content);
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
                $this->twitterService->tweet($vals->short_content);
                // TODO: post on Facebook
                //$this->facebookService->postToPage($vals->short_content);
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
}
