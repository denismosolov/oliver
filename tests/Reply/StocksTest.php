<?php

declare(strict_types=1);

namespace Oliver;

use PHPUnit\Framework\TestCase;
use Oliver\Reply\Stocks;
use jamesRUS52\TinkoffInvest\TIClient;
use jamesRUS52\TinkoffInvest\TICandle;
use jamesRUS52\TinkoffInvest\TIInstrumentInfo;
use jamesRUS52\TinkoffInvest\TIPortfolio;
use jamesRUS52\TinkoffInvest\TIPortfolioInstrument;

final class StocksTest extends TestCase
{
    public function testSharesTCS(): void
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
                "command" => "баланс",
                "original_utterance" => "баланс",
                "type" => "SimpleUtterance",
                "markup" => [
                    "dangerous_context" => true
                ],
                "payload" => [],
                'nlu' => [
                    'tokens' => [
                        'баланс',
                    ],
                    'entities' => [],
                    'intents' => [
                      'my.stocks' => [
                        'slots' => [],
                      ],
                    ]
                ],
            ],
            "session" => [
                "new" => true,
            ],
            "version" => "1.0"
        ];
        $amount = 300;
        $figi = 'BBG005DXJS36';
        $ticker = 'TCS';

        $instrument_info = $this->createStub(TIInstrumentInfo::class);
        $instrument_info->method('getTrade_status')
                        ->willReturn('normal_trading');
        $portfolio = $this->createStub(TIPortfolio::class);
        $instrument = $this->createStub(TIPortfolioInstrument::class);
        $instrument->method('getInstrumentType')
                    ->willReturn('stock');
        $instrument->method('getFigi')
                    ->willReturn($figi);
        $instrument->method('getBalance')
                    ->willReturn($amount);
        $instrument->method('getTicker')
                    ->willReturn($ticker);
        $portfolio->method('getAllinstruments')
                    ->willReturn([
                        $instrument,
                    ]);
        $client = $this->createStub(TIClient::class);
        $client->method('getPortfolio')
                ->willReturn($portfolio);
        $client->method('getInstrumentInfo')
                ->willReturn($instrument_info);
        $candle = $this->createStub(TICandle::class);
        // $candle->method('getLow')
        //         ->willReturn(18.01);
        // $candle->method('getHigh')
        //         ->willReturn(19.8);
        $client->method('getCandle')
                ->willReturn($candle);

        $stocks = new Stocks($client);
        $result = $stocks->handle($event);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('text', $result['response']);
        $this->assertArrayHasKey('session_state', $result);
        $this->assertArrayHasKey('text', $result['session_state']);
        $this->assertArrayHasKey('context', $result['session_state']);
        $this->assertStringContainsStringIgnoringCase($ticker, $result['response']['text']);
        // @todo: check price and currency
        $this->assertStringContainsStringIgnoringCase('минимальная цена', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('максимальная цена', $result['response']['text']);
        // @todo: fix tinkoff invest php sdk first
        // $this->assertStringContainsStringIgnoringCase('средняя цена', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase("у вас $amount акций", $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('мои заявки', $result['response']['text']);
    }

    public function testNoSharesTCS(): void
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
                "command" => "баланс",
                "original_utterance" => "баланс",
                "type" => "SimpleUtterance",
                "markup" => [
                    "dangerous_context" => true
                ],
                "payload" => [],
                'nlu' => [
                    'tokens' => [
                        'баланс',
                    ],
                    'entities' => [],
                    'intents' => [
                      'my.stocks' => [
                        'slots' => [],
                      ],
                    ]
                ],
            ],
            "session" => [
                "new" => true,
            ],
            "version" => "1.0"
        ];

        $portfolio = $this->createStub(TIPortfolio::class);
        $instrument = $this->createStub(TIPortfolioInstrument::class);
        $portfolio->method('getAllinstruments')
                    ->willReturn([]);
        $client = $this->createStub(TIClient::class);
        $client->method('getPortfolio')
                ->willReturn($portfolio);

        $stocks = new Stocks($client);
        $result = $stocks->handle($event);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('text', $result['response']);
        $this->assertArrayHasKey('session_state', $result);
        $this->assertArrayHasKey('text', $result['session_state']);
        $this->assertArrayHasKey('context', $result['session_state']);
        $this->assertStringContainsStringIgnoringCase('нет акций', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase('чтобы купить акции', $result['response']['text']);
    }
}
