<?php

declare(strict_types=1);

namespace Oliver;

use PHPUnit\Framework\TestCase;
use Oliver\Application;
use Dotenv\Dotenv;
use Dotenv\Repository\RepositoryBuilder;
use jamesRUS52\TinkoffInvest\TIClient;
use jamesRUS52\TinkoffInvest\TISiteEnum;

final class ApplicationTest extends TestCase
{
    public function testAccessCheck(): void
    {
        $repository = RepositoryBuilder::createWithDefaultAdapters()
            ->allowList(['SESSION_USER_ID', 'TINKOFF_OPEN_API_SANDBOX'])
            ->make();
        $dotenv = Dotenv::create($repository, __DIR__ . '/../');
        $dotenv->load();

        $token = $_ENV['TINKOFF_OPEN_API_SANDBOX'] ?? '';
        $client = new TIClient($token, TISiteEnum::SANDBOX);

        $allowed_user_id = $_ENV['SESSION_USER_ID'] ?? '';
        $restricted_user_id = '-';

        $app = new Application();
        $app->setUserId($restricted_user_id);
        $app->setClient($client);
        $app->setEvent([
            "meta" => [
                "locale" => "ru-RU",
                "timezone" => "Europe/Moscow",
                "client_id" => "ru.yandex.searchplugin/5.80 (Samsung Galaxy; Android 4.4)",
                "interfaces" => [
                    "screen" => [],
                    "account_linking" => []
                ]
            ],
            "request" => [
                "command" => "",
                "original_utterance" => "",
                "type" => "SimpleUtterance",
                "markup" => [
                    "dangerous_context" => true
                ],
                "payload" => [],
            ],
            "session" => [
                "message_id" => 0,
                "session_id" => "2eac4854-fce721f3-b845abba-20d60",
                "skill_id" => "3ad36498-f5rd-4079-a14b-788652932056",
                "user_id" => $allowed_user_id,
                "user" => [
                    "user_id" => $allowed_user_id,
                    "access_token" => "AgAAAAAB4vpbAAApoR1oaCd5yR6eiXSHqOGT8dT"
                ],
                "application" => [
                    "application_id" => $allowed_user_id
                ],
                "new" => true,
            ],
            "version" => "1.0"
        ]);
        $result = $app->run();
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('text', $result['response']);
        $this->assertEquals('Это приватный навык. У вас нет доступа. Завершаю сессию.', $result['response']['text']);

        $app->setUserId($allowed_user_id);
        $app->setEvent([
            "meta" => [
                "locale" => "ru-RU",
                "timezone" => "Europe/Moscow",
                "client_id" => "ru.yandex.searchplugin/5.80 (Samsung Galaxy; Android 4.4)",
                "interfaces" => [
                    "screen" => [],
                    "account_linking" => []
                ]
            ],
            "request" => [
                "command" => "",
                "original_utterance" => "",
                "type" => "SimpleUtterance",
                "markup" => [
                    "dangerous_context" => true
                ],
                "payload" => [],
            ],
            "session" => [
                "message_id" => 0,
                "session_id" => "2eac4854-fce721f3-b845abba-20d60",
                "skill_id" => "3ad36498-f5rd-4079-a14b-788652932056",
                "user_id" => $allowed_user_id,
                "user" => [
                    "user_id" => $allowed_user_id,
                    "access_token" => "AgAAAAAB4vpbAAApoR1oaCd5yR6eiXSHqOGT8dT"
                ],
                "application" => [
                    "application_id" => $allowed_user_id
                ],
                "new" => true,
            ],
            "version" => "1.0"
        ]);
        $result = $app->run();
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('text', $result['response']);
        $this->assertStringNotContainsStringIgnoringCase('это приватный навык', $result['response']['text']);
    }
}
