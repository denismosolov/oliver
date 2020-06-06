<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use Oliver\Application;

function main($event, $context): array
{
    $app = new Application();
    $app->setEvent($event);
    $app->setEnv($_ENV);
    return $app->run();
}