<?php

declare(strict_types=1);

namespace Oliver\Tests\Reply;

use jamesRUS52\TinkoffInvest\TIClient;
use jamesRUS52\TinkoffInvest\TIInstrument;
use jamesRUS52\TinkoffInvest\TIOperationEnum;
use jamesRUS52\TinkoffInvest\TIOrder;
use PHPUnit\Framework\TestCase;
use Oliver\Reply\Order;
use Oliver\Logger;
use Oliver\Tests\Extra;

final class OrderTest extends TestCase
{
    use Extra;

    public const FIGI_USDRUB = 'BBG0013HGFT4';

    private function assertOrderContext(array $result): void
    {
        $this->assertContains('order', $result['session_state']['context']);
        $this->assertIsArray($result['session_state']['context']['order']);
        $this->assertContains('operation', $result['session_state']['context']['order']);
        $this->assertContains('type', $result['session_state']['context']['order']);
        // @todo: add market
    }

    public function testSkip(): void
    {
        $event = [
            'session' => [
                'new' => false
            ],
            'request' => [
                'command' => 'мои акции',
                'original_utterance' => 'мои акции',
                'nlu' => [
                    'tokens' => [
                        'мои',
                        'акции'
                    ],
                    'entities' => [],
                    'intents' => [],
                ],
                'markup' => [
                    'dangerous_context' => false
                ],
                'type' => 'SimpleUtterance'
            ],
            'state' => [
                'session' => [
                    'text' => '',
                    'context' => []
                ],
                'user' => []
            ],
            'version' => '1.0'
        ];

        $client = $this->createMock(TIClient::class);
        $client->expects($this->never())
                ->method('sendOrder');
        $logger = new Logger('');
        $order = new Order($client, $logger);
        $result = $order->handle($event);
        $this->assertIsArray($result);
        $this->assertEquals([], $result);
    }

