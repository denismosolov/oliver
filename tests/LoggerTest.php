<?php

declare(strict_types=1);

namespace Oliver;

use PHPUnit\Framework\TestCase;
use Oliver\Logger;

final class LoggerTest extends TestCase
{
    public function testCloudOutput(): void
    {
        $this->expectOutputString("error id message\n");
        $logger = new Logger('id');
        $logger->error('message');
    }

    public function testNoOutput(): void
    {
        $this->expectOutputString('');
        $logger = new Logger('');
        $logger->error('message');
    }
}
