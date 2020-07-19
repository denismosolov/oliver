<?php

declare(strict_types=1);

namespace Oliver;

use jamesRUS52\TinkoffInvest\TIClient;
use Oliver\Reply\ReplyInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class Application implements LoggerAwareInterface
{
    /**
     * request data
     * @see https://yandex.ru/dev/dialogs/alice/doc/protocol-docpage/#request
     */
    private array $event;

    /**
     * Цепочка обработчиков запросов
     */
    private array $replies = [];

    private LoggerInterface $logger;

    public function __construct()
    {
    }

    /**
     * @param array $event
     * @see https://yandex.ru/dev/dialogs/alice/doc/protocol-docpage/#request
     */
    public function setEvent(array $event): void
    {
        $this->event = $event;
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Добавь обработчик в цепочку
     * @todo: reply не очень удачное имя для обработчика
     */
    public function add(ReplyInterface ...$replies)
    {
        foreach ($replies as $reply) {
            $this->replies[] = $reply;
        }
    }

    /**
     * @see https://yandex.ru/dev/dialogs/alice/doc/protocol-docpage/#response
     */
    public function run(): array
    {
        try {
            foreach ($this->replies as $reply) {
                $response = $reply->handle($this->event);
                if ($response) {
                    return $response;
                }
            }
        } catch (\Exception $e) {
            // @todo: обработка исключений, по некоторым не надо завершать сессию
            $this->logger->debug(
                'Исключительная ситуация',
                ['exception' => $e]
            );
            // @todo: покрой тестами
            return [
                'response' => [
                    'text' => 'произошла ошибка, я уведомил разработчиков, повторите действие позже.',
                    'end_session' => true,
                ],
                'version' => '1.0',
            ];
        }
        // @todo: create a Default reply and add the the end of the $replies
        $singlePassMode = $this->event['session']['new'] === true; // однопроходный режим
        // @todo: неизвестная команда, справка?
        $text = 'пожалуйста, повторите ещё раз, ';
        $hint = 'если не знаете, что делать, то скажите: купи одну акцию яндекс';
        if (! $singlePassMode) {
            $text .= $hint;
        }
        if (! $this->event['request']['original_utterance'] !== 'ping') {
            $this->logger->debug(
                sprintf(
                    "command: %s",
                    $this->event['request']['command']
                )
            );
        }
        return [
            'response' => [
                'text' => $text,
                'end_session' => $singlePassMode,
            ],
            'version' => '1.0',
        ];
    }
}
