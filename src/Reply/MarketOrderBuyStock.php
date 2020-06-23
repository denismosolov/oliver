<?php

declare(strict_types=1);

namespace Oliver\Reply;

use jamesRUS52\TinkoffInvest\TIClient;
use jamesRUS52\TinkoffInvest\TIInstrument;
use jamesRUS52\TinkoffInvest\TIOperationEnum;
use jamesRUS52\TinkoffInvest\TIException;
use jamesRUS52\TinkoffInvest\TIOrder;

class MarketOrderBuyStock implements ReplyInterface
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
        if ($this->order($event)) {
            return $this->askForConfirmation($event);
        } elseif ($this->confirm($event)) {
            return $this->createMarketOrder($event);
        } elseif ($this->reject($event)) {
            return $this->hint($event);
        } elseif ($this->cannotRecognizeConfirmation($event)) {
            return $this->askForConfirmation($event);
        } else {
            return [];
        }
    }

    private function createMarketOrder(array $event): array
    {
        $text = '';
        $figi = $event['state']['session']['order_details']['figi'] ?? '';
        $amount = $event['state']['session']['order_details']['amount'] ?? 0;
        $amount = intval($amount);
        // @todo: validation
        try {
            $order = $this->client->sendOrder(
                $figi,
                $amount,
                TIOperationEnum::BUY
            );
            $text = $this->checkStatus($order);
        } catch (TIException $te) {
            $text = $this->checkException($te);
        }
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
     * @todo: move to a separate class, can be re-used for limit orders
     */
    private function checkStatus(TIOrder $order): string
    {
        switch ($order->getStatus()) {
            // [ New, PartiallyFill, Fill, Cancelled, Replaced, PendingCancel, Rejected, PendingReplace, PendingNew ]
            case 'Fill':
                return 'заявка исполнена,'; // @fixme: $order->getPrice() всегда возвращает null
                // @todo: добавь информацию о комиссии брокера
                // @todo: добавь информацию о цене
            case 'New':
                return 'заявка на покупку создана,';
            case 'PendingNew':
                return 'заявка на покупку отправлена,';
            case 'Rejected':
                $text = 'заявка на покупку отклонена системой,';
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
                        $text .= 'неизвестная ошибка,';
                    }
                }
                // @todo: Specified security is not found [...]
                return $text;
            default:
                // @todo: add test case
                print $order->getStatus() . "\n";
                return 'произошло что-то непонятное, проверьте свои заявки и акции,';
        }
    }

    /**
     * @todo: move to a separate class, can be re-used for limit orders
     */
    private function checkException(TIException $te): string
    {
        print $te->getMessage() . "\n";
        $text = 'заявка на покупку отклонена системой,';
        // Недостаточно активов для сделки [OrderNotAvailable]
        if (preg_match('/\[OrderNotAvailable\]/', $te->getMessage())) {
            $text = preg_replace('/\[OrderNotAvailable\]/', '', $te->getMessage());
            if (is_null($text)) {
                // @todo: ????
                $text = 'неизвестная ошибка,';
            }
        } elseif (preg_match('/\[VALIDATION_ERROR\]/', $te->getMessage())) {
            if (preg_match('/has invalid scale/', $te->getMessage())) {
                $text .= 'недопустимый шаг цены, узнайте минимальный шаг цены для этого инструмента на бирже,';
            }
        } else {
            $text = 'ошибка при взаимодействии с биржей, попробуйте создать лимитную заявку позже,';
        }
        return $text;
    }

    private function askForConfirmation(array $event): array
    {
        $amount = $event['request']['nlu']['intents']['market.order']['slots']['amount']['value'] ??
            $event['state']['session']['order_details']['amount'] ??
            0;
        $amount = intval($amount);
        $figi = $event['request']['nlu']['intents']['market.order']['slots']['figi']['value'] ??
            $event['state']['session']['order_details']['figi'] ??
            0;
        $unit = $event['request']['nlu']['intents']['market.order']['slots']['unit']['value'] ??
            $event['state']['session']['order_details']['unit'] ??
            0;
        // validation
        // @todo: move to a separate method?
        if ($amount <= 0) {
            // @todo: add test case
            return $this->replyNegativeValue();
        }
        if ($figi == '') {
            // @todo: add test case
            return $this->replyEmptyFigi();
        }
        $instrument = $this->client->getInstrumentByFigi($figi);
        if ($unit !== 'lot') {
            // @todo: add test case
            return $this->replyLotAllowed($instrument);
        }

        $text = sprintf('заявка на покупку %s по рыночной цене,', $instrument->getName());
        $text .= sprintf('тикер: %s,', $instrument->getTicker());
        $text .= sprintf('количество лотов: %d,', $amount);
        $text .= 'для подтверждения скажите подтверждаю, для отмены скажите нет.';
        return [
            'session_state' => [
                'text' => $text,
                'context' => [
                    'market_order_buy_stock',
                ],
                'order_details' => [
                    'figi' => $figi,
                    'amount' => $amount,
                    'unit' => $unit,
                    'ticker' => $instrument->getTicker(), // not necessary
                    'name' => $instrument->getName(), // not necessary
                ],
            ],
            'response' => [
                'text' => $text,
                'tts' => $text,
                'end_session' => false,
            ],
            'version' => '1.0',
        ];
    }

    private function hint(array $event): array
    {
        $name = $event['state']['session']['order_details']['name'] ?? 'и название компании';
        $text = 'операция отменена, когда захотить купи акции по рыночной цене,';
        $text .= sprintf('скажите: купи два лота %s,', $name);
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

    private function order(array $event): bool
    {
        // @todo: validate slots
        $operation = $event['request']['nlu']['intents']['market.order']['slots']['operation']['value'] ?? '';
        return $operation === 'buy';
    }

    private function confirm(array $event): bool
    {
        $confirm = isset($event['request']['nlu']['intents']['YANDEX.CONFIRM']) &&
            $event['request']['nlu']['intents']['YANDEX.CONFIRM'];
        $context = isset($event['state']['session']['context']) &&
            is_array($event['state']['session']['context']) &&
            in_array('market_order_buy_stock', $event['state']['session']['context']);
        return $confirm && $context;
    }

    private function reject(array $event): bool
    {
        $reject = isset($event['request']['nlu']['intents']['YANDEX.REJECT']) &&
            $event['request']['nlu']['intents']['YANDEX.REJECT'];
        $context = isset($event['state']['session']['context']) &&
            is_array($event['state']['session']['context']) &&
            in_array('market_order_buy_stock', $event['state']['session']['context']);
        return $reject && $context;
    }

    private function cannotRecognizeConfirmation(array $event): bool
    {
        $confirm = isset($event['request']['nlu']['intents']['YANDEX.CONFIRM']) &&
            $event['request']['nlu']['intents']['YANDEX.CONFIRM'];
        $reject = isset($event['request']['nlu']['intents']['YANDEX.REJECT']) &&
            $event['request']['nlu']['intents']['YANDEX.REJECT'];
        $context = isset($event['state']['session']['context']) &&
            is_array($event['state']['session']['context']) &&
            in_array('market_order_buy_stock', $event['state']['session']['context']);
        return ! $confirm && ! $reject && $context;
    }
    
    private function replyEmptyFigi(): array
    {
        $text = 'не могу распознать тикер, повторите, пожалуйста, ещё раз,';
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

    private function replyNegativeValue(): array
    {
        $text = 'не могу распознать количество, понимаю только целые числа больше нуля,';
        $text .= 'повторите команду ещё раз,';
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

    private function replyLotAllowed(TIInstrument $instrument): array
    {
        $text = 'инструмент продаётся лотами,';
        $text .= sprintf('количество акций в одном лоте: %d,', $instrument->getLot());
        $text .= 'повторите команду ещё раз,';
        $text .= 'вместо акций используйте лоты,';
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
}
