<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use Oliver\Application;
use jamesRUS52\TinkoffInvest\TIClient;
use jamesRUS52\TinkoffInvest\TISiteEnum;
use jamesRUS52\TinkoffInvest\TIException;

function main($event, $context): array
{
    $user_id = $_ENV['SESSION_USER_ID'] ?? '';
    $token = $_ENV['TINKOFF_OPEN_API_EXCHANGE'] ?? '';
    try {
        // @todo: на самом деле это лучше запихнуть в Application
        // чтобы была возможность покрыть тестами
        $client = new TIClient($token, TISiteEnum::EXCHANGE);
    } catch (TIException $ce) {
        // запись в лог
        print $ce->getMessage();
        print $ce->getTraceAsString();
        // завершение работы
        return [
            'response' => [
                'text' => 'Извините, я не могу подключиться к бирже, попробуйте позже, завершаю работу.',
                'end_session' => true,
            ],
            'version' => '1.0',
        ];
    }

    $app = new Application();
    $app->setClient($client);
    $app->setEvent($event);
    $app->setUserId($user_id);
    return $app->run();
}