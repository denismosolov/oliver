<?php

declare(strict_types=1);

namespace Oliver\Tests\Reply;

use PHPUnit\Framework\TestCase;
use Oliver\Reply\Introduction;

final class IntroductionTest extends TestCase
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
                "command" => "",
                "original_utterance" => "",
                "type" => "SimpleUtterance",
                "markup" => [
                    "dangerous_context" => true
                ],
                "payload" => [],
            ],
            "session" => [
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
        $this->assertStringContainsStringIgnoringCase('что ты умеешь', $result['response']['text']);
    }
}
