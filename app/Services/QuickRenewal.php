<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Subscription;
use App\Support\RenewalLineParser;
use Carbon\Carbon;

/**
 * Builds and saves subscriptions from one-line input (web paste box and Telegram).
 */
class QuickRenewal
{
    public function __construct(private DomainExpiryLookup $rdap)
    {
    }

    /**
     * @return array<string, mixed>|null  editable draft, or null for a blank line
     */
    public function draft(string $line): ?array
    {
        $p = RenewalLineParser::parse($line);
        if (! $p) {
            return null;
        }

        $registry = null;
        $registrar = null;
        if ($p['domain'] && in_array($p['type'], ['domain', 'bundle'], true)) {
            try {
                $r = $this->rdap->lookup($p['domain']);
                $registry = $r['expiry'] ?? null;
                $registrar = $r['registrar'] ?? null;
            } catch (\Throwable) {
                // Offline or registry down: fall back to the typed date.
            }
        }

        $typed = array_map(fn (Carbon $d) => $d->toDateString(), $p['dates']);
        $expiry = $registry?->toDateString() ?? ($typed[0] ?? null);
        $dateSource = match (true) {
            $registry !== null => 'registry',
            count($typed) > 1 => 'ambiguous',
            count($typed) === 1 => 'typed',
            default => 'missing',
        };

        $client = $this->guessClient($p['domain'], $p['client_hint']);

        return [
            'raw' => $p['raw'],
            'domain' => $p['domain'],
            'name' => self::serviceName($p['type'], $p['domain'] ?? $p['name']),
            'type' => $p['type'],
            'amount' => $p['amount'],
            'currency' => $p['currency'],
            'expiry' => $expiry,
            'date_options' => $typed,
            'date_source' => $dateSource,
            'typed_differs' => $registry !== null && $typed !== [] && ! in_array($registry->toDateString(), $typed, true),
            'provider' => $registrar ?? self::providerFor($p['domain']),
            'registry_expiry' => $registry?->toDateString(),
            'client_id' => $client?->id,
            'client_hint' => $p['client_hint'],
            'paid' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     */
    public function save(array $draft, int $clientId): Subscription
    {
        $expiry = Carbon::parse($draft['expiry']);
        $currency = $draft['currency'] ?? 'USD';

        return Subscription::create([
            'client_id' => $clientId,
            'name' => $draft['name'],
            'type' => $draft['type'],
            'domain_name' => $draft['domain'],
            'provider' => $draft['provider'],
            'cost_price' => 0,
            'selling_price' => (float) ($draft['amount'] ?? 0),
            'currency' => $currency,
            'billing_cycle' => 'annual',
            'start_date' => $expiry->copy()->subYear()->toDateString(),
            'expiry_date' => $expiry->toDateString(),
            'registry_expiry_date' => $draft['registry_expiry'] ?? null,
            'registry_checked_at' => ($draft['registry_expiry'] ?? null) ? now() : null,
            'auto_renew' => false,
            'status' => $expiry->isPast() ? 'expired' : 'active',
            'reminder_days_before' => 30,
        ]);
    }

    public static function serviceName(string $type, string $target): string
    {
        return match ($type) {
            'hosting' => "هۆستینگی {$target}",
            'email' => "ئیمەیڵی بزنس ({$target})",
            'vps' => "سێرڤەری تایبەت ({$target})",
            default => "دۆمەینی {$target}",
        };
    }

    public static function providerFor(?string $domain): ?string
    {
        if (! $domain) {
            return null;
        }

        return match (true) {
            str_ends_with($domain, '.iq') => 'IQ Registry (CMC)',
            str_ends_with($domain, '.krd') => 'KRD Registry',
            default => null,
        };
    }

    /**
     * Same domain already on file -> same client; otherwise match the typed name.
     */
    private function guessClient(?string $domain, ?string $hint): ?Client
    {
        if ($domain) {
            $base = DomainExpiryLookup::registrableDomain($domain);
            $existing = Subscription::with('client')
                ->where('domain_name', 'like', "%{$base}")
                ->latest()
                ->first();
            if ($existing?->client) {
                return $existing->client;
            }

            $byEmail = Client::where('email', 'like', "%@{$base}")->first();
            if ($byEmail) {
                return $byEmail;
            }
        }

        if ($hint && mb_strlen($hint) >= 3) {
            return Client::where('name', 'like', "%{$hint}%")
                ->orWhere('business_name', 'like', "%{$hint}%")
                ->first();
        }

        return null;
    }
}
