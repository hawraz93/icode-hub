<?php

namespace App\Services;

use App\Models\ServicePeriod;
use App\Models\Subscription;
use App\Support\Decimal;
use App\Support\Period;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Owns the period history of client services. Subscription start/expiry dates mirror the
 * current period; every renewal is a new row, the old one is kept as "superseded".
 */
class ServicePeriodService
{
    /** Set while this service writes the mirror dates itself, so the model hook does not re-sync. */
    public static bool $syncPaused = false;

    /**
     * Called after a service is saved: make sure it has a current period and that the period
     * follows corrections typed on the service form. Never creates a renewal.
     */
    public function syncCurrent(Subscription $sub): ?ServicePeriod
    {
        if (! $sub->start_date || ! $sub->expiry_date) {
            return null;
        }

        $current = $sub->currentPeriod()->first();
        if (! $current) {
            return $this->importCurrent($sub, $sub->wasRecentlyCreated ? 'initial' : 'import');
        }

        $start = Carbon::parse($sub->start_date)->toDateString();
        $end = Carbon::parse($sub->expiry_date)->toDateString();
        if ($current->starts_on->toDateString() !== $start || $current->expires_on->toDateString() !== $end) {
            // A correction of the current period (typo, registry date), not a new period.
            [$unit, $count] = self::lengthOf($start, $end, $sub->billing_cycle);
            $current->update(['starts_on' => $start, 'expires_on' => $end, 'billing_unit' => $unit, 'billing_count' => $count]);
        }

        return $current;
    }

    /**
     * The service's current dates as one period. Idempotent per service.
     */
    public function importCurrent(Subscription $sub, string $origin = 'import'): ServicePeriod
    {
        return ServicePeriod::firstOrCreate(
            ['idempotency_key' => "current:subscription:{$sub->id}"],
            self::importPlan($sub, $origin),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function importPlan(Subscription $sub, string $origin = 'import'): array
    {
        $start = Carbon::parse($sub->start_date)->toDateString();
        $end = Carbon::parse($sub->expiry_date)->toDateString();
        [$unit, $count] = self::lengthOf($start, $end, $sub->billing_cycle);
        [$cycleUnit, $cycleCount] = Period::fromLegacyCycle($sub->billing_cycle);
        $matchesCycle = [$unit, $count] === [$cycleUnit, $cycleCount];

        return [
            'subscription_id' => $sub->id,
            'starts_on' => $start,
            'expires_on' => $end,
            'status' => 'active',
            'billing_unit' => $unit,
            'billing_count' => $count,
            // A biennial price is already the 2-year price; it is never multiplied again.
            'price' => Decimal::str(Decimal::of($sub->selling_price)),
            'currency' => strtoupper($sub->currency ?: 'USD'),
            'origin' => $origin,
            'notes' => $matchesCycle ? null : 'درێژی ماوە لەگەڵ billing cycleی خزمەتگوزاری یەک ناگرێتەوە؛ نرخ وەک خۆی هێڵرایەوە.',
        ];
    }

    /**
     * Add the next period and supersede the current one. The service dates follow.
     */
    public function recordRenewal(Subscription $sub, Carbon $start, Carbon $end, ?string $price = null, ?string $key = null, string $origin = 'renewal'): ServicePeriod
    {
        if ($end->lte($start)) {
            throw new \InvalidArgumentException('A period must end after it starts.');
        }

        return DB::transaction(function () use ($sub, $start, $end, $price, $key, $origin) {
            if ($key && ($existing = ServicePeriod::where('idempotency_key', $key)->first())) {
                return $existing;
            }

            $current = $sub->currentPeriod()->first() ?? $this->importCurrent($sub);
            [$unit, $count] = self::lengthOf($start->toDateString(), $end->toDateString(), $sub->billing_cycle);

            $period = ServicePeriod::create([
                'subscription_id' => $sub->id,
                'starts_on' => $start->toDateString(),
                'expires_on' => $end->toDateString(),
                'status' => 'active',
                'renewed_from_id' => $current->id,
                'billing_unit' => $unit,
                'billing_count' => $count,
                'price' => Decimal::str(Decimal::of($price ?? $sub->selling_price)),
                'currency' => strtoupper($sub->currency ?: 'USD'),
                'origin' => $origin,
                'idempotency_key' => $key,
            ]);
            $current->update(['status' => 'superseded']);

            // The service's dates always describe its current period.
            $this->mirror($sub, ['start_date' => $start->toDateString(), 'expiry_date' => $end->toDateString()]);

            return $period;
        });
    }

    /**
     * Write subscription mirror fields without triggering a correction sync.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function mirror(Subscription $sub, array $attributes): void
    {
        self::$syncPaused = true;
        try {
            $sub->update($attributes);
        } finally {
            self::$syncPaused = false;
        }
    }

    /**
     * Whole months/years between two dates when exact (no overflow), else the service's cycle.
     *
     * @return array{0: string, 1: int}
     */
    public static function lengthOf(string $start, string $end, ?string $fallbackCycle): array
    {
        $s = Carbon::parse($start)->startOfDay();
        $e = Carbon::parse($end)->startOfDay();
        $years = (int) $s->diffInYears($e);
        if ($years >= 1 && Period::add($s, 'year', $years)->equalTo($e)) {
            return ['year', $years];
        }
        $months = (int) $s->diffInMonths($e);
        if ($months >= 1 && Period::add($s, 'month', $months)->equalTo($e)) {
            return ['month', $months];
        }

        return Period::fromLegacyCycle($fallbackCycle);
    }
}
