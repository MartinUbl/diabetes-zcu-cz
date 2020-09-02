<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

final class DiabetesPresenter extends BasePresenter
{
    public function __construct() {
        parent::__construct("diabetes");
    }
    
    public function actionDefault()
    {
        //
    }
}
