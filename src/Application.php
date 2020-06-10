<?php

declare(strict_types=1);

namespace Oliver;

use Oliver\Reply\PrivateSkill;
use Oliver\Reply\Stocks;
use jamesRUS52\TinkoffInvest\TIClient;
use Oliver\Reply\Introduction;
use Oliver\Reply\Repeat;

class Application
{
    /**
     * request data
     * @see https://yandex.ru/dev/dialogs/alice/doc/protocol-docpage/#request
     */
    private array $event;

    /**
     * $_ENV
     */
    private array $env = [];

    /**
     * Tinkoff Open API Client
     */
    private TIClient $client;

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

    public function setEnv(array $env): void
    {
        $this->env = $env;
    }

    /**
     * @param TIClient $client
     * @see https://github.com/jamesRUS52/tinkoff-invest
     */
    public function setClient(TIClient $client): void
    {
        $this->client = $client;
    }

    /**
     * Allowed user id
     */
    public function setUserId(string $id): void
    {
        $this->session_user_id = $id;
    }

    /**
     * @see https://yandex.ru/dev/dialogs/alice/doc/protocol-docpage/#response
     */
    public function run(): array
    {
        try {
            $replies = [
                new PrivateSkill($this->session_user_id),
                new Introduction(),
                new Repeat(),
                new Stocks($this->client),
            ];
            foreach ($replies as $reply) {
                $response = $reply->handle($this->event);
                if ($response) {
                    return $response;
                }
            }
        } catch (\Exception $e) {
            // @todo: обработка разных искочений, по некоторым не надо завершать сессию
            // запись в лог
            print $e->getMessage();
            print $e->getTraceAsString();
            // @todo: покрой тестами
            return [
                'response' => [
                    'text' => 'произошла ошибка, я уведомил разработчиков, повторите действие позже.',
                    'end_session' => true,
                ],
                'version' => '1.0',
            ];
        }
        // @todo: неизвестная команда, справка?
        return [
            'response' => [
                'text' => 'всё хорошо',
                'end_session' => true,
            ],
            'version' => '1.0',
        ];
    }
}
