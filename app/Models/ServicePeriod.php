<?php

namespace App\Models;

use App\Support\Money;
use App\Support\Period;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One period of a client service, [starts_on, expires_on). expires_on is the renewal day.
 * Renewals add a row; old rows are kept (status = superseded). Write through ServicePeriodService.
 */
class ServicePeriod extends Model
{
    protected $fillable = [
        'subscription_id',
        'starts_on',
        'expires_on',
        'status',
        'renewed_from_id',
        'billing_unit',
        'billing_count',
        'price',
        'currency',
        'origin',
        'idempotency_key',
        'notes',
    ];

    protected $casts = [
        'starts_on' => 'date',
        'expires_on' => 'date',
        'billing_count' => 'integer',
        'price' => 'decimal:2',
    ];

    public const ORIGINS = [
        'initial' => 'یەکەم ماوە',
        'renewal' => 'نوێکردنەوە',
        'import' => 'هاوردەکراو',
        'invoice' => 'لە وەسڵەوە',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function renewedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'renewed_from_id');
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function getLengthLabelAttribute(): string
    {
        $unit = $this->billing_unit === 'month' ? 'مانگ' : 'ساڵ';

        return "{$this->billing_count} {$unit}";
    }

    public function getPriceLabelAttribute(): string
    {
        return Money::format((float) $this->price, $this->currency);
    }

    /** Whether the stored end equals start + unit × count (false means a custom period). */
    public function getIsRegularAttribute(): bool
    {
        return Period::add($this->starts_on, $this->billing_unit, $this->billing_count)->equalTo(Carbon::parse($this->expires_on)->startOfDay());
    }
}
