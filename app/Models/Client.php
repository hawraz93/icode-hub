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

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->invoices()->where('status', 'paid')->sum('total');
    }

    public function getPendingAmountAttribute(): float
    {
        return (float) $this->invoices()->whereIn('status', ['sent', 'partial', 'overdue'])->sum('total');
    }
}
