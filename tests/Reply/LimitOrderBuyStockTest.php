<?php

declare(strict_types=1);

namespace Oliver;

use PHPUnit\Framework\TestCase;
use jamesRUS52\TinkoffInvest\TIClient;
use jamesRUS52\TinkoffInvest\TIOperationEnum;
use jamesRUS52\TinkoffInvest\TIAccount;
use jamesRUS52\TinkoffInvest\TISiteEnum;
use jamesRUS52\TinkoffInvest\TICurrencyEnum;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\Adapter\EnvConstAdapter;
use Dotenv\Repository\Adapter\PutenvAdapter;
use jamesRUS52\TinkoffInvest\TIException;
use jamesRUS52\TinkoffInvest\TIInstrument;
use jamesRUS52\TinkoffInvest\TIOrder;
use Oliver\Reply\LimitOrderBuyStock;

final class LimitOrderBuyStockTest extends TestCase
{
    public function testLimitOrderBuyStockConfirm(): void
    {
        $event = [
            'request' => [
                'command' => 'подтверждаю',
                'original_utterance' => 'подтверждаю',
                'nlu' => [
                    'tokens' => [
                        'подтверждаю'
                    ],
                    'entities' => [],
                    'intents' => [
                        'YANDEX.CONFIRM' => [
                            'slots' => []
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
                    'text' => 'заявка на покупку 10 лотов НЛМК, тикер NLMK, по цене сто двадцать рублей пятьдесят копеек за акцию. сумма сделки одна тысяча двести пять рублей плюс комиссия брокера. для подтверждения скажите подтверждаю.',
                    'context' => [
                        'limit_order_buy_stock',
                    ],
                    'order_details' => [
                        'price' => 120.5,
                        'figi' => 'BBG004S681B4',
                        'requestedLots' => 10,
                        'name' => 'НЛМК',
                    ]
                ],
                'user' => []
            ]
        ];
        $order = $this->createStub(TIOrder::class);
        $order->method('getStatus')
              ->willReturn('New');
        $client = $this->createMock(TIClient::class);
        $client->expects($this->once())
                ->method('sendOrder')
                ->with(
                    $this->equalTo('BBG004S681B4'),
                    $this->equalTo(10),
                    $this->equalTo(TIOperationEnum::BUY)
                )->willReturn($order);

        $limitOrder = new LimitOrderBuyStock($client);
        $result = $limitOrder->handle($event);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('text', $result['response']);
        $this->assertArrayHasKey('session_state', $result);
        $this->assertArrayHasKey('text', $result['session_state']);
        $this->assertArrayHasKey('context', $result['session_state']);
        $this->assertNotContains('limit_order_buy_stock', $result['session_state']['context']);
        $this->assertStringContainsStringIgnoringCase('лимитная заявка на покупку создана', $result['response']['text']);
        // @todo: lot, figi and price not in session


        $order = $this->createStub(TIOrder::class);
        $order->method('getStatus')
              ->willReturn('PendingNew');
        $client = $this->createMock(TIClient::class);
        $client->expects($this->once())
                ->method('sendOrder')
                ->with(
                    $this->equalTo('BBG004S681B4'),
                    $this->equalTo(10),
                    $this->equalTo(TIOperationEnum::BUY)
                )->willReturn($order);

        $limitOrder = new LimitOrderBuyStock($client);
        $result = $limitOrder->handle($event);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('text', $result['response']);
        $this->assertArrayHasKey('session_state', $result);
        $this->assertArrayHasKey('text', $result['session_state']);
        $this->assertArrayHasKey('context', $result['session_state']);
        $this->assertNotContains('limit_order_buy_stock', $result['session_state']['context']);
        $this->assertStringContainsStringIgnoringCase('лимитная заявка на покупку отправлена', $result['response']['text']);


        $order = $this->createStub(TIOrder::class);
        $order->method('getStatus')
              ->willReturn('Rejected');
        $order->method('getRejectReason')
              ->willReturn('Unknown');
        $order->method('getMessage')
              ->willReturn('ОШИБКА: (579) Для выбранного финансового инструмента цена должна быть не меньше 126.02');
        $client = $this->createMock(TIClient::class);
        $client->expects($this->once())
                ->method('sendOrder')
                ->with(
                    $this->equalTo('BBG004S681B4'),
                    $this->equalTo(10),
                    $this->equalTo(TIOperationEnum::BUY)
                )->willReturn($order);

        $limitOrder = new LimitOrderBuyStock($client);
        $result = $limitOrder->handle($event);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('text', $result['response']);
        $this->assertArrayHasKey('session_state', $result);
        $this->assertArrayHasKey('text', $result['session_state']);
        $this->assertArrayHasKey('context', $result['session_state']);
        $this->assertNotContains('limit_order_buy_stock', $result['session_state']['context']);
        $this->assertStringContainsStringIgnoringCase('лимитная заявка на покупку отклонена системой', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('для выбранного финансового инструмента цена должна быть не меньше', $result['response']['text']);
        $this->assertStringNotContainsStringIgnoringCase('(', $result['response']['text']);

        /* does not work becase TIException extends Exception which is final
        $exception = $this->createStub(TIException::class);
        $exception->method('getMessage')
                  ->willReturn('Недостаточно активов для сделки [OrderNotAvailable]');
        $client = $this->createMock(TIClient::class);
        $client->expects($this->once())
                ->method('sendOrder')
                ->with(
                    $this->equalTo('BBG004S681B4'),
                    $this->equalTo(10),
                    $this->equalTo(TIOperationEnum::BUY)
                )->willThrowException($exception);

        $limitOrder = new LimitOrderBuyStock($client);
        $result = $limitOrder->handle($event);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('text', $result['response']);
        $this->assertArrayHasKey('session_state', $result);
        $this->assertArrayHasKey('text', $result['session_state']);
        $this->assertArrayHasKey('context', $result['session_state']);
        $this->assertNotContains('limit_order_buy_stock', $result['session_state']['context']);
        $this->assertStringContainsStringIgnoringCase('недостаточно активов для сделки', $result['response']['text']);
        $this->assertStringNotContainsStringIgnoringCase('OrderNotAvailable', $result['response']['text']);


        $exception = $this->createStub(TIException::class);
        $exception->method('getMessage')
                  ->willReturn('[price]: 129.99 has invalid scale, minPriceIncrement=0.02 [VALIDATION_ERROR]');
        $client = $this->createMock(TIClient::class);
        $client->expects($this->once())
                ->method('sendOrder')
                ->with(
                    $this->equalTo('BBG004S681B4'),
                    $this->equalTo(10),
                    $this->equalTo(TIOperationEnum::BUY)
                )->willThrowException($exception);

        $limitOrder = new LimitOrderBuyStock($client);
        $result = $limitOrder->handle($event);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('text', $result['response']);
        $this->assertArrayHasKey('session_state', $result);
        $this->assertArrayHasKey('text', $result['session_state']);
        $this->assertArrayHasKey('context', $result['session_state']);
        $this->assertNotContains('limit_order_buy_stock', $result['session_state']['context']);
        $this->assertStringContainsStringIgnoringCase('недопустимый шаг цены', $result['response']['text']);
        $this->assertStringNotContainsStringIgnoringCase('has invalid scale', $result['response']['text']);
        */
    }

    public function testLimitOrderBuyStockReject(): void
    {
        $event = [
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
                    ]
                ],
                'markup' => [
                    'dangerous_context' => false
                ],
                'type' => 'SimpleUtterance'
            ],
            'state' => [
                'session' => [
                    'text' => 'заявка на покупку 10 лотов НЛМК, тикер NLMK, по цене сто двадцать рублей пятьдесят копеек за акцию. сумма сделки одна тысяча двести пять рублей плюс комиссия брокера. для подтверждения скажите подтверждаю.',
                    'context' => [
                        'limit_order_buy_stock',
                    ],
                    'order_details' => [
                        'price' => 120.5,
                        'figi' => 'BBG004S681B4',
                        'requestedLots' => 10,
                        'name' => 'НЛМК',
                    ]
                ],
                'user' => []
            ]
        ];
        $client = $this->createMock(TIClient::class);
        $client->expects($this->never())
                ->method('sendOrder');

        $newOrder = new LimitOrderBuyStock($client);
        $result = $newOrder->handle($event);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('text', $result['response']);
        $this->assertArrayHasKey('session_state', $result);
        $this->assertArrayHasKey('text', $result['session_state']);
        $this->assertArrayHasKey('context', $result['session_state']);
        $this->assertNotContains('limit_order_buy_stock', $result['session_state']['context']);
        $this->assertStringContainsStringIgnoringCase('заявка отменена', $result['response']['text']);
        // @todo: lot, figi and price not in session
    }

