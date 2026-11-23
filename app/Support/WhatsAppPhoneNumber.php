<?php

namespace App\Support;

class WhatsAppPhoneNumber
{
    public static function normalize(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (! $digits) {
            return null;
        }

        if (! str_starts_with($digits, '62')) {
            if (str_starts_with($digits, '0')) {
                $digits = '62'.substr($digits, 1);
            } elseif (str_starts_with($digits, '8')) {
                $digits = '62'.$digits;
            } else {
                return null;
            }
        }

        return preg_match('/^62[0-9]{8,13}$/', $digits) === 1
            ? $digits
            : null;
    }
}
