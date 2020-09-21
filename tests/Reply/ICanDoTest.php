<?php

declare(strict_types=1);

namespace Oliver\Tests\Reply;

use PHPUnit\Framework\TestCase;
use Oliver\Reply\ICanDo;
use Oliver\Tests\Extra;

final class ICanDoTest extends TestCase
{
    use Extra;

    public function testNormalFlow(): void
    {
        $event = [
            'request' => [
                'command' => 'что ты умеешь',
                'original_utterance' => 'что ты умеешь',
                'nlu' => [
                    'tokens' => [
                        'что',
                        'ты',
                        'умеешь'
                    ],
                    'entities' => [],
                    'intents' => [
                        'YANDEX.WHAT_CAN_YOU_DO' => [
                            'slots' => []
                        ]
                    ]
                ],
                'markup' => [
                    'dangerous_context' => false
                ],
                'type' => 'SimpleUtterance'
            ],
            'session' => [
                'new' => false,
            ],
            'state' => [
                'session' => [
                    'text' => '',
                    'context' => [],
                ],
                'user' => []
            ],
            'version' => '1.0'
        ];

        $instance = new ICanDo();
        $result = $instance->handle($event);
        $this->assertStructure($result);
        $this->assertStringContainsStringIgnoringCase('я могу', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('купи', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('продай', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('мои акции', $result['response']['text']);
    }
}
