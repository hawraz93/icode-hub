<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotifier
{
    private const MAX_LENGTH = 4000;

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
                Log::warning('Telegram send failed', ['status' => $response->status(), 'body' => $response->body()]);

                return false;
            }
        }

        return true;
    }

    /**
     * Chats that have recently messaged the bot, used to discover TELEGRAM_CHAT_ID.
     *
     * @return array<int, array{id: string, name: string}>
     */
    public function recentChats(): array
    {
        if (blank($this->token)) {
            return [];
        }

        $updates = Http::timeout(15)->get($this->endpoint('getUpdates'))->json('result', []);

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
