<?php

declare(strict_types=1);

namespace Oliver\Reply;

interface ReplyInterface
{
    public function handle(array $event): array;
}
