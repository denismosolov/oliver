<?php

declare(strict_types=1);

namespace Oliver\Reply\Message;

/**
 * Add instrument name.
 *
 * */
final class Instrument extends Decorator
{
    private string $name;

    public function __construct(Message $message, string $name)
    {
        $this->name = $name;
        parent::__construct($message);
    }

    // @todo tweak names for better sounding if necessary
    protected function getName()
    {
        return $this->name;
    }

    public function text(): string
    {
        return $this->message->text() . ' ' . $this->getName();
    }
}
