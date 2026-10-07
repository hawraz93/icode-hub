<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ledger of money actually paid. Each row counts once, on its expense_date; it is never annualised.
 * Recurring plans live in ExpenseSchedule. Mistakes are voided (status=void) instead of deleted.
 */
class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category',
        'amount',
        'currency',
        'billing_cycle',
        'expense_date',
        'payment_method',
        'vendor',
        'notes',
        'expense_schedule_id',
        'server_id',
        'project_id',
        'period_start',
        'period_end',
        'reference',
        'status',
        'void_reason',
        'voided_at',
        'idempotency_key',
        'needs_review',
        'review_note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'voided_at' => 'datetime',
        'needs_review' => 'boolean',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ExpenseSchedule::class, 'expense_schedule_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** Rows that count as money paid. */
    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', 'posted');
    }

    public function getIsVoidAttribute(): bool
    {
        return $this->status === 'void';
    }


    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'infrastructure' => 'سێرڤەر و ژێرخان',
            'software_ai' => 'AI و نەرمەکاڵا',
            'telecom' => 'ئینتەرنێت و پەیوەندی',
            'transport' => 'بەنزین و هاتووچۆ',
            'office' => 'کەرەستە و شوێن',
            'marketing' => 'ڕیکلام و مارکێتینگ',
            default => 'خەرجی تر',
        };
    }

    public function getCategoryIconAttribute(): string
    {
        return match ($this->category) {
            'infrastructure' => '🖥️',
            'software_ai' => '🤖',
            'telecom' => '📱',
            'transport' => '🚗',
            'office' => '🏢',
            'marketing' => '📢',
            default => '💸',
        };
    }

    public function getCategoryColorAttribute(): string
    {
        return match ($this->category) {
            'infrastructure' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'software_ai' => 'bg-purple-50 text-purple-700 border-purple-200',
            'telecom' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'transport' => 'bg-amber-50 text-amber-700 border-amber-200',
            'office' => 'bg-blue-50 text-blue-700 border-blue-200',
            'marketing' => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-slate-50 text-slate-700 border-slate-200',
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'fastpay' => 'فاستپەی (FastPay)',
            'fib' => 'بانکی یەکەمی عێراقی (FIB)',
            'zaincash' => 'زەین کاش (ZainCash)',
            'card' => 'ماستەرکارد / ڤیزا',
            default => 'کاش (نەقد)',
        };
    }
}
