<?php

namespace App\Models;

use App\Services\ExpenseService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class Server extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'kind',
        'provider',
        'ip_address',
        'location',
        'specs',
        'cost',
        'currency',
        'billing_cycle',
        'purchase_date',
        'renewal_date',
        'status',
        'auto_renew',
        'notes',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'purchase_date' => 'date',
        'renewal_date' => 'date',
        'auto_renew' => 'boolean',
    ];

    /** What you bought for yourself; every kind counts as a company expense. */
    public const KINDS = [
        'server' => 'سێرڤەر / VPS',
        'domain' => 'دۆمەین',
        'email' => 'ئیمەیڵ',
        'hosting' => 'هۆستینگ',
        'software' => 'بەرنامە / سەبسکریپشن',
        'other' => 'هی تر',
    ];

    public const CYCLES = [
        'monthly' => 'مانگ',
        'quarterly' => '٣ مانگ',
        'semi_annual' => '٦ مانگ',
        'annual' => 'ساڵ',
        'biennial' => '٢ ساڵ',
    ];

    /**
     * cost / billing_cycle / renewal_date are mirrored into the server's ExpenseSchedule, which is
     * what forecasts read. Money paid is only ever an Expense row.
     */
    protected static function booted(): void
    {
        static::saved(fn (self $server) => app(ExpenseService::class)->syncServerSchedule($server));
    }

    public function schedule(): HasOne
    {
        return $this->hasOne(ExpenseSchedule::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function getKindLabelAttribute(): string
    {
        return self::KINDS[$this->kind] ?? self::KINDS['other'];
    }

    /** Forecast only: cost per year in the item's own currency. Never add this to paid expenses. */
    public function getAnnualCostAttribute(): float
    {
        return (float) $this->cost * Subscription::perYear($this->billing_cycle);
    }

    public function getCostLabelAttribute(): string
    {
        return \App\Support\Money::format((float) $this->cost, $this->currency) . ' / ' . (self::CYCLES[$this->billing_cycle] ?? 'مانگ');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function getDaysUntilRenewalAttribute(): int
    {
        if (!$this->renewal_date) return 999;
        return (int) Carbon::now()->startOfDay()->diffInDays(Carbon::parse($this->renewal_date)->startOfDay(), false);
    }

    /**
     * Advance the renewal date by whole billing cycles until it is in the future,
     * keeping the provider's billing anchor day.
     */
    public function renew(): Carbon
    {
        $next = Carbon::parse($this->renewal_date);
        do {
            $next = match ($this->billing_cycle) {
                'biennial' => $next->addYears(2),
                'annual' => $next->addYear(),
                'semi_annual' => $next->addMonths(6),
                'quarterly' => $next->addMonths(3),
                default => $next->addMonth(),
            };
        } while ($next->lte(Carbon::today()));

        $this->update([
            'renewal_date' => $next->format('Y-m-d'),
            'status' => 'active',
        ]);

        return $next;
    }

    public function getRenewalStatusTextAttribute(): string
    {
        $days = $this->days_until_renewal;
        if ($days < 0) {
            $absDays = abs($days);
            return "بەسەرچووە! ({$absDays} ڕۆژ پێش ئێستا)";
        }
        if ($days === 0) {
            return "ئەمڕۆ کاتی نوێکردنەوەیەتی!";
        }
        return "{$days} ڕۆژ ماوە بۆ نوێکردنەوە";
    }
}
