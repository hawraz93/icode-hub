<?php

namespace App\Services;

use App\Exceptions\FinanceException;
use App\Livewire\Admin\RenewalRadar;
use App\Models\ActivityReminder;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Telegram front-end for renewals: one card per subscription with action buttons,
 * plus quick-add by sending "domain price date" to the bot.
 *
 * Callback data (max 64 bytes):
 *   p:{id} toggle paid · r:{id} ask renew · R:{id} renew · x:{id} ask cancel · X:{id} cancel · b:{id} back
 *   s:{key}:{clientId} save draft · c:{key} pick client · d:{key}:{i} pick date
 */
class RenewalBot
{
    private const EMOJI = ['late' => '🔴', 'week' => '🟠', 'month' => '🟡', 'later' => '🟢'];

    private const DRAFT_TTL_HOURS = 24;

    public function __construct(
        private TelegramNotifier $telegram,
        private QuickRenewal $quick,
    ) {
    }

    public static function webhookSecret(): string
    {
        return substr(hash_hmac('sha256', 'telegram-webhook', (string) config('app.key')), 0, 48);
    }

    // ---------------------------------------------------------------- cards

    /**
     * @return array{0: string, 1: array<int, array<int, array<string, string>>>}
     */
    public function card(Subscription $sub, ?string $footer = null): array
    {
        $e = fn ($s) => TelegramNotifier::escape($s);
        $sub->loadMissing('client');
        $client = $sub->client?->business_name ?: $sub->client?->name;
        $emoji = self::EMOJI[RenewalRadar::urgencyOf($sub->days_until_expiry)];
        $stage = Subscription::STAGE_LABELS[$sub->renewal_stage] ?? '';

        $text = "{$emoji} <code>" . $e($sub->domain_name ?: $sub->name) . '</code> · ' . $e($this->typeShort($sub->type)) . "\n"
            . $e($client) . "\n"
            . $e($sub->expiry_status_text) . ' · <b>' . $e($sub->selling_label) . "</b>\n"
            . '↳ ' . $e($stage);
        if ($footer) {
            $text .= "\n\n" . $footer;
        }

        return [$text, $this->cardButtons($sub)];
    }

    private function cardButtons(Subscription $sub): array
    {
        $rows = [];
        if ($sub->client?->whatsapp_number) {
            $rows[] = [['text' => '📤 نامەی واتسئاپ بۆ کڕیار', 'url' => self::whatsappLink($sub)]];
        }
        $paid = $sub->renewal_stage >= Subscription::STAGE_PAID;
        $rows[] = [
            ['text' => $paid ? '↩️ پارە نەدراوە' : '💰 پارەی دا', 'callback_data' => "p:{$sub->id}"],
            ['text' => '✅ نوێکرایەوە', 'callback_data' => "r:{$sub->id}"],
            ['text' => '❌ نایەوێت', 'callback_data' => "x:{$sub->id}"],
        ];

        return $rows;
    }

    /**
     * Signed link that marks the client as notified, then opens WhatsApp with the message.
     */
    public static function whatsappLink(Subscription $sub, string $lang = 'ku'): string
    {
        return URL::temporarySignedRoute('renewals.whatsapp', now()->addDays(14), ['subscription' => $sub->id, 'lang' => $lang]);
    }

    /**
     * @return array{0: string, 1: array<int, array<int, array<string, string>>>}
     */
    public function invoiceCard(Invoice $inv): array
    {
        $e = fn ($s) => TelegramNotifier::escape($s);
        $inv->loadMissing('client', 'subscription');
        $today = \Carbon\Carbon::today();
        $when = match (true) {
            $inv->due_date->lt($today) => '🔴 ' . $inv->due_date->diffInDays($today) . ' ڕۆژ دواکەوتووە',
            $inv->due_date->isToday() => '🟠 ئەمڕۆ',
            default => '🟡 ماوە ' . $today->diffInDays($inv->due_date) . ' ڕۆژ',
        };

        $text = '💳 <b>' . $e($inv->client?->business_name ?: $inv->client?->name) . "</b>\n"
            . ($inv->subscription?->domain_name ? '<code>' . $e($inv->subscription->domain_name) . "</code>\n" : '')
            . 'بڕ: <b>' . $e(Subscription::formatAmount($inv->remaining_balance, $inv->currency)) . '</b> · کاتی دان ' . $inv->due_date->format('Y-m-d') . "\n"
            . $when;

        $row = [];
        if ($url = $inv->paymentReminderUrl()) {
            $row[] = ['text' => '📤 بیرخستنەوە', 'url' => $url];
        }
        $row[] = ['text' => '💰 وەرگیرا', 'callback_data' => "v:{$inv->id}"];

        return [$text, [$row]];
    }

