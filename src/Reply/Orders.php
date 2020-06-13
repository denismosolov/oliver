<?php

declare(strict_types=1);

namespace Oliver\Reply;

use Oliver\Declension;
use ivanovsaleksejs\NumToText\NumToText_RU;
use ivanovsaleksejs\NumToText\Price;
use jamesRUS52\TinkoffInvest\TIClient;
use jamesRUS52\TinkoffInvest\TISiteEnum;
use jamesRUS52\TinkoffInvest\TICurrencyEnum;
use jamesRUS52\TinkoffInvest\TIInstrument;
use jamesRUS52\TinkoffInvest\TIPortfolio;
use jamesRUS52\TinkoffInvest\TIOperationEnum;
use jamesRUS52\TinkoffInvest\TIIntervalEnum;
use jamesRUS52\TinkoffInvest\TICandleIntervalEnum;
use jamesRUS52\TinkoffInvest\TICandle;
use jamesRUS52\TinkoffInvest\TIOrderBook;
use jamesRUS52\TinkoffInvest\TIInstrumentInfo;
use jamesRUS52\TinkoffInvest\TIPortfolioInstrument;
use jamesRUS52\TinkoffInvest\TIOrder;

class Orders implements ReplyInterface
{
    /**
     * Tinkoff Invest API Client
     */
    private $client;

    public function __construct(TIClient $client)
    {
        $this->client = $client;
    }

    public function handle(array $event): array
    {
        if (isset($event['request']['nlu']['intents']['my.orders'])) {
            $orders = $this->client->getOrders();
            $text = $this->orderCountHelper(count($orders));
            $i = 1;
            foreach ($orders as $o) {
                $text .= sprintf('заявка %d, ', $i);
                // @todo: ask Tinkoff to add ticker and name in addition to figi
                if ($o->getOperation() === TIOperationEnum::BUY) {
                    $text .= $this->buy($o);
                } elseif ($o->getOperation() === TIOperationEnum::SELL) {
                    $text .= $this->sell($o);
                } else {
                    // @todo: как-нибудь обработай потом, а пока игнор
                }
                $i = $i + 1;
            }
            return [
                'session_state' => [
                    'text' => $text,
                    'context' => [
                        'my_orders',
                    ]
                ],
                'response' => [
                    'text' => $text,
                    'tts' => $text,
                    'end_session' => false,
                ],
                'version' => '1.0',
            ];
        }
        return [];
    }

    private function buy(TIOrder $order): string
    {
        // @todo: акция вместо лота
        $instrument = $this->client->getInstrumentByFigi($order->getFigi()); // @todo: speed up
        $text = '';
        $text .= sprintf(
            'покупка %s, тикер %s, %d %s, ',
            $instrument->getName(),
            $instrument->getTicker(),
            $order->getRequestedLots(),
            Declension::default($order->getRequestedLots(), 'лот', 'лота', 'лотов')
        );
        $text .= sprintf('по цене %s sil <[700]> ', $this->price($order->getPrice(), $instrument->getCurrency()));
        return $text;
    }

    private function sell(TIOrder $order): string
    {
        // @todo: акция вместо лота
        $instrument = $this->client->getInstrumentByFigi($order->getFigi()); // @todo: speed up
        $text = '';
        $text .= sprintf(
            'продажа %s, тикер %s, %d %s, ',
            $instrument->getName(),
            $instrument->getTicker(),
            $order->getRequestedLots(),
            Declension::default($order->getRequestedLots(), 'лот', 'лота', 'лотов')
        );
        $text .= sprintf('по цене %s sil <[700]> ', $this->price($order->getPrice(), $instrument->getCurrency()));
        return $text;
    }

    private function price(float $price, string $currency): string
    {
        if (TICurrencyEnum::getCurrency($currency) === TICurrencyEnum::USD) {
            return Price::toText($price, [['долларов', 'доллар', 'доллара'], ['центов', 'цент', 'цента']], 'RU');
        }
        if (TICurrencyEnum::getCurrency($currency) === TICurrencyEnum::RUB) {
            return Price::toText($price, [['рублей', 'рубль', 'рубля'], ['копейка', 'копеек', 'копейки']], 'RU');
        }
        return sprintf('%g', $price);
    }

    private function orderCountHelper(int $count): string
    {
        if ($count === 0) {
            return 'У вас нет заявок, ';
        }
        $formatter = new NumToText_RU();
        $formatter->digits = ['', 'одна', 'две', 'три', 'четыре', 'пять',
            'шесть', 'семь', 'восемь', 'девять', 'одна', 'две'];
        $text = sprintf(
            'у вас %s %s, ',
            $formatter->toWords($count),
            Declension::default($count, 'заявка', 'заявки', 'заявок')
        );
        return $text;
    }
}
