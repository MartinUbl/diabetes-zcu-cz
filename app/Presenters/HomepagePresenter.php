<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

final class HomepagePresenter extends BasePresenter
{
    public function __construct() {
        parent::__construct("homepage");
    }
    
    public function actionDefault()
    {
        //
    }
}