    /**
     * Card for a client who has not paid, with a one-tap "paid" button.
     */
    public function unpaidCard(Subscription $sub): array
    {
        $e = fn ($s) => TelegramNotifier::escape($s);
        $sub->loadMissing('client');

        $text = '🔴 <b>' . $e($sub->client?->business_name ?: $sub->client?->name) . '</b> پارەی نەداوە' . "\n"
            . '<code>' . $e($sub->domain_name ?: $sub->name) . '</code> · <b>' . $e($sub->selling_label) . '</b>';

        $row = [];
        if ($sub->client?->whatsapp_number) {
            $row[] = ['text' => '📤 واتسئاپ', 'url' => "https://wa.me/{$sub->client->whatsapp_number}"];
        }
        $row[] = ['text' => '💰 دای', 'callback_data' => "u:{$sub->id}"];

        return [$text, [$row]];
    }

    public function sendUnpaidCard(Subscription $sub): ?int
    {
        [$text, $keyboard] = $this->unpaidCard($sub);

        return $this->telegram->sendWithButtons($text, $keyboard);
    }

    public function sendInvoiceCard(Invoice $inv): ?int
    {
        [$text, $keyboard] = $this->invoiceCard($inv);

        return $this->telegram->sendWithButtons($text, $keyboard);
    }

    public function sendCard(Subscription $sub): ?int
    {
        [$text, $keyboard] = $this->card($sub);

        return $this->telegram->sendWithButtons($text, $keyboard);
    }

    // ------------------------------------------------------------- webhook

