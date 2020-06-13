<?php

declare(strict_types=1);

namespace Oliver;

use PHPUnit\Framework\TestCase;
use Oliver\Reply\Repeat;

final class RepeatTest extends TestCase
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
                "command" => "повтори",
                "original_utterance" => "повтори",
                "type" => "SimpleUtterance",
                "markup" => [
                    "dangerous_context" => true
                ],
                "payload" => [],
                "nlu" => [
                    "tokens" => [
                        "повтори",
                    ],
                    "entities" => [],
                    "intents" => [
                      "YANDEX.REPEAT" => [
                        "slots" => [],
                      ],
                    ]
                ],
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
            "state" => [
                "session" => [
                    "text" => "текст для проверки",
                ],
            ],
            "version" => "1.0"
        ];

        $repeat = new Repeat();
        $result = $repeat->handle($event);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('text', $result['response']);
        $this->assertArrayHasKey('session_state', $result);
        $this->assertArrayHasKey('text', $result['session_state']);
        $this->assertEquals($result['response']['text'], $result['session_state']['text']);
    }
}