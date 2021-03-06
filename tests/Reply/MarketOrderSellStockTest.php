<?php

declare(strict_types=1);

namespace Oliver\Tests\Reply;

use jamesRUS52\TinkoffInvest\TIClient;
use jamesRUS52\TinkoffInvest\TIInstrument;
use jamesRUS52\TinkoffInvest\TIOperationEnum;
use jamesRUS52\TinkoffInvest\TIOrder;
use PHPUnit\Framework\TestCase;
use Oliver\Reply\MarketOrderSellStock;
use Oliver\Logger;
use Oliver\Tests\Extra;

final class MarketOrderSellStockTest extends TestCase
{
    use Extra;

    private const FIGI_NLMK = 'BBG004S681B4';
    private const FIGI_YANDEX = 'BBG006L8G4H1';

    public function testCreateOrder(): void
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
                        'market_order_sell_stock',
                    ],
                    'order_details' => [
                        'figi' => self::FIGI_NLMK,
                        'amount' => 10,
                        'unit' => 'lot',
                        'ticker' => 'NLMK',
                        'name' => 'НЛМК',
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
                    $this->equalTo(self::FIGI_NLMK),
                    $this->equalTo(10),
                    $this->equalTo(TIOperationEnum::SELL),
                    $this->equalTo(null) // I wonder if it works?
                )->willReturn($order);
        $logger = new Logger('');
        $newOrder = new MarketOrderSellStock($client, $logger);
        $result = $newOrder->handle($event);
        $this->assertStructure($result);
        $this->assertNotContains('market_order_sell_stock', $result['session_state']['context']);
        $this->assertStringContainsStringIgnoringCase('заявка исполнена', $result['response']['text']);
    }

    public function testAskConfirmationShare(): void
    {
        $event = [
            'session' => [
                'new' => false
            ],
            'request' => [
                'command' => 'продай 10 акций яндекс',
                'original_utterance' => 'продай 10 акций яндекс',
                'nlu' => [
                    'tokens' => [
                        'продай',
                        '10',
                        'акций',
                        'яндекс'
                    ],
                    'entities' => [
                        [
                            'type' => 'YANDEX.NUMBER',
                            'tokens' => [
                                'start' => 1,
                                'end' => 2
                            ],
                            'value' => 10
                        ]
                    ],
                    'intents' => [
                        'market.order' => [
                            'slots' => [
                                'amount' => [
                                    'type' => 'YANDEX.NUMBER',
                                    'tokens' => [
                                        'start' => 1,
                                        'end' => 2
                                    ],
                                    'value' => 10
                                ],
                                'unit' => [
                                    'type' => 'OperationUnit',
                                    'tokens' => [
                                        'start' => 2,
                                        'end' => 3
                                    ],
                                    'value' => 'share'
                                ],
                                'figi' => [
                                    'type' => 'FIGI',
                                    'tokens' => [
                                        'start' => 3,
                                        'end' => 4
                                    ],
                                    'value' => self::FIGI_YANDEX
                                ],
                                'operation' => [
                                    'type' => 'OperationType',
                                    'tokens' => [
                                        'start' => 0,
                                        'end' => 1
                                    ],
                                    'value' => 'sell'
                                ]
                            ]
                        ]
                    ]
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

        $instrument = $this->createStub(TIInstrument::class);
        $instrument->method('getName')
                    ->willReturn('Яндекс');
        $instrument->method('getTicker')
                    ->willReturn('YNDX');
        $instrument->method('getLot')
                    ->willReturn(1);
        $client = $this->createMock(TIClient::class);
        $client->expects($this->never())
                ->method('sendOrder');
        $client->method('getInstrumentByFigi')
                ->willReturn($instrument);
        $logger = new Logger('');
        $newOrder = new MarketOrderSellStock($client, $logger);
        $result = $newOrder->handle($event);
        $this->assertStructure($result);
        $this->assertContains('market_order_sell_stock', $result['session_state']['context']);
        $this->assertStringContainsStringIgnoringCase('количество акций', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('по рыночной цене', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('тикер', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('YNDX', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('для подтверждения', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('для отмены', $result['response']['text']);
    }

    public function testAskConfirmationLot(): void
    {
        $event = [
            'session' => [
                'new' => false
            ],
            'request' => [
                'command' => 'продай 10 лотов нлмк',
                'original_utterance' => 'продай 10 лотов нлмк',
                'nlu' => [
                    'tokens' => [
                        'продай',
                        '10',
                        'лотов',
                        'нлмк'
                    ],
                    'entities' => [
                        [
                            'type' => 'YANDEX.NUMBER',
                            'tokens' => [
                                'start' => 1,
                                'end' => 2
                            ],
                            'value' => 10
                        ]
                    ],
                    'intents' => [
                        'market.order' => [
                            'slots' => [
                                'amount' => [
                                    'type' => 'YANDEX.NUMBER',
                                    'tokens' => [
                                        'start' => 1,
                                        'end' => 2
                                    ],
                                    'value' => 10
                                ],
                                'unit' => [
                                    'type' => 'OperationUnit',
                                    'tokens' => [
                                        'start' => 2,
                                        'end' => 3
                                    ],
                                    'value' => 'lot'
                                ],
                                'figi' => [
                                    'type' => 'FIGI',
                                    'tokens' => [
                                        'start' => 3,
                                        'end' => 4
                                    ],
                                    'value' => self::FIGI_NLMK
                                ],
                                'operation' => [
                                    'type' => 'OperationType',
                                    'tokens' => [
                                        'start' => 0,
                                        'end' => 1
                                    ],
                                    'value' => 'sell'
                                ]
                            ]
                        ]
                    ]
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

        $instrument = $this->createStub(TIInstrument::class);
        $instrument->method('getName')
                    ->willReturn('НЛМК');
        $instrument->method('getTicker')
                    ->willReturn('NLMK');
        $client = $this->createMock(TIClient::class);
        $client->expects($this->never())
                ->method('sendOrder');
        $client->method('getInstrumentByFigi')
                ->willReturn($instrument);
        $logger = new Logger('');
        $newOrder = new MarketOrderSellStock($client, $logger);
        $result = $newOrder->handle($event);
        $this->assertStructure($result);
        $this->assertContains('market_order_sell_stock', $result['session_state']['context']);
        $this->assertStringContainsStringIgnoringCase('количество лотов', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('по рыночной цене', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('тикер', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('NLMK', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('для подтверждения', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('для отмены', $result['response']['text']);
    }

    //
    public function testHint(): void
    {
        $event = [
            'session' => [
                'new' => false
            ],
            'request' => [
                'command' => 'нет',
                'original_utterance' => 'нет',
                'nlu' => [
                    'tokens' => [
                        'нет'
                    ],
                    'entities' => [],
                    'intents' => [
                        'YANDEX.REJECT' => [
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
                        'market_order_sell_stock',
                    ],
                    'order_details' => [
                        'figi' => self::FIGI_NLMK,
                        'amount' => 10,
                        'unit' => 'lot',
                        'ticker' => 'NLMK',
                        'name' => 'НЛМК',
                    ]
                ],
                'user' => []
            ],
            'version' => '1.0'
        ];

        $client = $this->createMock(TIClient::class);
        $client->expects($this->never())
                ->method('sendOrder');
        $logger = new Logger('');
        $newOrder = new MarketOrderSellStock($client, $logger);
        $result = $newOrder->handle($event);
        $this->assertStructure($result);
        $this->assertNotContains('market_order_sell_stock', $result['session_state']['context']);
        $this->assertStringContainsStringIgnoringCase('операция отменена', $result['response']['text']);
    }

    public function testCannotRecognizeConfirmation(): void
    {
        $event = [
            'session' => [
                'new' => false
            ],
            'request' => [
                'command' => 'ой',
                'original_utterance' => 'ой',
                'nlu' => [
                    'tokens' => [
                        'ой'
                    ],
                    'entities' => [],
                    'intents' => [],
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
                        'market_order_sell_stock',
                    ],
                    'order_details' => [
                        'figi' => self::FIGI_NLMK,
                        'amount' => 10,
                        'unit' => 'lot',
                        'ticker' => 'NLMK',
                        'name' => 'НЛМК',
                    ]
                ],
                'user' => []
            ],
            'version' => '1.0'
        ];

        $instrument = $this->createStub(TIInstrument::class);
        $instrument->method('getName')
                    ->willReturn('НЛМК');
        $instrument->method('getTicker')
                    ->willReturn('NLMK');
        $client = $this->createMock(TIClient::class);
        $client->expects($this->never())
                ->method('sendOrder');
        $client->method('getInstrumentByFigi')
                ->willReturn($instrument);
        $logger = new Logger('');
        $newOrder = new MarketOrderSellStock($client, $logger);
        $result = $newOrder->handle($event);
        $this->assertStructure($result);
        $this->assertContains('market_order_sell_stock', $result['session_state']['context']);
        $this->assertStringContainsStringIgnoringCase('количество лотов', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('по рыночной цене', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('тикер', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('NLMK', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('для подтверждения', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('для отмены', $result['response']['text']);
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
        $order = new MarketOrderSellStock($client, $logger);
        $result = $order->handle($event);
        $this->assertIsArray($result);
        $this->assertEquals([], $result);
    }
}
