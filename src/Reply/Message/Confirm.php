<?php

declare(strict_types=1);

namespace Oliver\Reply\Message;

/**
 * Add a call to action.
 */
final class Confirm extends Decorator
{
    public function text(): string
    {
        return $this->message->text() . ' для подтверждения скажите да, для отмены скажите нет';
    }
}
