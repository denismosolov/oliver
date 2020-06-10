<?php

declare(strict_types=1);

namespace Oliver\Reply;

class Introduction implements ReplyInterface
{
    public function __construct()
    {
    }

    public function handle(array $event): array
    {
        $new = isset($event['session']['new']);
        $empty = isset($event['request']['command']) && $event['request']['command'] === '';
        $text = 'Здравствуйте! Чтобы получить информацию о торгах по тикерам вашего брокерского счёта, скажите баланс.';
        if ($new && $empty) {
            return [
                'response' => [
                    'text' => $text,
                    'end_session' => false,
                ],
                'version' => '1.0',
            ];
        }
        return [];
    }
}
