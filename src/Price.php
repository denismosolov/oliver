<?php

declare(strict_types=1);

namespace Oliver;

use Oliver\InvalidPriceException;

class Price
{
    private string $currency;

    public function __construct(string $currency = 'RUB')
    {
        $this->currency = $currency;
    }

    /**
     * Переводит рубли и копейки в float.
     * В случае некорректных данных выбрасывает исключение, например:
     * 120 рублей 50 копеек - 120.5
     * 120 рублей - 120.0
     * 50 копеек - 0.5
     * 0.5 копейки - 0.05
     * 120 рублей 120 копеек - исключение
     * 120 рублей 10 центов - исключение
     * 50 копеек 120 рублей - исключение
     * 120 лей - исключение
     *
     * @return float
     * @throws Oliver\TIException
     */
    public function concat(float $price1, string $currency1, float $price2 = 0.0, string $currency2 = '')
    {
        if ($price2 === 0.0 && $currency2 === '') {
            // ожидаются рубли или копейки
            if (in_array($currency1, ['рубль', 'рубля', 'рублей'])) {
                // считать допустимым 120.5 рублей
                if ($price1 > 0) {
                    return $price1;
                } else {
                    throw new InvalidPriceException('не понимаю, похоже на отрицательную цену, используйте рубли и копейки, например сто рублей десять копеек.');
                }
            } elseif (in_array($currency1, ['копейка', 'копейки', 'копеек'])) {
                // не может быть больше 99 копеек
                if ($price1 > 0 && $price1 < 100.0) {
                    return $price1 * 0.01;
                } else {
                    throw new InvalidPriceException('не понимаю цену, не доложно быть больше девяносто девяти копеек, например, сто рублей десять копеек.');
                }
            } else {
                // @todo: rework
                throw new InvalidPriceException('неправильная валюта, используйте рубли и копейки, например сто рублей десять копеек.');
            }
        } else {
            if (
                in_array($currency1, ['рубль', 'рубля', 'рублей']) &&
                in_array($currency2, ['копейка', 'копейки', 'копеек'])
            ) {
                if ($price2 > 0 && $price2 < 100.0) {
                    return intval($price1) + $price2 * 0.01;
                } else {
                    throw new InvalidPriceException('не понимаю цену, не доложно быть больше девяносто девяти копеек, например, сто рублей десять копеек.');
                }
            } else {
                throw new InvalidPriceException('не понимаю цену, сначала рубли затем копейки, например, сто рублей десять копеек.');
            }
        }
        throw new InvalidPriceException('не понимаю цену, пример, сто рублей десять копеек.');
    }
}
