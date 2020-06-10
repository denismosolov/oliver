<?php

declare(strict_types=1);

namespace Oliver\Reply;

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

class Stocks implements ReplyInterface
{
    /**
     * Tinkoff Invest API Client
     */
    private $client;

    public function __construct(TIClient $client)
    {
        $this->client = $client;
    }

    // @todo: try..catch
    public function handle(array $event): array
    {
        if (isset($event['request']['nlu']['intents']['my.stocks'])) {
            $port = $this->client->getPortfolio();
            $instruments = $port->getAllinstruments();
            $stocks = array_filter(
                $instruments,
                function ($i) {
                    return strtolower($i->getInstrumentType()) === 'stock';
                }
            );
            $text = '';
            foreach ($stocks as $s) {
                $tradeStatus = $this->client->getInstrumentInfo($s->getFigi());
                $status = $tradeStatus->getTrade_status();
                if ($status === 'normal_trading') {
                    $candle = $this->client->getCandle($s->getFigi(), TICandleIntervalEnum::DAY);
                    $text .= $this->normal($s, $candle);
                } elseif ($status === 'not_available_for_trading') {
                    $text .= $this->notAvailable($s);
                } else {
                    // @todo непонятно
                }
            }
            return [
                'response' => [
                    'text' => $text,
                    'end_session' => false,
                ],
                'version' => '1.0',
            ];
        }
        return [];
    }

    private function normal(TIPortfolioInstrument $stock, TICandle $candle): string
    {
        $balance = (int) $stock->getBalance();
        $ticker = $stock->getTicker();
        $dayLow = $candle->getLow();
        $dayHigh = $candle->getHigh();
        $average = $stock->getAveragePositionPrice(); // @fixme тут всегда null
        // @todo: check if null
        $shares = sprintf($balance === 1 ? "%d акция" : "%d акций", $balance); // ngettext doesnot work in Yandex Cloud
        $text = sprintf(
            "%s, минимальная цена сегодня: %g, максимальная цена: %g, у вас %s.",
            $ticker,
            $dayLow,
            $dayHigh,
            $shares
        );
        if (is_float($average) && $average) {
            $text .= sprintf('средняя цена: %g.', $average);
        }
        return $text;
    }

    private function notAvailable(TIPortfolioInstrument $stock): string
    {
        $balance = (int) $stock->getBalance();
        $ticker = $stock->getTicker();
        // @todo: check if null
        $shares = sprintf($balance === 1 ? "%d акция" : "%d акций", $balance); // ngettext doesnot work in Yandex Cloud
        $text = sprintf(
            "%s, у вас %s, биржа закрыта.",
            $ticker,
            $shares
        );
        return $text;
    }
}
