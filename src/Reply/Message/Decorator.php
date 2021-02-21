<?php

declare(strict_types=1);

namespace Oliver\Reply\Message;

/**
 * Base class for Confirm messages.
 *
 * Abstract component in terms of decorator pattern.
 */
abstract class Decorator extends Message
{
    protected Message $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    abstract public function text(): string;
}
