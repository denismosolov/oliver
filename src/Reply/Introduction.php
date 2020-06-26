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
        $text = 'здравствуйте, я голосовой помощник вашего брокера, могу купить ценные бумаги на бирже, ';
        $text .= 'если захотите узнать обо всём, что я умею, то в любое время спросите «что ты умеешь?».';
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
