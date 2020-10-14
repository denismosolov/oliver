<?php

declare(strict_types=1);

namespace Oliver\Reply\Message;

/**
 * Add instrument ticker.
 * @todo tweak tickers for better sounding
 */
final class Ticker extends Decorator
{
    private string $ticker;

    public function __construct(Message $message, string $ticker)
    {
        $this->ticker = $ticker;
        parent::__construct($message);
    }
    public function text(): string
    {
        return $this->message->text() . ' ' . $this->ticker;
    }
}
