<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_formats_without_trailing_zeros(): void
    {
        $this->assertSame('$100', Money::format(100, 'USD'));
        $this->assertSame('$100', Money::format(100.00, 'USD'));
        $this->assertSame('$12.50', Money::format(12.5, 'USD'));
        $this->assertSame('$1,250', Money::format(1250, 'USD'));
        $this->assertSame('100,000 د.ع', Money::format(100000, 'IQD'));
        $this->assertSame('-$11', Money::format(-11, 'USD'));
    }

    public function test_totals_never_mix_currencies(): void
    {
        $items = [['a' => 100, 'c' => 'USD'], ['a' => 100000, 'c' => 'IQD'], ['a' => 20, 'c' => 'USD'], ['a' => 0, 'c' => 'EUR']];

        $totals = Money::totals($items, fn ($i) => $i['a'], fn ($i) => $i['c']);

        $this->assertSame(['USD' => 120.0, 'IQD' => 100000.0], $totals);
        $this->assertSame('$120 · 100,000 د.ع', Money::formatTotals($totals));
        $this->assertSame('$0', Money::formatTotals([]));
        $this->assertSame(['USD' => 100.0, 'IQD' => -5000.0], Money::subtract($totals, ['USD' => 20, 'IQD' => 105000]));
    }
}
