<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

final class HomepagePresenter extends BasePresenter
{
    /** @var \App\Models\NewsModel */
    private $news;
    
    public function __construct(\App\Models\NewsModel $news) {
        parent::__construct("homepage");
        
        $this->news = $news;
    }
    
    public function actionDefault()
    {
        $this->template->news = $this->news->getNews(0, 3);
    }
}
