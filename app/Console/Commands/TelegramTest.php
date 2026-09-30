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
            $chats = $telegram->recentChats();
            if (empty($chats)) {
                $this->warn('No chats found. Send /start to your bot in Telegram, then run this again.');

                return self::FAILURE;
            }

            $this->info('Put one of these in .env as TELEGRAM_CHAT_ID:');
            $this->table(['Chat ID', 'Name'], $chats);

            return self::SUCCESS;
        }

        $sent = $telegram->send("✅ <b>iCode Hub</b>\nپەیوەندی لەگەڵ تێلێگرام سەرکەوتوو بوو. ئاگادارییەکانی نوێکردنەوە لێرە دەگەن.");

        $sent ? $this->info('Test message sent.') : $this->error('Sending failed. Check the token and chat ID (details in storage/logs).');

        return $sent ? self::SUCCESS : self::FAILURE;
    }
}
