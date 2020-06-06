<?php

declare(strict_types=1);

namespace Oliver\Reply;

class PrivateSkill implements ReplyInterface
{
    private $session_user_id;

    public function __construct(string $session_user_id)
    {
        $this->session_user_id = $session_user_id;
    }

    public function handle(array $event): array
    {
        $allowed = isset($event['session']['user']['user_id']) &&
                   $event['session']['user']['user_id'] === $this->session_user_id;
        if (! $allowed) {
            return [
                'response' => [
                    'text' => 'Это приватный навык. У вас нет доступа. Завершаю сессию.',
                    'end_session' => true,
                ],
                'version' => '1.0',
            ];
        }
        return [];
    }
}