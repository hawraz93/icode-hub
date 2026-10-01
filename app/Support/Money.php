<?php

namespace App\Support;

/**
 * Money display and totals. Dollars and dinars are never converted or added together:
 * totals are kept per currency and shown side by side.
 */
class Money
{
    /** Display order of currencies in totals. */
    private const ORDER = ['USD', 'IQD'];

    /**
     * "$100", "$12.50", "100,000 د.ع" — no trailing ".00".
     */
    public static function format(float $amount, ?string $currency = 'USD'): string
    {
        $currency = strtoupper((string) ($currency ?: 'USD'));

        if ($currency === 'IQD') {
            return number_format(round($amount)) . ' د.ع';
        }

        $decimals = abs($amount - round($amount)) < 0.005 ? 0 : 2;
        $sign = $amount < 0 ? '-' : '';
        $number = number_format(abs($amount), $decimals);

        return $currency === 'USD' ? "{$sign}\${$number}" : "{$sign}{$number} {$currency}";
    }

    /**
     * Sum items per currency: ['USD' => 300.0, 'IQD' => 200000.0]. Zero totals are dropped.
     *
     * @param  iterable<mixed>  $items
     * @return array<string, float>
     */
    public static function totals(iterable $items, callable $amount, callable $currency): array
    {
        $totals = [];
        foreach ($items as $item) {
            $code = strtoupper((string) ($currency($item) ?: 'USD'));
            $totals[$code] = ($totals[$code] ?? 0) + (float) $amount($item);
        }

        return self::sorted(array_filter($totals, fn ($v) => abs($v) >= 0.005));
    }

    /**
     * Subtract per currency: $a - $b, keeping every currency that appears in either.
     *
     * @param  array<string, float>  $a
     * @param  array<string, float>  $b
     * @return array<string, float>
     */
    public static function subtract(array $a, array $b): array
    {
        $result = $a;
        foreach ($b as $code => $value) {
            $result[$code] = ($result[$code] ?? 0) - $value;
        }

        return self::sorted(array_filter($result, fn ($v) => abs($v) >= 0.005));
    }

    /**
     * "$300 · 200,000 د.ع", or $empty when there is nothing.
     *
     * @param  array<string, float>  $totals
     */
    public static function formatTotals(array $totals, string $empty = '$0', string $separator = ' · '): string
    {
        if ($totals === []) {
            return $empty;
        }

        return implode($separator, array_map(fn ($code, $value) => self::format($value, $code), array_keys($totals), $totals));
    }

    /**
     * @param  array<string, float>  $totals
     * @return array<string, float>
     */
    private static function sorted(array $totals): array
    {
        uksort($totals, fn ($x, $y) => (array_search($x, self::ORDER, true) === false ? 99 : array_search($x, self::ORDER, true))
            <=> (array_search($y, self::ORDER, true) === false ? 99 : array_search($y, self::ORDER, true)));

        return $totals;
    }
}