    public function testBuy1LotUSDRUBConfirmation(): void
    {
        $event = [
            'session' => [
                'new' => true
            ],
            'request' => [
                'command' => 'купи доллары 1 лот',
                'original_utterance' => 'купи доллары один лот',
                'nlu' => [
                    'tokens' => [
                        'купи',
                        'доллары',
                        '1',
                        'лот',
                    ],
                    'intents' => [
                        'order' => [
                            'slots' => [
                                'amount' => [
                                    'type' => 'YANDEX.NUMBER',
                                    'tokens' => [
                                        'start' => 2,
                                        'end' => 3,
                                    ],
                                    'value' => 1,
                                ],
                                'unit' => [
                                    'type' => 'OperationUnit',
                                    'tokens' => [
                                        'start' => 3,
                                        'end' => 4,
                                    ],
                                    'value' => 'lot',
                                ],
                                'figi' => [
                                    'type' => 'FIGI',
                                    'tokens' => [
                                        'start' => 1,
                                        'end' => 2,
                                    ],
                                    'value' => 'BBG0013HGFT4',
                                ],
                                'operation' => [
                                    'type' => 'OperationType',
                                    'tokens' => [
                                        'start' => 0,
                                        'end' => 1,
                                    ],
                                    'value' => 'buy',
                                ],
                            ]
                        ]
                    ],
                    'markup' => [
                        'dangerous_context' => false
                    ],
                    'type' => 'SimpleUtterance'
                ],
            ],
            'version' => '1.0'
        ];

        $instrument = $this->createStub(TIInstrument::class);
        $instrument->method('getName')
                    ->willReturn('Доллар США');
        $instrument->method('getTicker')
                    ->willReturn(self::FIGI_USDRUB);
        $instrument->method('getType')
                    ->willReturn('Currency');
        $instrument->method('getLot')
                    ->willReturn(1000);

        $client = $this->createMock(TIClient::class);
        $client->expects($this->never())
                ->method('sendOrder');
        $client->method('getInstrumentByFigi')
                ->willReturn($instrument);

        $logger = new Logger('');
        $newOrder = new Order($client, $logger);
        $result = $newOrder->handle($event);
        $this->assertStructure($result);
        $this->assertOrderContext($result);

        $this->assertStringContainsStringIgnoringCase('покупка', $result['response']['text']);
        $this->assertStringNotContainsStringIgnoringCase('продажа', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('доллар сша', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('количество лотов', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('по рыночной цене', $result['response']['text']);
    }

    public function testBuy1LotUSDRUBOrder(): void
    {
        $event = [
            'session' => [
                'new' => false
            ],
            'request' => [
                'command' => 'да',
                'original_utterance' => 'да',
                'nlu' => [
                    'tokens' => [
                        'да'
                    ],
                    'entities' => [],
                    'intents' => [
                        'YANDEX.CONFIRM' => [
                            'slots' => []
                        ]
                    ],
                    'markup' => [
                        'dangerous_context' => false
                    ],
                    'type' => 'SimpleUtterance'
                ],
            ],
            'state' => [
                'session' => [
                    'text' => '',
                    'context' => [
                        'order',
                    ],
                    'order_details' => [
                        'operation' => 'buy',
                        'figi' => self::FIGI_USDRUB,
                        'type' => 'currency',
                        'amount' => 1,
                        'unit' => 'lot',
                        'name' => 'Доллар США',
                    ]
                ],
                'user' => []
            ],
            'version' => '1.0'
        ];

        $order = $this->createStub(TIOrder::class);
        $order->method('getStatus')
              ->willReturn('Fill');
        $client = $this->createMock(TIClient::class);
        $client->expects($this->once())
                ->method('sendOrder')
                ->with(
                    $this->equalTo(self::FIGI_USDRUB),
                    $this->equalTo(1),
                    $this->equalTo(TIOperationEnum::BUY),
                    $this->equalTo(null) // I wonder if it works?
                )->willReturn($order);
        $logger = new Logger('');
        $newOrder = new Order($client, $logger);
        $result = $newOrder->handle($event);
        $this->assertStructure($result);
        $this->assertNotContains('order', $result['session_state']['context']);
        $this->assertStringContainsStringIgnoringCase('заявка исполнена', $result['response']['text']);
    }

    public function testSell1LotUSDRUBConfirmation(): void
    {
        $event = [
            'session' => [
                'new' => true
            ],
            'request' => [
                'command' => 'продай доллары 1 лот',
                'original_utterance' => 'продай доллары один лот',
                'nlu' => [
                    'tokens' => [
                        'продай',
                        'доллары',
                        '1',
                        'лот',
                    ],
                    'intents' => [
                        'order' => [
                            'slots' => [
                                'amount' => [
                                    'type' => 'YANDEX.NUMBER',
                                    'tokens' => [
                                        'start' => 2,
                                        'end' => 3,
                                    ],
                                    'value' => 1,
                                ],
                                'unit' => [
                                    'type' => 'OperationUnit',
                                    'tokens' => [
                                        'start' => 3,
                                        'end' => 4,
                                    ],
                                    'value' => 'lot',
                                ],
                                'figi' => [
                                    'type' => 'FIGI',
                                    'tokens' => [
                                        'start' => 1,
                                        'end' => 2,
                                    ],
                                    'value' => 'BBG0013HGFT4',
                                ],
                                'operation' => [
                                    'type' => 'OperationType',
                                    'tokens' => [
                                        'start' => 0,
                                        'end' => 1,
                                    ],
                                    'value' => 'sell',
                                ],
                            ]
                        ]
                    ],
                    'markup' => [
                        'dangerous_context' => false
                    ],
                    'type' => 'SimpleUtterance'
                ],
            ],
            'version' => '1.0'
        ];

        $instrument = $this->createStub(TIInstrument::class);
        $instrument->method('getName')
                    ->willReturn('Доллар США');
        $instrument->method('getTicker')
                    ->willReturn(self::FIGI_USDRUB);
        $instrument->method('getType')
                    ->willReturn('Currency');
        $instrument->method('getLot')
                    ->willReturn(1000);

        $client = $this->createMock(TIClient::class);
        $client->expects($this->never())
                ->method('sendOrder');
        $client->method('getInstrumentByFigi')
                ->willReturn($instrument);

        $logger = new Logger('');
        $newOrder = new Order($client, $logger);
        $result = $newOrder->handle($event);
        $this->assertStructure($result);
        $this->assertOrderContext($result);
        $this->assertStringContainsStringIgnoringCase('продажа', $result['response']['text']);
        $this->assertStringNotContainsStringIgnoringCase('покупка', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('доллар сша', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('количество лотов', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('по рыночной цене', $result['response']['text']);
    }

    public function testSell1LotUSDRUBOrder(): void
    {
        $event = [
            'session' => [
                'new' => false
            ],
            'request' => [
                'command' => 'да',
                'original_utterance' => 'да',
                'nlu' => [
                    'tokens' => [
                        'да'
                    ],
                    'entities' => [],
                    'intents' => [
                        'YANDEX.CONFIRM' => [
                            'slots' => []
                        ]
                    ],
                    'markup' => [
                        'dangerous_context' => false
                    ],
                    'type' => 'SimpleUtterance'
                ],
            ],
            'state' => [
                'session' => [
                    'text' => '',
                    'context' => [
                        'order',
                    ],
                    'order_details' => [
                        'operation' => 'sell',
                        'figi' => self::FIGI_USDRUB,
                        'type' => 'currency',
                        'amount' => 1,
                        'unit' => 'lot',
                        'name' => 'Доллар США',
                    ]
                ],
                'user' => []
            ],
            'version' => '1.0'
        ];

        $order = $this->createStub(TIOrder::class);
        $order->method('getStatus')
              ->willReturn('Fill');
        $client = $this->createMock(TIClient::class);
        $client->expects($this->once())
                ->method('sendOrder')
                ->with(
                    $this->equalTo(self::FIGI_USDRUB),
                    $this->equalTo(1),
                    $this->equalTo(TIOperationEnum::BUY),
                    $this->equalTo(null) // I wonder if it works?
                )->willReturn($order);
        $logger = new Logger('');
        $newOrder = new Order($client, $logger);
        $result = $newOrder->handle($event);
        $this->assertStructure($result);
        $this->assertNotContains('order', $result['session_state']['context']);
        $this->assertStringContainsStringIgnoringCase('заявка исполнена', $result['response']['text']);
    }
}
