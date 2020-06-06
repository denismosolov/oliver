<?php

declare(strict_types=1);

namespace Oliver;

use PHPUnit\Framework\TestCase;
use Oliver\Application;

final class ApplicationTest extends TestCase
{
    private function checkVersion(array $result): void
    {
        $this->assertArrayHasKey('version', $result);
        $this->assertEquals($result['version'], '1.0');
    }

    public function testAccessCheck(): void
    {
        $app = new Application();
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
                "user_id" => "47C73714B580ED2469056E71081159529FFC676A4E5B059D629A819E857DC2F8",
                "user" => [
                    "user_id" => "6C91DA5198D1758C6A9F63A7C5CDDF09359F683B13A18A151FBF4C8B092BB0C2",
                    "access_token" => "AgAAAAAB4vpbAAApoR1oaCd5yR6eiXSHqOGT8dT"
                ],
                "application" => [
                    "application_id" => "47C73714B580ED2469056E71081159529FFC676A4E5B059D629A819E857DC2F8"
                ],
                "new" => true,
            ],
            "version" => "1.0"
        ]);
        $result = $app->run();
        $this->checkVersion($result);
        $this->arrayHasKey('response');
        $this->arrayHasKey('text');
        $this->assertEquals($result['response']['text'], 'Это приватный навык. У вас нет доступа. Завершаю сессию.');

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
                "user_id" => "47C73714B580ED2469056E71081159529FFC676A4E5B059D629A819E857DC2F8",
                "user" => [
                    "user_id" => "6C91DA5198D1758C6A9F63A7C5CDDF09359F683B13A18A151FBF4C8B092BB0C2",
                    "access_token" => "AgAAAAAB4vpbAAApoR1oaCd5yR6eiXSHqOGT8dT"
                ],
                "application" => [
                    "application_id" => "47C73714B580ED2469056E71081159529FFC676A4E5B059D629A819E857DC2F8"
                ],
                "new" => true,
            ],
            "version" => "1.0"
        ]);
        $app->setEnv(['SESSION_USER_ID' => '6C91DA5198D1758C6A9F63A7C5CDDF09359F683B13A18A151FBF4C8B092BB0C2']);
        $result = $app->run();
        $this->checkVersion($result);
        $this->arrayHasKey('response');
        $this->arrayHasKey('text');
        $this->assertEquals($result['response']['text'], 'всё хорошо');
    }
}
