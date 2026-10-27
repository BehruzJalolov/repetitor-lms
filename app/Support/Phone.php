<?php

namespace App\Support;

class Phone
{
    /**
     * Telefon raqamni yagona "+998XXXXXXXXX" ko'rinishiga keltiradi.
     * Yaroqsiz bo'lsa null qaytaradi.
     */
    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if (strlen($digits) === 9) {
            $digits = '998'.$digits;
        }

        if (! preg_match('/^998\d{9}$/', $digits)) {
            return null;
        }

        return '+'.$digits;
    }
}
