<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use InvalidArgumentException;

/**
 * Service and expense periods are half-open: [start, end). The end date is the day the next
 * period begins (the renewal day). Month/year steps never overflow: Jan 31 + 1 month = Feb 28/29.
 */
class Period
{
    public const UNITS = ['month', 'year'];

    /** Legacy billing_cycle strings mapped to [unit, count]. */
    public const LEGACY_CYCLES = [
        'monthly' => ['month', 1],
        'quarterly' => ['month', 3],
        'semi_annual' => ['month', 6],
        'annual' => ['year', 1],
        'biennial' => ['year', 2],
    ];

    public static function add(CarbonInterface|string $start, string $unit, int $count): Carbon
    {
        self::assertValid($unit, $count);
        $date = Carbon::parse($start)->startOfDay();

        return $unit === 'year' ? $date->addYearsNoOverflow($count) : $date->addMonthsNoOverflow($count);
    }

    /**
     * @return array{0: string, 1: int}
     */
    public static function fromLegacyCycle(?string $cycle): array
    {
        return self::LEGACY_CYCLES[$cycle] ?? self::LEGACY_CYCLES['monthly'];
    }

    public static function toLegacyCycle(string $unit, int $count): ?string
    {
        $found = array_search([$unit, $count], self::LEGACY_CYCLES, true);

        return $found === false ? null : $found;
    }

    /** How many cycles fit in a year (monthly = 12, biennial = 0.5). Used only for forecasts. */
    public static function perYear(string $unit, int $count): float
    {
        self::assertValid($unit, $count);

        return $unit === 'year' ? 1 / $count : 12 / $count;
    }

    public static function label(string $unit, int $count): string
    {
        return match ([$unit, $count]) {
            ['month', 1] => 'مانگانە',
            ['month', 3] => 'هەر ٣ مانگ',
            ['month', 6] => 'هەر ٦ مانگ',
            ['year', 1] => 'ساڵانە',
            ['year', 2] => 'هەر ٢ ساڵ',
            default => $unit === 'year' ? "هەر {$count} ساڵ" : "هەر {$count} مانگ",
        };
    }

    public static function assertValid(string $unit, int $count): void
    {
        if (! in_array($unit, self::UNITS, true)) {
            throw new InvalidArgumentException("Unknown period unit [{$unit}].");
        }
        if ($count < 1) {
            throw new InvalidArgumentException('A period must be at least one whole unit.');
        }
    }
}
