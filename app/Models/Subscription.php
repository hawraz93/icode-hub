<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'server_id',
        'name',
        'type',
        'domain_name',
        'provider',
        'cost_price',
        'selling_price',
        'currency',
        'billing_cycle',
        'payment_plan',
        'installment_amount',
        'payment_day',
        'start_date',
        'expiry_date',
        'registry_expiry_date',
        'registry_checked_at',
        'auto_renew',
        'status',
        'renewal_stage',
        'stage_updated_at',
        'reminder_days_before',
        'last_reminded_at',
        'credentials_note',
        'notes',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'start_date' => 'date',
        'expiry_date' => 'date',
        'auto_renew' => 'boolean',
        'last_reminded_at' => 'datetime',
        'stage_updated_at' => 'datetime',
        'registry_expiry_date' => 'date',
        'registry_checked_at' => 'datetime',
        'renewal_stage' => 'integer',
        'installment_amount' => 'decimal:2',
        'payment_day' => 'integer',
    ];

    public const STAGE_NONE = 0;
    public const STAGE_NOTIFIED = 1;
    public const STAGE_PAID = 2;

    public const STAGE_LABELS = [
        self::STAGE_NONE => 'هێشتا ئاگادار نەکراوەتەوە',
        self::STAGE_NOTIFIED => 'ئاگادارکرایەوە',
        self::STAGE_PAID => 'پارەی وەرگیرا',
    ];

    /**
     * Subscriptions that still need a renewal decision: anything not cancelled
     * whose expiry falls within the given window, including already-expired ones.
     */
    public function scopeOpenRenewals(Builder $query, int $withinDays = 30): Builder
    {
        return $query->whereIn('status', ['active', 'grace_period', 'expired'])
            ->whereDate('expiry_date', '<=', Carbon::now()->addDays($withinDays)->toDateString());
    }

    public function renewalCycleEnd(Carbon $from): Carbon
    {
        return match ($this->billing_cycle) {
            'biennial' => $from->copy()->addYears(2),
            'semi_annual' => $from->copy()->addMonths(6),
            'quarterly' => $from->copy()->addMonths(3),
            'monthly' => $from->copy()->addMonth(),
            default => $from->copy()->addYear(),
        };
    }

    /**
     * Extend the expiry by one billing cycle and reset the renewal workflow.
     */
    public function renew(): Carbon
    {
        $currentExpiry = Carbon::parse($this->expiry_date);
        // Registrars extend late domain renewals from the old expiry; other services restart from today.
        $fromOldExpiry = in_array($this->type, ['domain', 'bundle'], true) || ! $currentExpiry->isPast();
        $baseDate = $fromOldExpiry ? $currentExpiry : Carbon::now();
        $newExpiry = $this->renewalCycleEnd($baseDate);
        if ($newExpiry->isPast()) {
            // Long-lapsed domain: effectively a new registration from today.
            $newExpiry = $this->renewalCycleEnd(Carbon::now());
        }

        $this->update([
            'expiry_date' => $newExpiry->format('Y-m-d'),
            'status' => 'active',
            'renewal_stage' => self::STAGE_NONE,
            'stage_updated_at' => now(),
            'last_reminded_at' => null,
        ]);

        ActivityReminder::create([
            'client_id' => $this->client_id,
            'subscription_id' => $this->id,
            'type' => 'subscription_renewed',
            'channel' => 'system',
            'message' => "نوێکرایەوە تا {$newExpiry->format('Y-m-d')}",
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return $newExpiry;
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /** Invoices the client still owes money on. */
    public function openInvoices(): HasMany
    {
        return $this->invoices()
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->whereColumn('paid_amount', '<', 'total')
            ->orderBy('due_date');
    }

    /** Outstanding amount in USD across all open invoices of this service. */
    public function getUnpaidBalanceUsdAttribute(): float
    {
        return (float) $this->openInvoices->sum(fn (Invoice $i) => self::toUsd($i->remaining_balance, $i->currency));
    }

    public function getNextDueInvoiceAttribute(): ?Invoice
    {
        return $this->openInvoices->first();
    }

    /**
     * Record money the client owes for this service (a debt with a due date), as an invoice.
     */
    public function bill(float $amount, Carbon $dueDate, string $description, ?string $notes = null): Invoice
    {
        $invoice = Invoice::create([
            'invoice_number' => Invoice::generateNextInvoiceNumber(),
            'client_id' => $this->client_id,
            'subscription_id' => $this->id,
            'issue_date' => Carbon::now(),
            'due_date' => $dueDate->toDateString(),
            'subtotal' => $amount,
            'discount' => 0.00,
            'tax' => 0.00,
            'total' => $amount,
            'paid_amount' => 0.00,
            'currency' => $this->currency ?: 'USD',
            'status' => 'sent',
            'payment_method' => 'FIB / FastPay / کاش',
            'notes' => $notes ?? "{$description} ({$this->domain_name})",
            'terms' => 'تکایە لە کاتی دیاریکراودا گوژمەکە پاکتاو بکەن.',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => $description,
            'quantity' => 1,
            'unit_price' => $amount,
            'total_price' => $amount,
            'service_type' => $this->type,
        ]);

        return $invoice;
    }

    public function createRenewalInvoice(): Invoice
    {
        return $this->bill(
            (float) $this->selling_price,
            Carbon::parse($this->expiry_date),
            "نوێکردنەوەی {$this->name} ({$this->type_label})",
            "وەسڵی نوێکردنەوەی: {$this->name} ({$this->domain_name})",
        );
    }

    /**
     * For monthly-installment services: create this month's invoice once, on or after the payment day.
     */
    public function billMonthlyInstallment(?Carbon $today = null): ?Invoice
    {
        $today ??= Carbon::today();
        if ($this->payment_plan !== 'monthly' || (float) $this->installment_amount <= 0 || $this->status === 'cancelled') {
            return null;
        }

        $due = $today->copy()->startOfMonth()->day(min(max(1, (int) $this->payment_day), $today->daysInMonth));
        if ($today->lt($due) || $due->lt(Carbon::parse($this->start_date)->startOfMonth()) || $due->gt(Carbon::parse($this->expiry_date))) {
            return null;
        }

        $alreadyBilled = $this->invoices()
            ->whereYear('due_date', $due->year)
            ->whereMonth('due_date', $due->month)
            ->exists();
        if ($alreadyBilled) {
            return null;
        }

        $month = ['', 'کانوونی دووەم', 'شوبات', 'ئازار', 'نیسان', 'ئایار', 'حوزەیران', 'تەمموز', 'ئاب', 'ئەیلوول', 'تشرینی یەکەم', 'تشرینی دووەم', 'کانوونی یەکەم'][$due->month];

        return $this->bill((float) $this->installment_amount, $due, "کرێی مانگی {$month} {$due->year} - {$this->name}");
    }

    /**
     * "https://Finance.iCodeGroup.net/path" -> "finance.icodegroup.net". Non-domain names are kept as typed.
     */
    public static function normalizeDomain(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $host = trim($value);
        $host = preg_replace('#^[a-z][a-z0-9+.-]*://#i', '', $host);
        $host = preg_replace('#[/?\#].*$#', '', $host);
        $host = rtrim($host, '.');

        return str_contains($host, '.') ? strtolower($host) : $host;
    }

    public function setDomainNameAttribute(?string $value): void
    {
        $this->attributes['domain_name'] = self::normalizeDomain($value);
    }
    /**
     * Convert an amount in the given currency to USD (IQD uses USD_TO_IQD).
     */
    public static function toUsd(float $amount, ?string $currency): float
    {
        return strtoupper((string) $currency) === 'IQD'
            ? $amount / max(1, (float) config('app.usd_to_iqd', 1500))
            : $amount;
    }

    /**
     * "$100" for dollars, "100,000 د.ع" for dinars.
     */
    public static function formatAmount(float $amount, ?string $currency): string
    {
        if (strtoupper((string) $currency) === 'IQD') {
            return number_format($amount) . ' د.ع';
        }
        $decimals = fmod($amount, 1) == 0 ? 0 : 2;

        return (strtoupper((string) ($currency ?: 'USD')) === 'USD' ? '$' : $currency . ' ') . number_format($amount, $decimals);
    }

    public function getSellingUsdAttribute(): float
    {
        return self::toUsd((float) $this->selling_price, $this->currency);
    }

    public function getCostUsdAttribute(): float
    {
        return self::toUsd((float) $this->cost_price, $this->currency);
    }

    public function getSellingLabelAttribute(): string
    {
        return self::formatAmount((float) $this->selling_price, $this->currency);
    }

    /**
     * Price line for client messages: dinar prices stay in dinars, dollar prices get an IQD hint.
     */
    private function priceForMessage(): string
    {
        if (strtoupper((string) $this->currency) === 'IQD') {
            return $this->selling_label;
        }

        $iqd = number_format((float) $this->selling_price * (float) config('app.usd_to_iqd', 1500));

        return "{$this->selling_label} (≈ {$iqd} د.ع)";
    }

    /**
     * Ready-to-send WhatsApp reminder text for the client.
     */
    public function whatsappMessage(string $lang = 'ku'): string
    {
        $client = $this->client;
        $clientName = $client?->business_name ?: $client?->name;
        $target = $this->domain_name ?: $this->name;
        $date = $this->expiry_date->format('Y-m-d');
        $price = $this->priceForMessage();
        $expired = $this->days_until_expiry < 0;

        if ($lang === 'ar') {
            $what = match ($this->type) {
                'domain' => 'النطاق',
                'email' => 'البريد الرسمي',
                'bundle' => 'النطاق والاستضافة',
                default => 'الاستضافة',
            };
            $verb = $expired ? 'انتهى بتاريخ' : 'ينتهي بتاريخ';

            return "مرحباً {$clientName}،\nنود تذكيركم بأن {$what} ({$target}) {$verb} {$date}.\nمبلغ التجديد: {$price}\nالدفع عبر FIB أو FastPay أو نقداً.\n— iCode Group";
        }

        $what = match ($this->type) {
            'domain' => 'دۆمەینی',
            'email' => 'ئیمەیڵی بزنسی',
            'bundle' => 'دۆمەین و هۆستینگی',
            'hosting' => 'هۆستینگی',
            default => $this->name . ' -',
        };
        $warn = match ($this->type) {
            'email' => 'ئەگەر نوێ نەکرێتەوە، ئیمەیڵەکانتان ڕادەوەستن.',
            'domain', 'bundle' => 'ئەگەر نوێ نەکرێتەوە، وێبسایت و ئیمەیڵەکانتان دەوەستن.',
            'hosting' => 'ئەگەر نوێ نەکرێتەوە، وێبسایتەکەتان دادەخرێت.',
            default => 'تکایە بۆ بەردەوامبوونی خزمەتگوزارییەکە نوێی بکەنەوە.',
        };
        $when = $expired ? "لە {$date} بەسەرچووە" : "لە بەرواری {$date} بەسەردەچێت";

        return "سڵاو بەڕێز {$clientName}،\n{$what} {$target} {$when}.\n{$warn}\nبڕی نوێکردنەوە: {$price}\nپارەدان: FIB · FastPay · کاش\n— iCode Group";
    }

    public function whatsappUrl(string $lang = 'ku'): ?string
    {
        $number = $this->client?->whatsapp_number;

        return $number ? "https://wa.me/{$number}?text=" . rawurlencode($this->whatsappMessage($lang)) : null;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function getDaysUntilExpiryAttribute(): int
    {
        if (!$this->expiry_date) return 999;
        return (int) Carbon::now()->startOfDay()->diffInDays(Carbon::parse($this->expiry_date)->startOfDay(), false);
    }

    public function getExpiryStatusTextAttribute(): string
    {
        $days = $this->days_until_expiry;
        if ($days < 0) {
            $absDays = abs($days);
            return "بەسەرچووە! ({$absDays} ڕۆژ پێش ئێستا)";
        }
        if ($days === 0) {
            return "ئەمڕۆ بەسەردەچێت!";
        }
        return "{$days} ڕۆژ ماوە";
    }

    /**
     * True when the registry (RDAP) reports a different expiry than the one recorded here.
     */
    public function getHasRegistryMismatchAttribute(): bool
    {
        return $this->registry_expiry_date && $this->expiry_date
            && abs($this->registry_expiry_date->diffInDays($this->expiry_date)) > 1;
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        $days = $this->days_until_expiry;
        return $days <= 30 && $days >= 0 && $this->status === 'active';
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->days_until_expiry < 0 || $this->status === 'expired';
    }

    public function getMonthlySellingPriceAttribute(): float
    {
        if ($this->payment_plan === 'monthly' && (float) $this->installment_amount > 0) {
            return self::toUsd((float) $this->installment_amount, $this->currency);
        }

        return match ($this->billing_cycle) {
            'monthly' => $this->selling_usd,
            'quarterly' => $this->selling_usd / 3,
            'semi_annual' => $this->selling_usd / 6,
            'annual' => $this->selling_usd / 12,
            'biennial' => $this->selling_usd / 24,
            default => $this->selling_usd / 12,
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'bundle' => 'دۆمەین و هۆستینگ (Domain & Hosting)',
            'hosting' => 'تەنها هۆستینگ (Hosting Only)',
            'domain' => 'تەنها دۆمەین (Domain Only)',
            'email' => 'ئیمەیڵی کۆمپانیا (Email)',
            'vps' => 'سێرڤەری تایبەت (VPS)',
            'license' => 'مۆڵەتنامەی بەرنامە (License)',
            'maintenance' => 'پشتگیری و چاکسازی',
            default => 'خزمەتگوزاری تر',
        };
    }
}
