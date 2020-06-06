<?php

declare(strict_types=1);

namespace Oliver;

use Oliver\Reply\PrivateSkill;

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
     * @see https://yandex.ru/dev/dialogs/alice/doc/protocol-docpage/#response
     */
    public function run(): array
    {
        $session_user_id = $this->env['SESSION_USER_ID'] ?? '';
        $replies = [
            new PrivateSkill($session_user_id),
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
