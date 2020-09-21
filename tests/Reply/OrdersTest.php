<?php

declare(strict_types=1);

namespace Oliver\Tests\Reply;

use PHPUnit\Framework\TestCase;
use jamesRUS52\TinkoffInvest\TIClient;
use jamesRUS52\TinkoffInvest\TIOperationEnum;
use jamesRUS52\TinkoffInvest\TIAccount;
use jamesRUS52\TinkoffInvest\TISiteEnum;
use jamesRUS52\TinkoffInvest\TICurrencyEnum;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\Adapter\EnvConstAdapter;
use Dotenv\Repository\Adapter\PutenvAdapter;
use jamesRUS52\TinkoffInvest\TIInstrument;
use jamesRUS52\TinkoffInvest\TIOrder;
use Oliver\Reply\Orders;

final class OrdersTest extends TestCase
{
    public function testEmptyOrdersList(): void
    {
        $event = [
            "request" => [
                "command" => "мои заявяки",
                "original_utterance" => "мои заявки",
                "type" => "SimpleUtterance",
                "markup" => [
                    "dangerous_context" => true
                ],
                "payload" => [],
                'nlu' => [
                    'tokens' => [
                        'мои заявки',
                    ],
                    'entities' => [],
                    'intents' => [
                      'my.orders' => [
                        'slots' => [],
                      ],
                    ]
                ],
            ],
        ];
        $client = $this->createStub(TIClient::class);
        $client->method('getOrders')
               ->willReturn([]);
        $stocks = new Orders($client);
        $result = $stocks->handle($event);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('text', $result['response']);
        $this->assertArrayHasKey('session_state', $result);
        $this->assertArrayHasKey('text', $result['session_state']);
        $this->assertArrayHasKey('context', $result['session_state']);
        $this->assertContains('my_orders', $result['session_state']['context']);
        $this->assertStringContainsStringIgnoringCase('у вас нет заявок', $result['response']['text']);
    }

    public function testSingleOrderBuy(): void
    {
        $event = [
            "request" => [
                "command" => "мои заявяки",
                "original_utterance" => "мои заявки",
                "type" => "SimpleUtterance",
                "markup" => [
                    "dangerous_context" => true
                ],
                "payload" => [],
                'nlu' => [
                    'tokens' => [
                        'мои заявки',
                    ],
                    'entities' => [],
                    'intents' => [
                      'my.orders' => [
                        'slots' => [],
                      ],
                    ]
                ],
            ],
        ];
        $order = $this->createStub(TIOrder::class);
        $order->method('getOperation')
                ->willReturn(TIOperationEnum::BUY);
        $order->method('getRequestedLots')
                ->willReturn(1);
        $order->method('getPrice')
                ->willReturn(14.04);

        $instrument = $this->createStub(TIInstrument::class);
        $instrument->method('getName')
                    ->willReturn('TCS Group (Tinkoff Bank holder)');
        $instrument->method('getTicker')
                    ->willReturn('TCS');
        $instrument->method('getCurrency')
                    ->willReturn('USD');
        $client = $this->createStub(TIClient::class);
        $client->method('getOrders')
               ->willReturn([$order]);
        $client->method('getInstrumentByFigi')
               ->willReturn($instrument);
        $stocks = new Orders($client);
        $result = $stocks->handle($event);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('session_state', $result);
        $this->assertArrayHasKey('text', $result['session_state']);
        $this->assertArrayHasKey('context', $result['session_state']);
        $this->assertArrayHasKey('text', $result['response']);
        $this->assertStringContainsStringIgnoringCase('одна заявка', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('четырнадцать долларов', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('четыре цента', $result['response']['text']);
    }
    // @todo:
    // 2. одна заявка на покупку
    // 3. одна заявка на продажу
    // 4. две заявки на покупку и продажу
    // 5. 1 лот, 2 лота, 5 лотов, 11 лотов, 20 лотов, 21 лот, 31 лот
    // 6. цена в рублях без копеек, цена в рублях с копейками, цена в копейках
    // 7. цена в долларах без центов, цена в долларах с центами, цена в центах

    // в ApplicationTest проверить, что этот класс вообще вызывается
}
