<?php

declare(strict_types=1);

namespace Oliver\Reply;

class ICanDo implements ReplyInterface
{
    public function __construct()
    {
    }

    public function handle(array $event): array
    {
        $do = isset($event['request']['nlu']['intents']['YANDEX.WHAT_CAN_YOU_DO']);
        if ($do) {
            $tts = 'я могу купить или продать акции на бирже по рыночной цене,';
            $tts .= 'например, чтобы купить акции,';
            $tts .= 'скажите купи 10 лотов нлмк,';
            $tts .= 'а чтобы продать,';
            $tts .= 'скажите продай 2 лота нлмк,';
            $tts .= 'я так же могу расказать об акциях на вашем счёте,';
            $tts .= 'скажите мои акции,';
            $text = $tts;
            return [
                'session_state' => [
                    'text' => $text,
                    'tts' => $tts,
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
