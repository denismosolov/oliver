<?php

declare(strict_types=1);

namespace Oliver\Reply\Message;

/**
 * A new order operation message, can be extended with decorators.
 */
final class Order extends Message
{
    public const BUY = 'buy';
    public const SELL = 'sell';

    private string $operation;

    /**
     * @throws ConfirmException
     */
    public function __construct(string $operation)
    {
        $operation = strtolower($operation);
        if (in_array($operation, [self::BUY, self::SELL])) {
            $this->operation = $operation;
        } else {
            throw new ConfirmException();
        }
    }

    /**
     * @throws ConfirmException
     */
    public function text(): string
    {
        if ($this->operation === self::BUY) {
            return 'Заявка на покупку';
        }
        if ($this->operation === self::SELL) {
            return 'Заявка на продажу';
        }
        throw new ConfirmException();
    }
}
