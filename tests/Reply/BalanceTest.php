<?php

declare(strict_types=1);

namespace Oliver;

use PHPUnit\Framework\TestCase;
use Oliver\Reply\Balance;
use Dotenv\Dotenv;
use jamesRUS52\TinkoffInvest\TIClient;
use jamesRUS52\TinkoffInvest\TIAccount;
use jamesRUS52\TinkoffInvest\TISiteEnum;
use jamesRUS52\TinkoffInvest\TICurrencyEnum;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\Adapter\EnvConstAdapter;
use Dotenv\Repository\Adapter\PutenvAdapter;

final class BalanceTest extends TestCase
{
    private TIClient $clent;

    private TIAccount $account;

    public function setUp(): void
    {
        $repository = RepositoryBuilder::createWithNoAdapters()
            ->addAdapter(EnvConstAdapter::class)
            ->addWriter(PutenvAdapter::class)
            ->allowList(['SESSION_USER_ID', 'TINKOFF_OPEN_API_SANDBOX'])
            ->make();
        $dotenv = Dotenv::create($repository, __DIR__ . '/../../');
        $dotenv->load();

        $token = $_ENV['TINKOFF_OPEN_API_SANDBOX'] ?? '';
        $this->client = new TIClient($token, TISiteEnum::SANDBOX);
        $this->account = $this->client->sbRegister();
    }

    public function tearDown(): void
    {
        $this->client->sbRemove();
    }

    public function testSharesTCS(): void
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
                      'balance' => [
                        'slots' => [],
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
            "version" => "1.0"
        ];
        $amount = 300;
        $figi = 'BBG005DXJS36';
        $ticker = 'TCS';
        $this->client->sbCurrencyBalance(1500, TICurrencyEnum::USD);
        $this->client->sbPositionBalance($amount, $figi);

        $balance = new Balance($this->client);
        $result = $balance->handle($event);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('response', $result);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('text', $result['response']);
        $this->assertStringContainsStringIgnoringCase($ticker, $result['response']['text']);
        // $this->assertStringContainsStringIgnoringCase('минимальная цена', $result['response']['text']);
        // $this->assertStringContainsStringIgnoringCase('максимальная цена', $result['response']['text']);
        // $this->assertStringContainsStringIgnoringCase('средняя цена', $result['response']['text']);
        $this->assertStringContainsStringIgnoringCase("у вас $amount акций", $result['response']['text']);
    }
}
