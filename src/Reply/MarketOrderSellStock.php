<?php

declare(strict_types=1);

namespace Oliver\Reply;

use jamesRUS52\TinkoffInvest\TIClient;
use jamesRUS52\TinkoffInvest\TIInstrument;
use jamesRUS52\TinkoffInvest\TIOperationEnum;
use jamesRUS52\TinkoffInvest\TIException;
use jamesRUS52\TinkoffInvest\TIOrder;
use Psr\Log\LoggerInterface;

// @todo: refactor,create abstract class with common methods
// MarketOrderSellStock and MarketOrderBuyStock
class MarketOrderSellStock implements ReplyInterface
{
   /**
     * Tinkoff Invest API Client
     */
    private $client;

    // @todo: better dependency injection
    private $logger;

    public function __construct(TIClient $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
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
                TIOperationEnum::SELL
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
                return 'заявка на продажу создана,';
            case 'PendingNew':
                return 'заявка на продажу отправлена,';
            case 'Rejected':
                $text = 'заявка на продажу отклонена системой,';
                $this->logger->debug($order->getRejectReason());
                $this->logger->debug($order->getMessage());
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
                return 'произошло что-то непонятное, проверьте свои заявки и акции,';
        }
    }

    /**
     * @todo: move to a separate class, can be re-used for limit orders
     */
    private function checkException(TIException $te): string
    {
        $this->logger->debug(
            'Исключительная ситуация',
            ['exception' => $te]
        );
        $text = 'заявка на продажу отклонена системой,';
        if (preg_match('/\[OrderNotAvailable\]/', $te->getMessage())) {
            $text = preg_replace('/\[OrderNotAvailable\]/', '', $te->getMessage());
            if (is_null($text)) {
                // @todo: ????
                $text = 'неизвестная ошибка,';
            }
        // Недостаточно заявок в стакане для тикера TCS [OrderBookException]
        } elseif (preg_match('/\[OrderBookException\]/', $te->getMessage())) {
            $text = preg_replace('/\[OrderBookException\]/', '', $te->getMessage());
            if (is_null($text)) {
                // @todo: ????
                $text = 'неизвестная ошибка,';
            }
            if (preg_match('/Недостаточно заявок в стакане для тикера/i', $text)) {
                // @todo: test case
                $text = 'недостаточно заявок в стакане, ';
                $text .= 'похоже биржа закрыта, попробуйте позже ';
            }
        } else {
            $text = 'ошибка при взаимодействии с биржей, попробуйте позже,';
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
        // если 1 лот = 1 акция, то всё хорошо, лот и акция взамозаменяемы
        // но если в 1 лоте 10 акций, а пользователь хочет только 5, то отказываем
        if (! in_array($unit, ['lot', 'share'])) {
            // @todo: add test case
            return $this->replyLotAllowed($instrument);
        }
        if ($unit === 'share' && $instrument->getLot() !== 1) {
            // @todo: add test case
            return $this->replyLotAllowed($instrument);
        }

        $text = sprintf('заявка на продажу %s по рыночной цене,', $instrument->getName());
        $text .= sprintf('тикер: %s,', $instrument->getTicker());
        if ($unit === 'share') {
            $text .= sprintf('количество акций: %d,', $amount);
        } elseif ($unit === 'lot') {
            $text .= sprintf('количество лотов: %d,', $amount);
        } else {
            // @todo: test case???
            throw new \Exception('Неизвестная единица измерения: ' . $unit);
        }
        $text .= 'для подтверждения скажите подтверждаю, для отмены скажите нет.';
        return [
            'session_state' => [
                'text' => $text,
                'context' => [
                    'market_order_sell_stock',
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
        $text = 'операция отменена, когда захотите продать %s по рыночной цене,';
        $text .= sprintf('скажите: продай два лота %s,', $name, $name);
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
        return $operation === 'sell';
    }

    private function confirm(array $event): bool
    {
        $confirm = isset($event['request']['nlu']['intents']['YANDEX.CONFIRM']) &&
            $event['request']['nlu']['intents']['YANDEX.CONFIRM'];
        $context = isset($event['state']['session']['context']) &&
            is_array($event['state']['session']['context']) &&
            in_array('market_order_sell_stock', $event['state']['session']['context']);
        return $confirm && $context;
    }

    private function reject(array $event): bool
    {
        $reject = isset($event['request']['nlu']['intents']['YANDEX.REJECT']) &&
            $event['request']['nlu']['intents']['YANDEX.REJECT'];
        $context = isset($event['state']['session']['context']) &&
            is_array($event['state']['session']['context']) &&
            in_array('market_order_sell_stock', $event['state']['session']['context']);
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
            in_array('market_order_sell_stock', $event['state']['session']['context']);
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
