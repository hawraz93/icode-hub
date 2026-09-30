<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'business_name',
        'email',
        'phone',
        'whatsapp',
        'address',
        'city',
        'notes',
        'status',
        'portal_access_code',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->business_name ? "{$this->business_name} ({$this->name})" : $this->name;
    }

    /**
     * International digits-only number for wa.me links (prefers the WhatsApp field,
     * converts local Iraqi numbers like 0750... to 964750...).
     */
    public function getWhatsappNumberAttribute(): ?string
    {
        $digits = preg_replace('/\D/', '', $this->whatsapp ?: ($this->phone ?? ''));
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '07')) {
            $digits = '964' . substr($digits, 1);
        }

        return $digits;
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->invoices()->where('status', 'paid')->sum('total');
    }

    public function getPendingAmountAttribute(): float
    {
        return (float) $this->invoices()->whereIn('status', ['sent', 'partial', 'overdue'])->sum('total');
    }
}
