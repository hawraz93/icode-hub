<?php

namespace App\Models;

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
        'auto_renew',
        'status',
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
    ];

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
