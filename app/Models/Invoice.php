<?php

namespace App\Models;

use App\Services\PaymentService;
use App\Support\Decimal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'client_id',
        'subscription_id',
        'contract_id',
        'project_id',
        'issue_date',
        'due_date',
        'subtotal',
        'discount',
        'tax',
        'total',
        'paid_amount',
        'currency',
        'exchange_rate',
        'status',
        'payment_method',
        'paid_at',
        'notes',
        'terms',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'paid_at' => 'datetime',
        'client_snapshot' => 'array',
    ];

    /**
     * Client details as printed: the snapshot taken when the invoice was issued, so renaming a
     * client later never silently changes an old invoice. Drafts show the live client.
     *
     * @return array<string, string|null>
     */
    public function getBillToAttribute(): array
    {
        return $this->client_snapshot ?: \App\Services\InvoiceService::clientSnapshot($this->client);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Record full payment of whatever is still owed (no-op when nothing is owed).
     */
    public function markPaid(?string $method = null, string $source = 'manual', ?string $idempotencyKey = null): ?Payment
    {
        $payment = app(PaymentService::class)->payRemaining($this, array_filter([
            'method' => $method,
            'source' => $source,
            'idempotency_key' => $idempotencyKey,
        ]));
        $this->refresh();

        return $payment;
    }

    /**
     * wa.me link with a polite payment reminder for this invoice, or null when the client has no number.
     */
    public function paymentReminderUrl(): ?string
    {
        $client = $this->client;
        $number = $client?->whatsapp_number;
        if (! $number) {
            return null;
        }

        $what = $this->subscription?->domain_name ?: ($this->items()->value('description') ?? $this->invoice_number);
        $amount = Subscription::formatAmount($this->remaining_balance, $this->currency);
        $when = $this->due_date->isPast() && ! $this->due_date->isToday()
            ? "کە لە {$this->due_date->format('Y-m-d')} کاتی دانی بوو"
            : "کە لە {$this->due_date->format('Y-m-d')} کاتی دانییەتی";
        $text = "سڵاو بەڕێز " . ($client->business_name ?: $client->name) . "،\n"
            . "بیرخستنەوەیەکی دۆستانە: بڕی {$amount} بۆ {$what} {$when}.\n"
            . "پارەدان: FIB · FastPay · کاش\nسوپاس — iCode Group";

        return "https://wa.me/{$number}?text=" . rawurlencode($text);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderBy('paid_on')->orderBy('id');
    }

    /** Invoices that count as money owed: issued, not cancelled, not drafts, balance left. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['sent', 'partial', 'overdue'])->whereColumn('paid_amount', '<', 'total');
    }

    public function getRemainingBalanceAttribute(): float
    {
        return Decimal::of($this->total)->minus(Decimal::of($this->paid_amount))->toFloat();
    }

    public function getIsOverdueAttribute(): bool
    {
        if (in_array($this->status, ['paid', 'cancelled', 'draft'], true) || $this->remaining_balance <= 0) return false;
        return $this->due_date && Carbon::now()->startOfDay()->gt(Carbon::parse($this->due_date)->startOfDay());
    }

    /** Status for display: "overdue" is derived from the due date and balance, never stored by new code. */
    public function getDisplayStatusAttribute(): string
    {
        return $this->is_overdue ? 'overdue' : ($this->status === 'overdue' ? 'sent' : $this->status);
    }

    public function getDisplayStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->display_status] ?? $this->display_status;
    }

    public const STATUS_LABELS = [
        'draft' => 'ڕەشنووس',
        'sent' => 'نێردراوە',
        'paid' => 'دراوە',
        'partial' => 'بەشێکی دراوە',
        'overdue' => 'دواکەوتووە',
        'cancelled' => 'هەڵوەشاوەتەوە',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Create an invoice with the next free number. invoice_number is unique in the database; when two
     * admins save at the same moment the loser retries with a fresh number.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function createNumbered(array $attributes, int $attempts = 5): self
    {
        for ($i = 1; ; $i++) {
            $number = $i === 1 && ! empty($attributes['invoice_number']) ? $attributes['invoice_number'] : self::generateNextInvoiceNumber();
            try {
                // Savepoint, so a collision does not abort an outer transaction.
                return DB::transaction(fn () => self::create(array_merge($attributes, ['invoice_number' => $number])));
            } catch (UniqueConstraintViolationException $e) {
                if ($i >= $attempts) {
                    throw $e;
                }
            }
        }
    }

    public static function generateNextInvoiceNumber(): string
    {
        $year = date('Y');
        $prefix = "ICODE-INV-{$year}-";

        $latestInvoice = self::where('invoice_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $nextNum = 1;
        if ($latestInvoice && preg_match('/-(\d+)$/', $latestInvoice->invoice_number, $matches)) {
            $nextNum = intval($matches[1]) + 1;
        } else {
            $nextNum = self::count() + 1;
        }

        $invoiceNumber = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

        while (self::where('invoice_number', $invoiceNumber)->exists()) {
            $nextNum++;
            $invoiceNumber = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
        }

        return $invoiceNumber;
    }
}
