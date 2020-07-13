<?php

declare(strict_types=1);

namespace Oliver\Reply;

class Ping implements ReplyInterface
{
    public function __construct()
    {
    }

    public function handle(array $event): array
    {
        $ping = $event['request']['original_utterance'] === 'ping';
        if ($ping) {
            return [
                'response' => [
                    'text' => '',
                    'end_session' => false,
                ],
                'version' => '1.0',
            ];
        }
        return [];
    }
}
