<?php

namespace App\Support;

final class CurrencyFormatter
{
    public static function rupiah(int|float|string $amount): string
    {
        if (! is_numeric($amount)) {
            throw new \InvalidArgumentException('Amount must be numeric.');
        }

        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }
}
