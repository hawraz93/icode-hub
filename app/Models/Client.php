<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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

    /**
     * Existing client with the same phone/WhatsApp number (ignores spaces, dashes, 0 vs 964 prefix).
     */
    public static function findByPhone(?string $phone, ?int $exceptId = null): ?self
    {
        $wanted = (new self(['phone' => $phone]))->whatsapp_number;
        if (! $wanted) {
            return null;
        }

        return self::query()
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->where(fn ($q) => $q->whereNotNull('phone')->orWhereNotNull('whatsapp'))
            ->get()
            ->first(fn (self $c) => $c->whatsapp_number === $wanted
                || ($c->phone && (new self(['phone' => $c->phone]))->whatsapp_number === $wanted));
    }

    /** @return array<string, float> net money received per currency (partial payments included) */
    public function getPaidTotalsAttribute(): array
    {
        return \App\Support\Money::totals($this->invoices()->where('status', '!=', 'cancelled')->get(), fn ($i) => $i->paid_amount, fn ($i) => $i->currency);
    }

    /** @return array<string, float> outstanding invoice balances per currency */
    public function getPendingTotalsAttribute(): array
    {
        return \App\Support\Money::totals($this->invoices()->open()->get(), fn ($i) => $i->remaining_balance, fn ($i) => $i->currency);
    }

    // ------------------------------------------------------------- portal login

    public static function hashPortalToken(string $token): string
    {
        return hash('sha256', trim($token));
    }

    /**
     * Issue a new portal login code. Only its hash is stored; the plain code is returned once
     * for the admin to send to the client. The old code (hashed or legacy plaintext) stops working.
     */
    public function rotatePortalToken(): string
    {
        $token = 'ICP-' . strtoupper(Str::random(8)) . '-' . Str::random(24);

        $this->forceFill([
            'portal_token_hash' => self::hashPortalToken($token),
            'portal_token_rotated_at' => now(),
            'portal_access_code' => null,
        ])->save();

        return $token;
    }

    public function revokePortalAccess(): void
    {
        $this->forceFill(['portal_token_hash' => null, 'portal_access_code' => null, 'portal_token_rotated_at' => now()])->save();
    }

    /**
     * Exact match only: a new hashed token, or (until rotated) the legacy plaintext code.
     * Phone numbers and partial codes never log anyone in.
     */
    public static function findByPortalCode(string $code): ?self
    {
        $code = trim($code);
        if (mb_strlen($code) < 6) {
            return null;
        }

        $client = self::where('portal_token_hash', self::hashPortalToken($code))->first();
        if (! $client && config('portal.allow_legacy_codes', true)) {
            $client = self::whereNotNull('portal_access_code')->where('portal_access_code', $code)->first();
            // Case-sensitive check (MySQL collations compare case-insensitively).
            $client = $client && hash_equals((string) $client->portal_access_code, $code) ? $client : null;
        }

        return $client && $client->status === 'active' ? $client : null;
    }

    public function getHasPortalAccessAttribute(): bool
    {
        return (bool) ($this->portal_token_hash || $this->portal_access_code);
    }
}
