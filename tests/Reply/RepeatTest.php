<?php

declare(strict_types=1);

namespace Oliver;

use PHPUnit\Framework\TestCase;
use Oliver\Reply\Repeat;

final class RepeatTest extends TestCase
{

    public function testIntro(): void
    {
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
                "new" => true,
            ],
            "state" => [
                "session" => [
                    "text" => "текст для проверки",
                    "context" => [
                        "some_context",
                    ]
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
        $this->assertArrayHasKey('context', $result['session_state']);
        $this->assertContains('some_context', $result['session_state']['context']);
        $this->assertEquals($result['response']['text'], $result['session_state']['text']);
    }
}
