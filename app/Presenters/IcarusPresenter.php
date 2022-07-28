<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

final class IcarusPresenter extends BasePresenter
{
    public function __construct() {
        parent::__construct("icarus");
    }
    
    public function actionDefault()
    {
        //
    }
}
