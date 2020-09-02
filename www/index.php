<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

define('UPLOADS_DIR', __DIR__."/../uploads/");

App\Bootstrap::boot()
    ->createContainer()
    ->getByType(Nette\Application\Application::class)
    ->run();
