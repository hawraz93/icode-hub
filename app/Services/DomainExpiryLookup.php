<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Reads a domain's registry expiry date over RDAP (free, no API key).
 */
class DomainExpiryLookup
{
    private const BOOTSTRAP_URL = 'https://data.iana.org/rdap/dns.json';

    /** Second-level labels that are part of the public suffix, e.g. edu.krd, com.iq. */
    private const SECOND_LEVEL = ['com', 'net', 'org', 'edu', 'gov', 'co', 'ac', 'mil', 'info', 'biz'];

    /**
     * Reduce "https://www.pos.example.com/path" to the registrable "example.com".
     */
    public static function registrableDomain(string $input): ?string
    {
        $host = strtolower(trim($input));
        $host = preg_replace('#^[a-z]+://#', '', $host);
        $host = preg_replace('#[/:?\#].*$#', '', $host);
        $host = trim($host, '.');

        $labels = array_values(array_filter(explode('.', $host)));
        if (count($labels) < 2) {
            return null;
        }

        $take = count($labels) >= 3 && in_array($labels[count($labels) - 2], self::SECOND_LEVEL, true) ? 3 : 2;

        return implode('.', array_slice($labels, -$take));
    }

    /**
     * @return array{supported: bool, expiry: ?Carbon}
     */
    public function lookup(string $domain): array
    {
        $domain = self::registrableDomain($domain);
        $base = $domain ? $this->serverFor(substr($domain, strrpos($domain, '.') + 1)) : null;

        if (! $base) {
            return ['supported' => false, 'expiry' => null];
        }

        $response = Http::timeout(15)
            ->accept('application/rdap+json')
            ->get(rtrim($base, '/') . '/domain/' . $domain);

        if (! $response->successful()) {
            return ['supported' => true, 'expiry' => null];
        }

        $event = collect($response->json('events', []))->firstWhere('eventAction', 'expiration');

        return [
            'supported' => true,
            'expiry' => isset($event['eventDate']) ? Carbon::parse($event['eventDate'])->timezone(config('app.timezone'))->startOfDay() : null,
        ];
    }

    private function serverFor(string $tld): ?string
    {
        $services = Cache::remember('rdap.bootstrap', now()->addWeek(), function () {
            return Http::timeout(15)->get(self::BOOTSTRAP_URL)->json('services', []);
        });

        foreach ($services as [$tlds, $urls]) {
            if (in_array($tld, $tlds, true)) {
                return collect($urls)->first(fn ($u) => str_starts_with($u, 'https://')) ?? $urls[0] ?? null;
            }
        }

        return null;
    }
}
