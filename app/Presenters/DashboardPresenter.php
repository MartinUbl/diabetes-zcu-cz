<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

final class DashboardPresenter extends BaseSecurePresenter
{
    public function __construct() {
        parent::__construct("dashboard");
    }
    
    public function actionDefault()
    {
        //
    }
}
