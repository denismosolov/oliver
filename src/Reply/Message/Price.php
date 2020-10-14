<?php

declare(strict_types=1);

namespace Oliver\Reply\Message;

/**
 * Add price.
 * @todo add limit orders price orders
 */
final class Price extends Decorator
{
    public function text(): string
    {
        return $this->message->text() . ' по рыночной цене';
    }
}
