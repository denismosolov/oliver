<?php

declare(strict_types=1);

namespace Oliver\Reply;

use ivanovsaleksejs\NumToText\Num;
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
use jamesRUS52\TinkoffInvest\TIException;
use jamesRUS52\TinkoffInvest\TIOrderBook;
use jamesRUS52\TinkoffInvest\TIInstrumentInfo;
use jamesRUS52\TinkoffInvest\TIPortfolioInstrument;
use jamesRUS52\TinkoffInvest\TIOrder;
use Oliver\Declension;
use Oliver\InvalidPriceException;
use Oliver\Price as StockPrice;

class LimitOrderBuyStock implements ReplyInterface
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
        $confirmed = isset($event['request']['nlu']['intents']['YANDEX.CONFIRM']) &&
            $event['request']['nlu']['intents']['YANDEX.CONFIRM'];
        $rejected = isset($event['request']['nlu']['intents']['YANDEX.REJECT']) &&
            $event['request']['nlu']['intents']['YANDEX.REJECT'];

        if ($this->userPlacesOrder($event)) {
            return $this->askConfirmation($event);
        } elseif ($confirmed && $this->userReplies($event)) {
            return $this->createLimitOrder($event);
        } elseif ($rejected && $this->userReplies($event)) {
            return $this->rejected();
        } elseif (! $confirmed && ! $rejected && $this->userReplies($event)) {
            return $this->askConfirmation($event);
        } else {
            // @todo: hint
            return [];
        }
    }

    /**
     * Создаёт лимитную заявку на покупку акций и сообщает результат пользователю
     */
    private function createLimitOrder(array $event): array
    {
        $text = '';
        try {
            // TIOrder
            $order = $this->client->sendOrder(
                $event['state']['session']['order_details']['figi'],
                $event['state']['session']['order_details']['requestedLots'],
                TIOperationEnum::BUY,
                $event['state']['session']['order_details']['price'],
            );
            switch ($order->getStatus()) {
                // [ New, PartiallyFill, Fill, Cancelled, Replaced, PendingCancel, Rejected, PendingReplace, PendingNew ]
                case 'New':
                    $text = 'лимитная заявка на покупку создана,';
                    break;
                case 'PendingNew':
                    $text = 'лимитная заявка на покупку отправлена,';
                    break;
                case 'Rejected':
                    $text = 'лимитная заявка на покупку отклонена системой,';
                    // ОШИБКА: (579) Для выбранного финансового инструмента цена должна быть не меньше 126.02
                    print $order->getRejectReason() . "\n";
                    print $order->getMessage() . "\n";
                    if (
                        $order->getRejectReason() === 'Unknown' &&
                        preg_match('/ОШИБКА:\s+\(\d+\)/', $order->getMessage())
                    ) {
                        $parts = false;
                        $parts = preg_split('/ОШИБКА:\s+\(\d+\)/', $order->getMessage());
                        if (is_array($parts)) {
                            $text .= end($parts);
                        } else {
                            // @todo: ????
                        }
                    }
                    // @todo: Specified security is not found [...]
                    break;
                default:
                    // @todo: add test case
                    print $order->getStatus() . "\n";
                    $text = 'произошло что-то непонятное, проверьте свои заявки и акции,';
                    break;
            }
        } catch (TIException $te) {
            print $te->getMessage() . "\n";
            // Недостаточно активов для сделки [OrderNotAvailable]
            if (preg_match('/\[OrderNotAvailable\]/', $te->getMessage())) {
                $text = preg_replace('/\[OrderNotAvailable\]/', '', $te->getMessage());
                if (is_null($text)) {
                    // @todo: ????
                }
            } elseif (preg_match('/\[VALIDATION_ERROR\]/', $te->getMessage())) {
                if (preg_match('/has invalid scale/', $te->getMessage())) {
                    $text .= 'недопустимый шаг цены, узнайте минимальный шаг цены для этого инструмента на бирже,';
                }
            } else {
                $text = 'ошибка при взаимодействии с биржей, попробуйте создать лимитную заявку позже,';
            }
        }

        return [
            'session_state' => [
                'text' => $text,
                'context' => [],
            ],
            'response' => [
                'text' => $text,
                'tts' => $text,
                'end_session' => false,
            ],
            'version' => '1.0',
        ];
    }

    /**
     * Сообщает пользователю детали заявки, которые удалось узнать
     * из запроса пользователя, затем предлагает подтвердить
     * или отменить заявку.
     */
    private function askConfirmation(array $event): array
    {
        $price1 = $event['request']['nlu']['intents']['limit.order.buy.stock']['slots']['price1']['value'] ?? 0.0;
        $currency1 = $event['request']['nlu']['intents']['limit.order.buy.stock']['slots']['currency1']['value'] ?? '';
        $price2 = $event['request']['nlu']['intents']['limit.order.buy.stock']['slots']['price2']['value'] ?? 0.0;
        $currency2 = $event['request']['nlu']['intents']['limit.order.buy.stock']['slots']['currency2']['value'] ?? '';
        $figi = $event['request']['nlu']['intents']['limit.order.buy.stock']['slots']['figi']['value'] ?? '';
        $lots = $event['request']['nlu']['intents']['limit.order.buy.stock']['slots']['requestedLots']['value'] ?? 0;

        $stockPrice = new StockPrice('RUB');
        try {
            $price = $stockPrice->concat($price1, $currency1, $price2, $currency2);
        } catch (InvalidPriceException $iv) {
            return [
                'session_state' => [
                    'text' => $iv->getMessage(), // @todo: do not use getMessage
                    'context' => [
                        'limit_order_buy_stock',
                    ],
                    'order_details' => [
                        'price' => $price,
                        'figi' => $figi,
                        'requestedLots' => $lots,
                    ],
                ],
                'response' => [
                    'text' => $iv->getMessage(),
                    'tts' => $iv->getMessage(),
                    'end_session' => false,
                ],
                'version' => '1.0',
            ];
        }

        $valid = $lots > 0;
        if ($valid) {
            $instrument = $this->client->getInstrumentByFigi($figi);
            // @todo: check minPriceIncrement
            $text = sprintf('лимитная заявка на покупку %s,', $instrument->getName());
            $text .= sprintf('тикер: %s,', $instrument->getTicker());
            $text .= sprintf('цена за акцию: %s,', Price::toText($price, [['рублей', 'рубль', 'рубля'], ['копеек', 'копейка', 'копейки']], 'RU'));
            $text .= sprintf('количество лотов: %s,', Num::toText($lots, 'RU'));
            // @todo: сколько акций в одном лоте
            $text .= sprintf('сумма заявки: %s плюс комиссия брокера,', Price::toText($price * $lots * $instrument->getLot(), [['рублей', 'рубль', 'рубля'], ['копеек', 'копейка', 'копейки']], 'RU'));
            $text .= 'для подтверждения заявки скажите да, для отмены скажите нет,';
            return [
                'session_state' => [
                    'text' => $text,
                    'context' => [
                        'limit_order_buy_stock',
                    ],
                    'order_details' => [
                        'price' => $price,
                        'figi' => $figi,
                        'requestedLots' => $lots,
                    ],
                ],
                'response' => [
                    'text' => $text,
                    'tts' => $text,
                    'end_session' => false,
                ],
                'version' => '1.0',
            ];
        } else {
            // price 120.5
            // currency копеек копеек
            // lot -2, 10.5
            // @todo: ???????????????????
            $text = '';
            return [
                'session_state' => [
                    'text' => $text,
                    'context' => [
                        'limit_order_buy_stock',
                    ],
                    'order_details' => [],
                ],
                'response' => [
                    'text' => $text,
                    'tts' => $text,
                    'end_session' => false,
                ],
                'version' => '1.0',
            ];
        }
    }

    private function rejected(): array
    {
        $text = 'заявка отменена';
        return [
            'session_state' => [
                'text' => $text,
                'context' => [],
                'order_details' => [],
            ],
            'response' => [
                'text' => $text,
                'tts' => $text,
                'end_session' => false,
            ],
            'version' => '1.0',
        ];
    }

    /**
     * Пользователь ответил что-то невнятное
     * Просим подтвердить заявку, либо отменить
     */
    private function hint(array $event): array
    {
        $text = 'чтобы создать заявку скажите подтверждаю или скажите отмена';
        $context = [];
        if (isset($event['state']['session']['context'])) {
            $context = $event['state']['session']['context'];
        }
        $details = [];
        if (isset($event['state']['session']['order_details'])) {
            $details = $event['state']['session']['order_details'];
        }
        return [
            'session_state' => [
                'text' => $text,
                'context' => $context,
                'order_details' => $details,
            ],
            'response' => [
                'text' => $text,
                'end_session' => false,
            ],
            'version' => '1.0',
        ];
        return [
            'session_state' => [
                'text' => $text,
                'context' => [],
                'order_details' => [],
            ],
            'response' => [
                'text' => $text,
                'tts' => $text,
                'end_session' => false,
            ],
            'version' => '1.0',
        ];
    }

    /**
     * Пользователь намеревается создать лимитную заявку
     */
    private function userPlacesOrder(array $event): bool
    {
        return isset($event['request']['nlu']['intents']['limit.order.buy.stock']);
    }

    /**
     * Пользователь ответил на предложение подтвердить или отменить лимитную заявку
     * @throws \Exception
     */
    private function userReplies(array $event): bool
    {
        $context = isset($event['state']['session']['context']) &&
            is_array($event['state']['session']['context']) &&
            in_array('limit_order_buy_stock', $event['state']['session']['context']);
        // $valid = isset($event['state']['session']['order_details']['figi']) &&
        //     isset($event['state']['session']['order_details']['requestedLots']) &&
        //     isset($event['state']['session']['order_details']['price']);
        // if (! $valid) {
        //     // @todo: залогируй session
        //     print_r($event['state']['session']);
        //     throw new \Exception('Невалидные данные в order_details.');
        // }
        return $context;
    }
}
