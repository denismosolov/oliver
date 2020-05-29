<?php

declare(strict_types=1);

namespace Oliver;

class Application
{
    /**
     * request data
     * @see https://yandex.ru/dev/dialogs/alice/doc/protocol-docpage/#request
     */
    private array $event;

    /**
     * context
     * @see https://cloud.yandex.ru/docs/functions/lang/php/context
     */
    private object $context;

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

    /**
     * @param object $context
     * @see https://cloud.yandex.ru/docs/functions/lang/php/context
     */
    public function setContext(object $context): void
    {
        $this->context = $context;
    }

    /**
     * @see https://yandex.ru/dev/dialogs/alice/doc/protocol-docpage/#response
     */
    public function run(): array
    {
        $response  = [
            'response' => [],
            'version' => '1.0',
        ];
        $allowed = isset($this->event['session']['user']['user_id']) &&
            $this->event['session']['user']['user_id'] === $_ENV['SESSION_USER_ID'];
        if ($allowed) {
            $response['response'] = [
                'text' => 'всё хорошо',
                'end_session' => true,
            ];
        } else {
            $response['response'] = [
                'text' => 'Это приватный навык. У вас нет доступа. Завершаю сессию.',
                'end_session' => true,
            ];
        }
        return $response;
    }
}