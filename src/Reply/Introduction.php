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
        $text = 'Здравствуйте! Чтобы получить информацию об акциях на брокерском счёте, скажите «мои акции».';
        if ($new && $empty) {
            return [
                'session_state' => [
                    'text' => $text,
                    'context' => [],
                ],
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
