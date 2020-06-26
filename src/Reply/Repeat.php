<?php

declare(strict_types=1);

namespace Oliver\Reply;

class Repeat implements ReplyInterface
{
    public function __construct()
    {
    }

    public function handle(array $event): array
    {
        if (isset($event['request']['nlu']['intents']['YANDEX.REPEAT'])) {
            if (isset($event['state']['session']['text'])) {
                $text = $event['state']['session']['text'];
                $context = [];
                if (isset($event['state']['session']['context'])) {
                    $context = $event['state']['session']['context'];
                }
                $details = [];
                if (isset($event['state']['session']['order_details'])) {
                    $details = $event['state']['session']['order_details'];
                }
                return [
                    'session_state' => [
                        'text' => $text,
                        'context' => $context,
                        'order_details' => $details,
                    ],
                    'response' => [
                        'text' => $text,
                        'end_session' => false,
                    ],
                    'version' => '1.0',
                ];
            }
        }
        return [];
    }
}
