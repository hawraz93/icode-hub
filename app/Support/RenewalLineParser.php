<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Turns a loosely written line such as "epochsp.com  $100  1-4-2027" or
 * "ghsooncompany.com.iq 100k 1/9/2026 hosting Ghsoon Co" into structured renewal data.
 */
class RenewalLineParser
{
    private const TYPE_WORDS = [
        'hosting' => 'hosting', 'هۆستینگ' => 'hosting', 'host' => 'hosting',
        'email' => 'email', 'mail' => 'email', 'ئیمەیڵ' => 'email', 'ایمێڵ' => 'email',
        'domain' => 'domain', 'دۆمەین' => 'domain',
        'vps' => 'vps', 'server' => 'vps', 'سێرڤەر' => 'vps',
    ];

    /**
     * @return array{raw: string, domain: ?string, name: string, type: string, amount: ?float, currency: string, dates: array<int, Carbon>, client_hint: ?string}|null
     */
    public static function parse(string $line): ?array
    {
        $raw = trim($line);
        if ($raw === '') {
            return null;
        }

        $rest = ' ' . preg_replace('/\s+/u', ' ', $raw) . ' ';

        // Domain: first token that looks like a hostname.
        $domain = null;
        if (preg_match('/(?:https?:\/\/)?(?:www\.)?((?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,})/i', $rest, $m)) {
            $domain = strtolower($m[1]);
            $rest = str_replace($m[0], ' ', $rest);
        }

        // Date: 2027-04-01, 1-4-2027, 1/9/2026, 15.2.2027
        $dates = [];
        if (preg_match('/(\d{4})[\/\-.](\d{1,2})[\/\-.](\d{1,2})/', $rest, $m)) {
            $dates = self::validDates([[(int) $m[1], (int) $m[2], (int) $m[3]]]);
            $rest = str_replace($m[0], ' ', $rest);
        } elseif (preg_match('/(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2,4})/', $rest, $m)) {
            [$a, $b, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            $y = $y < 100 ? 2000 + $y : $y;
            // Day/month first (local habit), then month/day; invalid combinations drop out.
            $dates = self::validDates($a === $b ? [[$y, $b, $a]] : [[$y, $b, $a], [$y, $a, $b]]);
            $rest = str_replace($m[0], ' ', $rest);
        }

        // Amount and currency: $100, 100$, 100 usd, 100k, 100,000, 100000 IQD, 100 د.ع
        $amount = null;
        $currency = 'USD';
        if (preg_match('/(\$\s*)?(\d[\d,]*(?:\.\d+)?)\s*(k\b|K\b|\$|usd\b|iqd\b|د\.ع|دینار|دينار)?/iu', $rest, $m) && $m[2] !== '') {
            $number = (float) str_replace(',', '', $m[2]);
            $suffix = mb_strtolower($m[3] ?? '');
            if ($suffix === 'k') {
                $amount = $number * 1000;
                $currency = 'IQD';
            } elseif (in_array($suffix, ['iqd', 'د.ع', 'دینار', 'دينار'], true)) {
                $amount = $number;
                $currency = 'IQD';
            } elseif (($m[1] ?? '') !== '' || in_array($suffix, ['$', 'usd'], true)) {
                $amount = $number;
            } else {
                $amount = $number;
                $currency = $number >= 1000 ? 'IQD' : 'USD';
            }
            $rest = str_replace($m[0], ' ', $rest);
        }

        // Service type keywords
        $type = 'domain';
        foreach (self::TYPE_WORDS as $word => $mapped) {
            if (preg_match('/(^|\s)' . preg_quote($word, '/') . '(\s|$)/iu', $rest)) {
                $type = $mapped;
                $rest = preg_replace('/(^|\s)' . preg_quote($word, '/') . '(\s|$)/iu', ' ', $rest);
                break;
            }
        }

        $leftover = trim(preg_replace('/\s+/u', ' ', $rest));

        // A bare word like "univsul" with no domain becomes the service name.
        $name = $domain ?? ($leftover !== '' ? strtok($leftover, ' ') : $raw);
        if (! $domain && $leftover !== '') {
            $leftover = trim(substr($leftover, strlen($name)));
        }

        return [
            'raw' => $raw,
            'domain' => $domain,
            'name' => $name,
            'type' => $type,
            'amount' => $amount,
            'currency' => $currency,
            'dates' => $dates,
            'client_hint' => $leftover !== '' ? $leftover : null,
        ];
    }

    /**
     * @param  array<int, array{0: int, 1: int, 2: int}>  $candidates  [year, month, day]
     * @return array<int, Carbon>
     */
    private static function validDates(array $candidates): array
    {
        return collect($candidates)
            ->filter(fn ($c) => checkdate($c[1], $c[2], $c[0]))
            ->map(fn ($c) => Carbon::create($c[0], $c[1], $c[2])->startOfDay())
            ->unique(fn ($d) => $d->toDateString())
            ->values()
            ->all();
    }
}
