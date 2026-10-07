<?php

namespace App\Models;

use App\Support\Money;
use App\Support\Period;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A recurring expense plan. It forecasts what will be paid and when; it never counts as money paid.
 * Paying a period goes through ExpenseService, which writes an Expense row and moves next_due_on.
 */
class ExpenseSchedule extends Model
{
    protected $fillable = [
        'title',
        'category',
        'vendor',
        'currency',
        'amount_per_cycle',
        'cycle_unit',
        'cycle_count',
        'starts_on',
        'next_due_on',
        'ends_on',
        'status',
        'auto_renew',
        'payment_method',
        'server_id',
        'notes',
        'legacy_source',
        'legacy_id',
    ];

    protected $casts = [
        'amount_per_cycle' => 'decimal:2',
        'cycle_count' => 'integer',
        'starts_on' => 'date',
        'next_due_on' => 'date',
        'ends_on' => 'date',
        'auto_renew' => 'boolean',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /** Forecast only: the plan's cost per year in its own currency. */
    public function getAnnualForecastAttribute(): float
    {
        return (float) $this->amount_per_cycle * Period::perYear($this->cycle_unit, $this->cycle_count);
    }

    public function getCycleLabelAttribute(): string
    {
        return Period::label($this->cycle_unit, $this->cycle_count);
    }

    public function getAmountLabelAttribute(): string
    {
        return Money::format((float) $this->amount_per_cycle, $this->currency) . ' · ' . $this->cycle_label;
    }

    /** End of the period that starts at next_due_on. */
    public function periodEndFrom(Carbon|string $start): Carbon
    {
        return Period::add($start, $this->cycle_unit, $this->cycle_count);
    }

    public function getDaysUntilDueAttribute(): int
    {
        return (int) Carbon::today()->diffInDays($this->next_due_on->copy()->startOfDay(), false);
    }
}
