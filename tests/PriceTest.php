<?php

declare(strict_types=1);

namespace Oliver;

use PHPUnit\Framework\TestCase;
use Oliver\Price;

final class PriceTest extends TestCase
{
    public function testInvalidCurrency(): void
    {
        $this->expectException(InvalidPriceException::class);
        $this->expectExceptionMessage(
            'неправильная валюта, используйте рубли и копейки, например сто рублей десять копеек.'
        );
        $instance = new Price('RUB');
        $instance->concat(1, 'акция');
    }
    
    public function testInvalidPriceException2(): void
    {
        $this->expectException(InvalidPriceException::class);
        $this->expectExceptionMessage(
            'не понимаю цену, не доложно быть больше девяносто девяти копеек, например, сто рублей десять копеек.'
        );
        $instance = new Price('RUB');
        $instance->concat(120.0, 'рублей', 120.0, 'копеек');
    }

    public function testCurrencyOrder(): void
    {
        $this->expectException(InvalidPriceException::class);
        $this->expectExceptionMessage(
            'не понимаю цену, сначала рубли затем копейки, например, сто рублей десять копеек.'
        );
        $instance = new Price('RUB');
        $instance->concat(50.0, 'копеек', 120.0, 'рублей');
    }

    public function testCurrency1(): void
    {
        $instance = new Price('RUB');
        $this->assertEquals(
            120.0,
            $instance->concat(120.0, 'рубль')
        );
        $this->assertEquals(
            0.01,
            $instance->concat(1.0, 'копейка')
        );
        $this->assertEquals(
            0.001,
            $instance->concat(0.1, 'копейка')
        );
    }

    public function testCurrency2(): void
    {
        $instance = new Price('RUB');
        $this->assertEquals(
            120.5,
            $instance->concat(120.0, 'рублей', 50.0, 'копеек')
        );
    }
}
