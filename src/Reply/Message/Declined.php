<?php

declare(strict_types=1);

namespace Oliver\Reply\Message;

/**
 * Operation is declined message, can be extended with decorators.
 */
final class Declined extends Message
{
    public function text(): string
    {
        return 'операция отклонена';
    }
}