    public function handleUpdate(array $update): void
    {
        if (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);
        } elseif (isset($update['message']['text'])) {
            $this->handleMessage($update['message']);
        }
    }

    private function authorized(array $chat): bool
    {
        return (string) ($chat['id'] ?? '') === (string) config('services.telegram.chat_id');
    }

    private function handleCallback(array $cq): void
    {
        $chatId = $cq['message']['chat']['id'] ?? null;
        $messageId = $cq['message']['message_id'] ?? null;

        if (! $this->authorized($cq['message']['chat'] ?? []) || ! $messageId) {
            $this->telegram->answerCallback($cq['id'], 'ڕێگەپێنەدراوە');

            return;
        }

        $parts = explode(':', (string) ($cq['data'] ?? ''));
        $action = $parts[0] ?? '';

        if ($action === 'u') {
            $sub = Subscription::with('client')->find((int) ($parts[1] ?? 0));
            if ($sub && ! $sub->is_paid) {
                $sub->markPaid();
            }
            $this->telegram->answerCallback($cq['id'], $sub ? '💰 تۆمارکرا' : 'نەدۆزرایەوە');
            if ($sub) {
                $this->telegram->editMessage($chatId, $messageId,
                    '✅ ' . TelegramNotifier::escape($sub->client?->business_name ?: $sub->client?->name)
                    . ' پارەی داوە · <b>' . TelegramNotifier::escape($sub->selling_label) . '</b>');
            }

            return;
        }

        if ($action === 'v') {
            $inv = Invoice::with('client')->find((int) ($parts[1] ?? 0));
            $payment = null;
            $error = null;
            if ($inv) {
                try {
                    // Same rule as the web: only the remaining balance; a repeated tap records nothing.
                    $payment = $inv->markPaid(null, 'telegram', "tg:v:{$inv->id}:{$messageId}");
                } catch (FinanceException $e) {
                    $error = $e->getMessage();
                }
            }
            $this->telegram->answerCallback($cq['id'], match (true) {
                ! $inv => 'نەدۆزرایەوە',
                $error !== null => Str::limit($error, 190),
                $payment === null => 'پێشتر تۆمارکرابوو',
                default => '💰 تۆمارکرا',
            });
            if ($inv && $error === null) {
                $this->telegram->editMessage($chatId, $messageId,
                    '✅ ' . ($payment ? 'پارەی <b>' . TelegramNotifier::escape(Subscription::formatAmount((float) $payment->amount, $payment->currency)) . '</b> وەرگیرا' : 'وەسڵەکە پێشتر پاکتاو کرابوو') . ' · '
                    . TelegramNotifier::escape($inv->client?->business_name ?: $inv->client?->name));
            }

            return;
        }

        if (in_array($action, ['s', 'c', 'd'], true)) {
            $this->handleDraftCallback($cq, $parts, $chatId, $messageId);

            return;
        }

        $sub = Subscription::with('client')->find((int) ($parts[1] ?? 0));
        if (! $sub) {
            $this->telegram->answerCallback($cq['id'], 'ئەم تۆمارە نەماوە');
            $this->telegram->editMessage($chatId, $messageId, '🗑 ئەم خزمەتگوزارییە سڕاوەتەوە.');

            return;
        }

        $e = fn ($s) => TelegramNotifier::escape($s);

        switch ($action) {
            case 'p':
                $paid = $sub->renewal_stage >= Subscription::STAGE_PAID;
                $sub->update(['renewal_stage' => $paid ? Subscription::STAGE_NOTIFIED : Subscription::STAGE_PAID, 'stage_updated_at' => now()]);
                if (! $paid) {
                    $this->log($sub, 'renewal_paid', 'پارەی نوێکردنەوە وەرگیرا (تێلێگرام)');
                }
                $this->telegram->answerCallback($cq['id'], $paid ? 'گەڕایەوە' : '💰 تۆمارکرا');
                [$text, $kb] = $this->card($sub->refresh());
                $this->telegram->editMessage($chatId, $messageId, $text, $kb);
                break;

            case 'r':
            case 'x':
                [$text] = $this->card($sub);
                $question = $action === 'r'
                    ? '❓ لە ' . $e($sub->provider ?: 'دابینکەر') . ' نوێت کردەوە؟'
                    : '❓ کڕیار نایەوێت؟ لە لیستی نوێکردنەوە لادەبرێت.';
                $this->telegram->answerCallback($cq['id']);
                $this->telegram->editMessage($chatId, $messageId, $text . "\n\n" . $question, [[
                    ['text' => $action === 'r' ? '✅ بەڵێ، نوێکرایەوە' : '❌ بەڵێ، لایبە', 'callback_data' => strtoupper($action) . ":{$sub->id}"],
                    ['text' => '↩️ گەڕانەوە', 'callback_data' => "b:{$sub->id}"],
                ]]);
                break;

            case 'R':
                $newExpiry = $sub->renew();
                $this->telegram->answerCallback($cq['id'], '✅ نوێکرایەوە');
                $this->telegram->editMessage($chatId, $messageId,
                    '✅ <code>' . $e($sub->domain_name ?: $sub->name) . '</code> نوێکرایەوە تا <b>' . $newExpiry->format('Y-m-d') . '</b>'
                    . "\n" . $e($sub->client?->business_name ?: $sub->client?->name));
                break;

            case 'X':
                $sub->update(['status' => 'cancelled']);
                $this->log($sub, 'subscription_cancelled', 'کڕیار نایەوێت نوێی بکاتەوە (تێلێگرام)');
                $this->telegram->answerCallback($cq['id'], 'لابرا');
                $this->telegram->editMessage($chatId, $messageId,
                    '❌ <s>' . $e($sub->domain_name ?: $sub->name) . '</s> لە لیست لابرا (کڕیار نایەوێت)');
                break;

            default: // 'b' and anything unknown: show the card again
                $this->telegram->answerCallback($cq['id']);
                [$text, $kb] = $this->card($sub);
                $this->telegram->editMessage($chatId, $messageId, $text, $kb);
        }
    }

    private function handleMessage(array $message): void
    {
        $chat = $message['chat'] ?? [];
        if (! $this->authorized($chat)) {
            $this->telegram->sendWithButtons('ئەم بۆتە تایبەتە بە iCode Hub.', null, (string) ($chat['id'] ?? ''));

            return;
        }

        $text = trim($message['text']);

        if (in_array(mb_strtolower($text), ['/start', '/help', 'help', 'یارمەتی'], true)) {
            $this->telegram->sendWithButtons(
                "👋 <b>iCode Hub</b>\n\n"
                . "بۆ تۆمارکردنی دۆمەین تەنها بینووسە:\n<code>epochsp.com 100$</code>\n<code>ghsooncompany.com.iq 100k</code>\n\n"
                . "بەروار و دابینکەر خۆکار لە تۆمارگە دێن. دەتوانیت ناوی کڕیار و جۆر (hosting / email) ش زیاد بکەیت.\n\n"
                . '/today — نوێکردنەوەکانی ئەم هەفتەیە');

            return;
        }

        if (in_array(mb_strtolower($text), ['/today', 'ئەمڕۆ', 'today'], true)) {
            $subs = Subscription::with('client')->openRenewals(7)->orderBy('expiry_date')->get();
            if ($subs->isEmpty()) {
                $this->telegram->sendWithButtons('✨ هیچ شتێک لە ٧ ڕۆژی داهاتوودا بەسەرناچێت.');
            }
            $subs->each(fn ($s) => $this->sendCard($s));
            Invoice::open()
                ->whereDate('due_date', '<=', now()->addDays(7)->toDateString())->orderBy('due_date')->get()
                ->each(fn ($i) => $this->sendInvoiceCard($i));
            Subscription::with('client')->unpaid()->get()->each(fn ($s) => $this->sendUnpaidCard($s));

            return;
        }

        foreach (array_slice(preg_split('/\R/u', $text), 0, 20) as $line) {
            $draft = $this->quick->draft($line);
            if (! $draft || (! $draft['domain'] && ! $draft['amount'])) {
                continue;
            }
            $key = Str::lower(Str::random(8));
            Cache::put("tg:draft:{$key}", $draft, now()->addHours(self::DRAFT_TTL_HOURS));
            [$preview, $kb] = $this->draftCard($draft, $key);
            $this->telegram->sendWithButtons($preview, $kb);
        }
    }

    // --------------------------------------------------------------- drafts

    private function draftCard(array $draft, string $key, bool $pickClient = false): array
    {
        $e = fn ($s) => TelegramNotifier::escape($s);
        $client = $draft['client_id'] ? Client::find($draft['client_id']) : null;
        $price = $draft['amount'] !== null ? Subscription::formatAmount((float) $draft['amount'], $draft['currency']) : '—';
        $dateNote = match ($draft['date_source']) {
            'registry' => ' ✓ لە تۆمارگە',
            'ambiguous' => ' ⚠️ دڵنیا نیم، هەڵیبژێرە',
            'missing' => '',
            default => '',
        };

        $text = "🆕 <b>تۆمارکردنی نوێ</b>\n"
            . '<code>' . $e($draft['domain'] ?? $draft['name']) . '</code> · ' . $e($this->typeShort($draft['type'])) . "\n"
            . 'نرخ: <b>' . $e($price) . "</b>\n"
            . 'بەسەرچوون: <b>' . ($draft['expiry'] ?? '❓ نەزانراوە') . '</b>' . $dateNote . "\n"
            . 'دابینکەر: ' . $e($draft['provider'] ?? '—') . "\n"
            . 'کڕیار: ' . ($client ? '<b>' . $e($client->business_name ?: $client->name) . '</b>' : '❓ هەڵیبژێرە');
        if ($draft['typed_differs'] ?? false) {
            $text .= "\n⚠️ بەرواری نووسراو جیاوازە لە تۆمارگە، بەرواری تۆمارگە بەکارهات.";
        }

        $rows = [];
        if ($draft['date_source'] === 'ambiguous') {
            $rows[] = collect($draft['date_options'])->map(fn ($d, $i) => [
                'text' => ($d === $draft['expiry'] ? '● ' : '') . $d, 'callback_data' => "d:{$key}:{$i}",
            ])->all();
        }

        if ($pickClient || ! $client) {
            $clients = Client::where('status', 'active')->latest('updated_at')->take(8)->get();
            foreach ($clients->chunk(2) as $pair) {
                $rows[] = $pair->map(fn ($c) => [
                    'text' => Str::limit($c->business_name ?: $c->name, 24), 'callback_data' => "s:{$key}:{$c->id}",
                ])->values()->all();
            }
            if ($clients->isEmpty()) {
                $text .= "\n\nهیچ کڕیارێک نییە. سەرەتا لە سیستەمەکە کڕیار زیاد بکە.";
            }
        } elseif ($draft['expiry']) {
            $rows[] = [
                ['text' => '💾 تۆمارکردن', 'callback_data' => "s:{$key}:{$client->id}"],
                ['text' => '👤 کڕیاری تر', 'callback_data' => "c:{$key}"],
            ];
        }

        if (! $draft['expiry']) {
            $text .= "\n\n❓ بەروارەکە بنووسە، بۆ نموونە: <code>" . $e($draft['domain'] ?? $draft['name']) . ' 100$ 2027-04-01</code>';
        }

        return [$text, $rows];
    }

    private function handleDraftCallback(array $cq, array $parts, $chatId, int $messageId): void
    {
        $key = $parts[1] ?? '';
        $draft = Cache::get("tg:draft:{$key}");
        if (! $draft) {
            $this->telegram->answerCallback($cq['id'], 'ماوەی بەسەرچوو، دووبارە بینووسە');
            $this->telegram->editMessage($chatId, $messageId, '⌛ ئەم پێشنیارە بەسەرچوو. دێڕەکە دووبارە بنێرە.');

            return;
        }

        if ($parts[0] === 'd') {
            $draft['expiry'] = $draft['date_options'][(int) ($parts[2] ?? 0)] ?? $draft['expiry'];
            $draft['date_source'] = 'typed';
            Cache::put("tg:draft:{$key}", $draft, now()->addHours(self::DRAFT_TTL_HOURS));
            $this->telegram->answerCallback($cq['id']);
            [$text, $kb] = $this->draftCard($draft, $key);
            $this->telegram->editMessage($chatId, $messageId, $text, $kb);

            return;
        }

        if ($parts[0] === 'c') {
            $this->telegram->answerCallback($cq['id']);
            [$text, $kb] = $this->draftCard($draft, $key, pickClient: true);
            $this->telegram->editMessage($chatId, $messageId, $text, $kb);

            return;
        }

        // 's': save with the chosen client
        $client = Client::find((int) ($parts[2] ?? 0));
        if (! $client || ! $draft['expiry']) {
            $this->telegram->answerCallback($cq['id'], $client ? 'بەروار نییە' : 'کڕیار نەدۆزرایەوە');

            return;
        }

        $sub = $this->quick->save($draft, $client->id);
        Cache::forget("tg:draft:{$key}");
        $this->telegram->answerCallback($cq['id'], '💾 تۆمارکرا');
        [$text, $kb] = $this->card($sub->load('client'), '💾 <b>تۆمارکرا</b>');
        $this->telegram->editMessage($chatId, $messageId, $text, $kb);
    }

    // -------------------------------------------------------------- helpers

    private function log(Subscription $sub, string $type, string $message): void
    {
        ActivityReminder::create([
            'client_id' => $sub->client_id,
            'subscription_id' => $sub->id,
            'type' => $type,
            'channel' => 'telegram',
            'message' => $message,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function typeShort(string $type): string
    {
        return match ($type) {
            'domain' => 'دۆمەین',
            'hosting' => 'هۆستینگ',
            'bundle' => 'دۆمەین + هۆستینگ',
            'email' => 'ئیمەیڵی بزنس',
            'vps' => 'VPS',
            'license' => 'مۆڵەت',
            'maintenance' => 'پشتگیری',
            default => 'خزمەتگوزاری',
        };
    }
}
