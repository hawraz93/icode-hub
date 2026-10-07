<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'total_price',
        'service_type',
        'billing_cycle',
        'start_date',
        'expiry_date',
        'subscription_id',
        'service_period_id',
        'custom_period',
        'period_note',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'start_date' => 'date',
        'expiry_date' => 'date',
        'custom_period' => 'boolean',
    ];

    public const SERVICE_TYPES = [
        'development' => 'دروستکردنی وێبسایت / سیستەم',
        'hosting' => 'هۆست',
        'domain' => 'دۆمەین',
        'vps' => 'VPS',
        'email' => 'ئیمەیڵ',
        'maintenance' => 'پشتگیری / چاککردنەوە',
        'license' => 'لایسەنس',
        'other' => 'هی تر',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function servicePeriod(): BelongsTo
    {
        return $this->belongsTo(ServicePeriod::class);
    }

    public function getIsRecurringAttribute(): bool
    {
        return in_array($this->billing_cycle, ['monthly', 'annual'], true);
    }

    /** month / year for recurring lines, null for one-time lines. */
    public function getBillingUnitAttribute(): ?string
    {
        return match ($this->billing_cycle) {
            'monthly' => 'month',
            'annual' => 'year',
            default => null,
        };
    }

    /** For recurring lines quantity is the number of months/years; for one-time lines it is a count. */
    public function getDurationCountAttribute(): ?int
    {
        return $this->is_recurring ? (int) $this->quantity : null;
    }

    public function getQuantityLabelAttribute(): string
    {
        $n = rtrim(rtrim(number_format((float) $this->quantity, 2, '.', ''), '0'), '.');

        return match ($this->billing_cycle) {
            'monthly' => "{$n} مانگ",
            'annual' => "{$n} ساڵ",
            default => $n,
        };
    }

    public function getPriceBasisLabelAttribute(): string
    {
        return match ($this->billing_cycle) {
            'annual' => 'نرخی ساڵێک',
            'monthly' => 'نرخی مانگێک',
            default => 'یەکجار',
        };
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
