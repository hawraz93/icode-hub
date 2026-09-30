<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Server extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
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

    public function getMonthlyRevenueAttribute(): float
    {
        // Calculate monthly revenue from hosted subscriptions
        $rev = 0;
        foreach ($this->subscriptions()->where('status', 'active')->get() as $sub) {
            $rev += $sub->monthly_selling_price;
        }
        return $rev;
    }

    public function getProfitMarginAttribute(): float
    {
        $cost = $this->billing_cycle === 'annual' ? ($this->cost / 12) : (float) $this->cost;
        return (float) ($this->monthly_revenue - $cost);
    }
}
