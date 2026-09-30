<?php

namespace App\Console\Commands;

use App\Services\TelegramNotifier;
use Illuminate\Console\Command;

class TelegramTest extends Command
{
    protected $signature = 'telegram:test';

    protected $description = 'Find your Telegram chat ID or send a test message to the configured chat';

    public function handle(TelegramNotifier $telegram): int
    {
        if (blank(config('services.telegram.bot_token'))) {
            $this->error('TELEGRAM_BOT_TOKEN is empty.');
            $this->line('1. In Telegram, open @BotFather and send /newbot');
            $this->line('2. Put the token it gives you in .env as TELEGRAM_BOT_TOKEN');
            $this->line('3. Send /start to your new bot, then run this command again');

            return self::FAILURE;
        }

        if (blank(config('services.telegram.chat_id'))) {
            try {
                $chats = $telegram->recentChats();
            } catch (\RuntimeException $e) {
                $this->error('Telegram rejected the token: ' . $e->getMessage());
                $this->line(match ($e->getCode()) {
                    401 => 'The token is wrong or was revoked. Copy the current one from @BotFather → /mybots → API Token.',
                    404 => 'The token is malformed. Make sure .env has exactly one TELEGRAM_BOT_TOKEN= line with no spaces or quotes.',
                    409 => 'A webhook is set on this bot. Remove it, or use a separate bot for iCode Hub.',
                    default => 'Check TELEGRAM_BOT_TOKEN in .env, then run `php artisan optimize:clear`.',
                });

                return self::FAILURE;
            }
            if (empty($chats)) {
                $this->warn('No chats found. Send /start to your bot in Telegram, then run this again.');

                return self::FAILURE;
            }

            $this->info('Put one of these in .env as TELEGRAM_CHAT_ID:');
            $this->table(['Chat ID', 'Name'], $chats);

            return self::SUCCESS;
        }

        $sent = $telegram->send("✅ <b>iCode Hub</b>\nپەیوەندی لەگەڵ تێلێگرام سەرکەوتوو بوو. ئاگادارییەکانی نوێکردنەوە لێرە دەگەن.");

        if ($sent) {
            $this->info('Test message sent.');
        } else {
            $this->error('Sending failed: ' . ($telegram->lastError ?? 'unknown error'));
            if (str_contains((string) $telegram->lastError, 'chat not found')) {
                $this->line('TELEGRAM_CHAT_ID is wrong, or you have not pressed Start in the bot. Clear TELEGRAM_CHAT_ID and run this again to find it.');
            }
        }

        return $sent ? self::SUCCESS : self::FAILURE;
    }
}
