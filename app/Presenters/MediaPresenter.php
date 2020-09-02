<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

final class MediaPresenter extends BasePresenter
{
    public function __construct() {
        parent::__construct("media");
    }
    
    public function actionDefault()
    {
        //
    }
}
