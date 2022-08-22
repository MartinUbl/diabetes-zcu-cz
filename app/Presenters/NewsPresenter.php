<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

final class NewsPresenter extends BasePresenter
{
    /** @var \App\Models\NewsModel */
    private $news;
    
    public function __construct(\App\Models\NewsModel $news) {
        parent::__construct("news");
        
        $this->news = $news;
    }
    
    public function actionDefault()
    {
        $this->template->news = $this->news->getNews();
    }
    
    public function actionRead($id)
    {
        $rec = $this->news->getById($id);
        if (!$rec)
        {
            $this->flashMessage('No such news record!', 'error');
            $this->redirect("Homepage:");
        }
        
        $this->template->news = $rec;
    }
}
