<?php

declare(strict_types=1);

namespace Oliver\Reply\Message;

/**
 * Base class for Confirm messages.
 */
abstract class Message
{
    /**
     * Return response' text
     */
    abstract public function text(): string;

    /**
     * Return response' tts
     *
     * Note, text and tts are the same for quickstart. This can be overwritten.
     */
    public function tts(): string
    {
        return $this->text();
    }
}
