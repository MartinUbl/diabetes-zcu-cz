<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

final class IkarosPresenter extends BasePresenter
{
    public function __construct() {
        parent::__construct("ikaros");
    }
    
    public function actionDefault()
    {
        //
    }
}
