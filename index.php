<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use Oliver\Application;
use jamesRUS52\TinkoffInvest\TIClient;
use jamesRUS52\TinkoffInvest\TISiteEnum;

function main($event, $context): array
{
    $user_id = $_ENV['SESSION_USER_ID'] ?? '';
    $token = $_ENV['TINKOFF_OPEN_API_EXCHANGE'] ?? '';
    $client = new TIClient($token, TISiteEnum::EXCHANGE);

    $app = new Application();
    $app->setClient($client);
    $app->setEvent($event);
    $app->setUserId($user_id);
    return $app->run();
}