    // @todo:
    // в ApplicationTest проверить, что этот класс вообще вызывается
    public function testLimitOrderBuyStock(): void
    {
        $event = [
            'request' => [
                'command' => 'купи 10 лотов нлмк по цене десять рублей пятьдесят копеек',
                'original_utterance' => 'купи 10 лотов нлмк по цене десять рублей пятьдесят копеек',
                'nlu' => [
                   'tokens' => [
                      'купи',
                      '10',
                      'лотов',
                      'нлмк',
                      'по',
                      'цене',
                      '100',
                      'р',
                      '50',
                      'к'
                   ],
                   'entities' => [
                        [
                            'type' => 'YANDEX.NUMBER',
                            'tokens' => [
                                'start' => 1,
                                'end' => 2
                            ],
                            'value' => 10
                        ],
                        [
                            'type' => 'YANDEX.NUMBER',
                            'tokens' => [
                                'start' => 6,
                                'end' => 7
                            ],
                            'value' => 100
                        ],
                        [
                            'type' => 'YANDEX.NUMBER',
                            'tokens' => [
                                'start' => 8,
                                'end' => 9
                            ],
                            'value' => 50
                        ],
                        [
                            'type' => 'YANDEX.NUMBER',
                            'tokens' => [
                                'start' => 11,
                                'end' => 12
                            ],
                            'value' => 1
                        ]
                    ],
                    'intents' => [
                        'limit.order.buy.stock' => [
                            'slots' => [
                                'currency1' => [
                                    'type' => 'YANDEX.STRING',
                                    'tokens' => [
                                        'start' => 7,
                                        'end' => 8
                                    ],
                                    'value' => 'рублей'
                                ],
                                'currency2' => [
                                    'type' => 'YANDEX.STRING',
                                    'tokens' => [
                                        'start' => 9,
                                        'end' => 10
                                    ],
                                    'value' => 'копеек'
                                ],
                                'figi' => [
                                    'type' => 'FIGI',
                                    'tokens' => [
                                        'start' => 3,
                                        'end' => 4
                                    ],
                                    'value' => 'BBG004S681B4'
                                ],
                                'requestedLots' => [
                                    'type' => 'YANDEX.NUMBER',
                                    'tokens' => [
                                        'start' => 1,
                                        'end' => 2
                                    ],
                                    'value' => 10
                                ],
                                'price1' => [
                                    'type' => 'YANDEX.NUMBER',
                                    'tokens' => [
                                        'start' => 6,
                                        'end' => 7
                                    ],
                                    'value' => 100
                                ],
                                'price2' => [
                                    'type' => 'YANDEX.NUMBER',
                                    'tokens' => [
                                        'start' => 8,
                                        'end' => 9
                                    ],
                                    'value' => 50
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $client = $this->createStub(TIClient::class);
        $client->method('getInstrumentByFigi')
                ->willReturn(new TIInstrument(
                    'BBG004S681B4',
                    'NLMK',
                    null,
                    null,
                    10,
                    TICurrencyEnum::RUB,
                    'НЛМК',
                    null,
                ));

        $newOrder = new LimitOrderBuyStock($client);
        $result = $newOrder->handle($event);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('text', $result['response']);
        $this->assertArrayHasKey('session_state', $result);
        $this->assertArrayHasKey('text', $result['session_state']);
        $this->assertArrayHasKey('context', $result['session_state']);
        $this->assertContains('limit_order_buy_stock', $result['session_state']['context']);
        $this->assertArrayHasKey('order_details', $result['session_state']);
        $this->assertArrayHasKey('price', $result['session_state']['order_details']);
        $this->assertEquals(100.5, $result['session_state']['order_details']['price']);
        $this->assertArrayHasKey('figi', $result['session_state']['order_details']);
        $this->assertEquals('BBG004S681B4', $result['session_state']['order_details']['figi']);
        $this->assertArrayHasKey('requestedLots', $result['session_state']['order_details']);
        $this->assertEquals(10, $result['session_state']['order_details']['requestedLots']);
        $this->assertStringContainsStringIgnoringCase('количество лотов: десять', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase(
            'цена за акцию: сто рублей пятьдесят копеек',
            $result['response']['text']
        );
        $this->assertStringContainsStringIgnoringCase('тикер: NLMK', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('сумма заявки: десять тысяч пятьдесят рублей', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('для подтверждения', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('для отмены', $result['response']['text']);
    }
}
