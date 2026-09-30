<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotifier
{
    private const MAX_LENGTH = 4000;

    /** Telegram's description of the last failed call, e.g. "Unauthorized" or "Bad Request: chat not found". */
    public ?string $lastError = null;

    public function __construct(
        private ?string $token = null,
        private ?string $chatId = null,
    ) {
        $this->token ??= config('services.telegram.bot_token');
        $this->chatId ??= config('services.telegram.chat_id');
    }

    public function isConfigured(): bool
    {
        return filled($this->token) && filled($this->chatId);
    }

    public function chatId(): ?string
    {
        return $this->chatId;
    }

    /**
     * Send an HTML-formatted message, splitting on line breaks when it exceeds Telegram's limit.
     */
    public function send(string $html): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        foreach ($this->chunks($html) as $chunk) {
            $response = Http::timeout(15)->asForm()->post($this->endpoint('sendMessage'), [
                'chat_id' => $this->chatId,
                'text' => $chunk,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => 'true',
            ]);

            if (! $response->successful()) {
                $this->lastError = $response->json('description') ?? "HTTP {$response->status()}";
                Log::warning('Telegram send failed', ['status' => $response->status(), 'body' => $response->body()]);

                return false;
            }
        }

        return true;
    }

    /**
     * Call any Bot API method. Returns the decoded "result" on success, null on failure.
     */
    public function call(string $method, array $params = []): mixed
    {
        if (blank($this->token)) {
            return null;
        }

        $response = Http::timeout(15)->asJson()->post($this->endpoint($method), $params);

        if (! $response->json('ok')) {
            $this->lastError = $response->json('description') ?? "HTTP {$response->status()}";
            Log::warning("Telegram {$method} failed", ['status' => $response->status(), 'body' => $response->body()]);

            return null;
        }

        return $response->json('result');
    }

    /**
     * Send one HTML message with optional inline buttons; returns the message ID.
     *
     * @param  array<int, array<int, array<string, string>>>|null  $keyboard  rows of inline buttons
     */
    public function sendWithButtons(string $html, ?array $keyboard = null, ?string $chatId = null): ?int
    {
        $result = $this->call('sendMessage', array_filter([
            'chat_id' => $chatId ?? $this->chatId,
            'text' => $html,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
            'reply_markup' => $keyboard ? ['inline_keyboard' => $keyboard] : null,
        ], fn ($v) => $v !== null));

        return $result['message_id'] ?? null;
    }

    public function editMessage(string|int $chatId, int $messageId, string $html, ?array $keyboard = null): bool
    {
        return $this->call('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $html,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
            'reply_markup' => ['inline_keyboard' => $keyboard ?? []],
        ]) !== null;
    }

    public function answerCallback(string $callbackId, ?string $text = null): void
    {
        $this->call('answerCallbackQuery', array_filter(['callback_query_id' => $callbackId, 'text' => $text]));
    }

    /**
     * Chats that have recently messaged the bot, used to discover TELEGRAM_CHAT_ID.
     *
     * @return array<int, array{id: string, name: string}>
     *
     * @throws \RuntimeException when Telegram rejects the request (bad token, webhook set, ...)
     */
    public function recentChats(): array
    {
        if (blank($this->token)) {
            return [];
        }

        $response = Http::timeout(15)->get($this->endpoint('getUpdates'));
        if (! $response->json('ok')) {
            throw new \RuntimeException($response->json('description') ?? "HTTP {$response->status()}", $response->status());
        }
        $updates = $response->json('result', []);

        return collect($updates)
            ->map(fn ($u) => $u['message']['chat'] ?? $u['my_chat_member']['chat'] ?? null)
            ->filter()
            ->unique('id')
            ->map(fn ($chat) => [
                'id' => (string) $chat['id'],
                'name' => trim(($chat['first_name'] ?? '') . ' ' . ($chat['last_name'] ?? '')) ?: ($chat['title'] ?? $chat['username'] ?? ''),
            ])
            ->values()
            ->all();
    }

    public static function escape(?string $text): string
    {
        return htmlspecialchars((string) $text, ENT_NOQUOTES, 'UTF-8');
    }

    private function endpoint(string $method): string
    {
        return "https://api.telegram.org/bot{$this->token}/{$method}";
    }

    /**
     * @return array<int, string>
     */
    private function chunks(string $text): array
    {
        if (mb_strlen($text) <= self::MAX_LENGTH) {
            return [$text];
        }

        $chunks = [];
        $current = '';
        foreach (explode("\n", $text) as $line) {
            if (mb_strlen($current) + mb_strlen($line) + 1 > self::MAX_LENGTH && $current !== '') {
                $chunks[] = $current;
                $current = '';
            }
            $current .= ($current === '' ? '' : "\n") . $line;
        }
        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }
}
