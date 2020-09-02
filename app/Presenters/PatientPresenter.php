<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

final class PatientPresenter extends BasePresenter
{
    public function __construct() {
        parent::__construct("patient");
    }
    
    public function actionDefault()
    {
        //
    }
}
