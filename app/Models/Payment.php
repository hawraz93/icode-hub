<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Money received against an invoice. Rows are never edited or deleted: a mistake is corrected
 * with a reversal row (negative amount) that points at the original. Create through PaymentService.
 */
class Payment extends Model
{
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_REVERSAL = 'reversal';

    public const METHODS = [
        'cash' => 'کاش',
        'fib' => 'FIB',
        'fastpay' => 'FastPay',
        'zaincash' => 'ZainCash',
        'bank' => 'گواستنەوەی بانکی',
        'other' => 'هی تر',
    ];

    protected $fillable = [
        'invoice_id',
        'type',
        'reverses_payment_id',
        'amount',
        'currency',
        'paid_on',
        'method',
        'reference',
        'notes',
        'recorded_by',
        'source',
        'idempotency_key',
        'needs_review',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_on' => 'date',
        'needs_review' => 'boolean',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function reverses(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_payment_id');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reverses_payment_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeReceivedBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->whereNotNull('paid_on')->whereBetween('paid_on', [$from, $to]);
    }

    public function getIsReversedAttribute(): bool
    {
        return $this->type === self::TYPE_PAYMENT && $this->reversal()->exists();
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->method] ?? ($this->method ?: '—');
    }
}
