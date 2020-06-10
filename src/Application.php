<?php

declare(strict_types=1);

namespace Oliver;

use Oliver\Reply\PrivateSkill;
use Oliver\Reply\Balance;
use jamesRUS52\TinkoffInvest\TIClient;
use Oliver\Reply\Introduction;

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
        $replies = [
            new PrivateSkill($this->session_user_id),
            new Introduction(),
            new Balance($this->client),
        ];
        foreach ($replies as $reply) {
            $response = $reply->handle($this->event);
            if ($response) {
                return $response;
            }
        }
        return [
            'response' => [
                'text' => 'всё хорошо',
                'end_session' => true,
            ],
            'version' => '1.0',
        ];
    }
}
