<?php

declare(strict_types=1);

namespace Oliver;

use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    /**
     * @see https://cloud.yandex.ru/docs/functions/lang/php/context
     */
    private string $id = '';

    public function __construct(string $id = '')
    {
        $this->id = $id;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed   $level
     * @param string  $message
     * @param mixed[] $context
     *
     * @return void
     *
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function log($level, $message, array $context = [])
    {
        if ($this->id) {
            // @todo: print level
            printf('%s: %s', $this->id, $message);
            $isException = isset($context['exception']) && is_a($context['exception'], '\Exception');
            if ($isException) {
                printf('%s: %s', $this->id, $context['exception']->getMessage());
                printf('%s: %s', $this->id, $context['exception']->getTraceAsString());
            }
        }
    }
}
