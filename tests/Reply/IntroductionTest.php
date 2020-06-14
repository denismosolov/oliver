<?php

declare(strict_types=1);

namespace Oliver;

use PHPUnit\Framework\TestCase;
use Oliver\Reply\Introduction;
use Dotenv\Dotenv;

final class IntroductionTest extends TestCase
{

    public function testIntro(): void
    {
        $user_id = $_ENV['SESSION_USER_ID'] ?? '';
        $event = [
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
                "user_id" => $user_id,
                "user" => [
                    "user_id" => $user_id,
                    "access_token" => "AgAAAAAB4vpbAAApoR1oaCd5yR6eiXSHqOGT8dT"
                ],
                "application" => [
                    "application_id" => $user_id
                ],
                "new" => true,
            ],
            "version" => "1.0"
        ];

        $balance = new Introduction();
        $result = $balance->handle($event);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('text', $result['response']);
        $this->assertArrayHasKey('session_state', $result);
        $this->assertArrayHasKey('text', $result['session_state']);
        $this->assertArrayHasKey('context', $result['session_state']);
        $this->assertStringContainsStringIgnoringCase('здравствуйте', $result['response']['text']);
    }
}
