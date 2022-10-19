<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

final class Error4xxPresenter extends Nette\Application\UI\Presenter
{
    public function startup(): void
    {
        parent::startup();
        if (!$this->getRequest()->isMethod(Nette\Application\Request::FORWARD))
            $this->error();

		$this->template->presenterName = "Error4xx";
    }

    public function renderDefault(Nette\Application\BadRequestException $exception): void
    {
        $file = __DIR__ . "/templates/Error/{$exception->getCode()}.latte";
        $this->template->setFile(is_file($file) ? $file : __DIR__ . '/templates/Error/4xx.latte');
    }
}
