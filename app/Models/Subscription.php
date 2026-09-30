<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function createRenewalInvoice(): Invoice
    {
        $invoice = Invoice::create([
            'invoice_number' => Invoice::generateNextInvoiceNumber(),
            'client_id' => $this->client_id,
            'issue_date' => Carbon::now(),
            'due_date' => $this->expiry_date,
            'subtotal' => $this->selling_price,
            'discount' => 0.00,
            'tax' => 0.00,
            'total' => $this->selling_price,
            'paid_amount' => 0.00,
            'currency' => $this->currency,
            'status' => 'sent',
            'payment_method' => 'FIB / FastPay / کاش',
            'notes' => "وەسڵی نوێکردنەوەی ساڵانەی: {$this->name} ({$this->domain_name})",
            'terms' => 'تکایە پێش بەرواری بەسەرچوون گوژمەکە پاکتاو بکەن.',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => "نوێکردنەوەی ساڵانەی {$this->name} ({$this->type_label})",
            'quantity' => 1,
            'unit_price' => $this->selling_price,
            'total_price' => $this->selling_price,
            'service_type' => $this->type,
        ]);

        return $invoice;
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
        $price = rtrim(rtrim(number_format((float) $this->selling_price, 2, '.', ''), '0'), '.');
        $iqd = number_format((float) $this->selling_price * (float) config('app.usd_to_iqd', 1500));
        $expired = $this->days_until_expiry < 0;

        if ($lang === 'ar') {
            $what = match ($this->type) {
                'domain' => 'النطاق',
                'email' => 'البريد الرسمي',
                'bundle' => 'النطاق والاستضافة',
                default => 'الاستضافة',
            };
            $verb = $expired ? 'انتهى بتاريخ' : 'ينتهي بتاريخ';

            return "مرحباً {$clientName}،\nنود تذكيركم بأن {$what} ({$target}) {$verb} {$date}.\nمبلغ التجديد: \${$price} (≈ {$iqd} د.ع)\nالدفع عبر FIB أو FastPay أو نقداً.\n— iCode Group";
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

        return "سڵاو بەڕێز {$clientName}،\n{$what} {$target} {$when}.\n{$warn}\nبڕی نوێکردنەوە: \${$price} (≈ {$iqd} د.ع)\nپارەدان: FIB · FastPay · کاش\n— iCode Group";
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
        return match ($this->billing_cycle) {
            'monthly' => (float) $this->selling_price,
            'quarterly' => (float) ($this->selling_price / 3),
            'semi_annual' => (float) ($this->selling_price / 6),
            'annual' => (float) ($this->selling_price / 12),
            'biennial' => (float) ($this->selling_price / 24),
            default => (float) ($this->selling_price / 12),
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
