<?php

namespace Tests\Unit;

use App\Support\CurrencyFormatter;
use PHPUnit\Framework\TestCase;

class CurrencyFormatterTest extends TestCase
{
    public function test_it_formats_rupiah_without_intl(): void
    {
        $this->assertSame('Rp 25.000', CurrencyFormatter::rupiah('25000.00'));
        $this->assertSame('Rp 1.234.568', CurrencyFormatter::rupiah(1234567.89));
    }
}
