<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Record full payment of whatever is still owed.
     */
    public function markPaid(?string $method = null): void
    {
        $this->update([
            'status' => 'paid',
            'paid_amount' => $this->total,
            'paid_at' => now(),
            'payment_method' => $method ?? $this->payment_method,
        ]);

        ActivityReminder::create([
            'client_id' => $this->client_id,
            'subscription_id' => $this->subscription_id,
            'invoice_id' => $this->id,
            'type' => 'invoice_paid',
            'channel' => 'system',
            'message' => "پارەی وەسڵی {$this->invoice_number} وەرگیرا",
            'status' => 'sent',
            'sent_at' => now(),
        ]);
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

    public function getRemainingBalanceAttribute(): float
    {
        return (float) ($this->total - $this->paid_amount);
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->status === 'paid' || $this->status === 'cancelled') return false;
        return $this->due_date && Carbon::now()->startOfDay()->gt(Carbon::parse($this->due_date)->startOfDay());
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'ڕەشنووس',
            'sent' => 'نێردراوە',
            'paid' => 'دراوە',
            'partial' => 'بەشێکی دراوە',
            'overdue' => 'دواکەوتووە',
            'cancelled' => 'هەڵوەشاوەتەوە',
            default => $this->status,
        };
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
