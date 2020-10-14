<?php

declare(strict_types=1);

namespace Oliver\Reply;

use jamesRUS52\TinkoffInvest\TIClient;
use jamesRUS52\TinkoffInvest\TIInstrument;
use jamesRUS52\TinkoffInvest\TIOperationEnum;
use jamesRUS52\TinkoffInvest\TIException;
use jamesRUS52\TinkoffInvest\TIOrder;
use Psr\Log\LoggerInterface;
use Oliver\Reply\Message\Amount;
use Oliver\Reply\Message\Declined;
use Oliver\Reply\Message\Confirm;
use Oliver\Reply\Message\Instrument;
use Oliver\Reply\Message\Order as ConfirmOrder;
use Oliver\Reply\Message\Price;
use Oliver\Reply\Message\Ticker;
use Oliver\Reply\Message\WannaBuy;

class Order implements ReplyInterface
{
   /**
     * Tinkoff Invest API Client
     */
    private $client;

    // @todo: better dependency injection
    private $logger;

    public const OPERATION_BUY = 'buy'; // @todo: use TIOperationEnum::BUY
    public const OPERATION_SELL = 'sell';
    public const UNIT_LOT = 'lot';

    public function __construct(TIClient $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    public function handle(array $event): array
    {
        $intents = $event['request']['nlu']['intents'] ?? [];
        $context = $event['state']['session']['context'] ?? [];
        if (
            isset($intents['order']) ||
            isset($context['order']) && ! isset($intents['YANDEX.REJECT']) && ! isset($intents['YANDEX.CONFIRM'])
        ) {
            // @todo: choose a better design pattern
            // ex. Reply/Order/(Buy|Sell)(Confirm...|Exec|Cancel) + validation
            // buy usd

            // @todo: class, read from intents or read from context
            $slots = $intents['order']['slots']; // shortcut
            $operation = $slots['operation']['value'] ?? '';
            $unit = $slots['unit']['value'] ?? '';
            $figi = $slots['figi']['value'] ?? '';
            $amount = $slots['amount']['value'] ?? 0;
            $buyLot = $operation === self::OPERATION_BUY && $unit === self::UNIT_LOT && $figi;
            if ($buyLot) {
                // @todo: validation
                $instrument = $this->client->getInstrumentByFigi($figi);
                $message = new Confirm(
                    new Amount(
                        new Ticker(
                            new Price(
                                new Instrument(
                                    new ConfirmOrder($operation),
                                    $instrument->getName()
                                ),
                                $instrument->getName()
                            ),
                            $instrument->getTicker()
                        ),
                        $amount,
                        $unit
                    )
                );
                // $orderMessage = new ConfirmOrder($operation);
                // $instrumentMessage = new Instrument($orderMessage, $instrument->getName());
                // $priceMessage = new Price($instrumentMessage);
                // $tickerMessage = new Ticker($priceMessage, $instrument->getTicker());
                // $amountMessage = new Amount($tickerMessage, $amount, $unit);
                // $message = new Confirm($amountMessage);
                return [
                    'session_state' => [
                        'text' => $message->text(),
                        'tts' => $message->tts(),
                        'context' => [
                            'order' => [
                                'operation' => $operation,
                                'figi' => $figi,
                                'type' => $instrument->getType(),
                                'amount' => $amount,
                                'unit' => $unit,
                                'ticker' => $instrument->getTicker(),
                                'name' => $instrument->getName(),
                            ],
                        ],
                    ],
                    'response' => [
                        'text' => $message->text(),
                        'tts' => $message->tts(),
                        'end_session' => false,
                    ],
                    'version' => '1.0',
                ];
            } else {
                // @todo: sell
            }
        } elseif (isset($intents['YANDEX.CONFIRM']) && isset($context['order'])) {
            $operation = $context['order']['operation'] ?? '';
            $unit = $context['order']['unit'] ?? '';
            $figi = $context['order']['figi'] ?? '';
            $amount = $context['order']['amount'] ?? 0;
            // @todo: validation
            $buyLot = $operation === self::OPERATION_BUY && $unit === self::UNIT_LOT && $figi;
            if ($buyLot) {
                try {
                    $order = $this->client->sendOrder(
                        $figi,
                        $amount,
                        TIOperationEnum::BUY
                    );
                    $text = $this->checkStatus($order); // @todo: refactor, move to a separate class
                } catch (TIException $te) {
                    $text = $this->checkException($te);
                }
                $newContext = $context;
                unset($newContext['order']);
                return [
                    'session_state' => [
                        'text' => $text,
                        'context' => $newContext,
                    ],
                    'response' => [
                        'text' => $text,
                        'tts' => $text,
                        'end_session' => false,
                    ],
                    'version' => '1.0',
                ];
            } else {
                // invalid data
                return [];
            }
        } elseif (isset($intents['YANDEX.REJECT']) && isset($context['order'])) {
            $message = new WannaBuy(
                new Declined(),
                $context['order']['name']
            );
            return [
                'session_state' => [
                    'text' => $message->text(),
                    'tts' => $message->tts(),
                    'context' => [],
                    'order_details' => [],
                ],
                'response' => [
                    'text' => $message->text(),
                    'tts' => $message->tts(),
                    'end_session' => false,
                ],
                'version' => '1.0',
            ];
        } else {
            return [];
        }
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
        $text = 'заявка на покупку отклонена системой,';
        // Недостаточно активов для сделки [OrderNotAvailable]
        if (preg_match('/\[OrderNotAvailable\]/', $te->getMessage())) {
            $text = preg_replace('/\[OrderNotAvailable\]/', '', $te->getMessage());
            if (is_null($text)) {
                // @todo: ????
                $text = 'неизвестная ошибка,';
            }
            if (preg_match('/Недостаточно активов для сделки/i', $text)) {
                // @todo: test case
                $text = 'недостаточно активов для сделки, ';
                $text .= 'пополните счёт и попробуйте снова, ';
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
        } elseif (preg_match('/\[VALIDATION_ERROR\]/', $te->getMessage())) {
            if (preg_match('/has invalid scale/', $te->getMessage())) {
                $text .= 'недопустимый шаг цены, узнайте минимальный шаг цены для этого инструмента на бирже,';
            }
        } else {
            $text = 'ошибка при взаимодействии с биржей, попробуйте позже,';
        }
        return $text;
    }
}
