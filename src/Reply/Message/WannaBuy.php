<?php

declare(strict_types=1);

namespace Oliver\Reply\Message;

/**
 * Add a hin on how to buy the instrument later
 * @todo tweak name for better sounding
 */
final class WannaBuy extends Instrument
{

    public function text(): string
    {
        $name = $this->getName() ?? 'и название инструемнта';
        return $this->message->text() .
            ' когда захотите купить инструмент, скажите: купи два лота ' .
            $name;
    }
}
