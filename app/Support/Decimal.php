<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Exact 2-decimal money arithmetic for totals and balances (no float accumulation).
 */
class Decimal
{
    public static function of(mixed $value): BigDecimal
    {
        if ($value instanceof BigDecimal) {
            return $value->toScale(2, RoundingMode::HalfUp);
        }
        $value = is_string($value) ? trim($value) : $value;
        if ($value === null || $value === '') {
            return BigDecimal::zero()->toScale(2);
        }
        if (is_float($value)) {
            // Floats come from form inputs; go through a fixed-precision string first.
            $value = number_format($value, 6, '.', '');
        }

        return BigDecimal::of((string) $value)->toScale(2, RoundingMode::HalfUp);
    }

    /**
     * @param  iterable<mixed>  $values
     */
    public static function sum(iterable $values): BigDecimal
    {
        $total = BigDecimal::zero()->toScale(2);
        foreach ($values as $v) {
            $total = $total->plus(self::of($v));
        }

        return $total;
    }

    public static function mul(mixed $a, mixed $b): BigDecimal
    {
        return self::of($a)->multipliedBy(BigDecimal::of(is_float($b) ? number_format($b, 6, '.', '') : (string) $b))
            ->toScale(2, RoundingMode::HalfUp);
    }

    public static function str(BigDecimal $value): string
    {
        return (string) $value->toScale(2, RoundingMode::HalfUp);
    }
}
