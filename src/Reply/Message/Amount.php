<?php

declare(strict_types=1);

namespace Oliver\Reply\Message;

/**
 * Add ammount.
 * @todo tweak digits for better sounding
 */
final class Amount extends Decorator
{
    public const SHARE = 'share';
    public const LOT = 'lot';

    private int $amount;
    private string $unit;

    public function __construct(Message $message, int $amount, string $unit)
    {
        $unit = strtolower($unit);
        if (in_array($unit, [self::SHARE, self::LOT])) {
            $this->unit = $unit;
        } else {
            throw new ConfirmException(); // @todo: add unique code
        }
        $this->amount = $amount;
        parent::__construct($message);
    }

    public function text(): string
    {
        if ($this->unit === self::SHARE) {
            return $this->message->text() . ' количество акций: ' . $this->amount;
        }
        if ($this->unit === self::LOT) {
            return $this->message->text() . ' количество лотов: ' . $this->amount;
        }
        throw new ConfirmException();
    }
}
