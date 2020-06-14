<?php

declare(strict_types=1);

namespace Oliver;

use PHPUnit\Framework\TestCase;
use Oliver\Declension;

final class DeclensionTest extends TestCase
{
    public function test1(): void
    {
        $instance = new Declension();
        $this->assertEquals(
            'лот',
            $instance->default(1, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лот',
            $instance->default(21, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лот',
            $instance->default(31, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лота',
            $instance->default(2, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лота',
            $instance->default(3, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лота',
            $instance->default(4, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лота',
            $instance->default(22, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лота',
            $instance->default(23, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лота',
            $instance->default(24, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лотов',
            $instance->default(5, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лотов',
            $instance->default(6, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лотов',
            $instance->default(7, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лотов',
            $instance->default(8, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лотов',
            $instance->default(9, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лотов',
            $instance->default(10, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лотов',
            $instance->default(11, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лотов',
            $instance->default(12, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лотов',
            $instance->default(13, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лотов',
            $instance->default(14, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лотов',
            $instance->default(15, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лотов',
            $instance->default(16, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лотов',
            $instance->default(17, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лотов',
            $instance->default(18, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лотов',
            $instance->default(19, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лотов',
            $instance->default(20, 'лот', 'лота', 'лотов')
        );
        $this->assertEquals(
            'лотов',
            $instance->default(25, 'лот', 'лота', 'лотов')
        );
    }
}
