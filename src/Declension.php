<?php

declare(strict_types=1);

namespace Oliver;

class Declension
{
    /**
     * Используй для склонения существительных,
     * например для существительного лот:
     * 1 лот
     * 2 лота
     * 5 лотов
     *
     * @param int $howMany - число перед склоняемым существительным
     * @param string $one - 1,21,31,41... лот
     * @param string $twofour - 2,3,4,22,23,24... лота
     * @param string $rest - 5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20... лотов
     */
    public static function default(int $howMany, string $one, string $twofour, string $rest)
    {
        $digits = $howMany % 100;
        if ($digits > 20) {
            $digits = $digits % 10;
        }
        switch ($digits) {
            case 1:
                return $one;
            case 2:
            case 3:
            case 4:
                return $twofour;
            default:
                return $rest;
        }
    }
}
