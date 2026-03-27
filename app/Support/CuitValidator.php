<?php

namespace App\Support;

class CuitValidator
{
    public static function normalize(?string $cuit): string
    {
        return preg_replace('/\D/', '', (string) $cuit);
    }

    public static function isValid(?string $cuit): bool
    {
        $n = self::normalize($cuit);

        if (strlen($n) !== 11) {
            return false;
        }

        $coeficients = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += (int) $n[$i] * $coeficients[$i];
        }
        $verif = 11 - ($sum % 11);
        if ($verif === 11) {
            $verif = 0;
        }
        if ($verif === 10) {
            $verif = 9;
        }

        return (int) $n[10] === $verif;
    }
}
