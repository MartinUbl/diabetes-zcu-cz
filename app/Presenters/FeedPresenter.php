<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

final class FeedPresenter extends BasePresenter
{
    /** @var \App\Models\NewsModel */
    private $news;

    public function __construct(\App\Models\NewsModel $news) {
        parent::__construct("feed");
        
        $this->news = $news;
    }
    
    public function actionDefault()
    {
        $this->template->news = $this->news->getNews();
        $this->template->pubdate = ($this->template->news && $this->template->news->count() > 1) ? (new \DateTime($this->template->news->fetch()->published_on))->format('r') : "Thu, 11 Aug 2022 12:00:00 +0200";
        $this->getHttpResponse()->setContentType('application/xml');
    }
}
