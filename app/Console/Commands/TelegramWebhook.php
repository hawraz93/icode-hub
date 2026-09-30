<?php

namespace App\Console\Commands;

use App\Services\RenewalBot;
use App\Services\TelegramNotifier;
use Illuminate\Console\Command;

class TelegramWebhook extends Command
{
    protected $signature = 'telegram:webhook {--delete : Disconnect the bot from this site}';

    protected $description = 'Connect the Telegram bot to this site so its buttons and messages work';

    public function handle(TelegramNotifier $telegram): int
    {
        if ($this->option('delete')) {
            $ok = $telegram->call('deleteWebhook') !== null;
            $ok ? $this->info('Webhook removed.') : $this->error('Failed: ' . $telegram->lastError);

            return $ok ? self::SUCCESS : self::FAILURE;
        }

        if (blank(config('services.telegram.chat_id'))) {
            $this->error('Set TELEGRAM_CHAT_ID first (run `php artisan telegram:test`), then run this again.');

            return self::FAILURE;
        }

        $url = route('telegram.webhook');
        if (! str_starts_with($url, 'https://')) {
            $this->error("Telegram needs HTTPS, but the webhook URL is {$url}. Set APP_URL=https://... in .env and run `php artisan optimize:clear`.");

            return self::FAILURE;
        }

        $ok = $telegram->call('setWebhook', [
            'url' => $url,
            'secret_token' => RenewalBot::webhookSecret(),
            'allowed_updates' => ['message', 'callback_query'],
        ]) !== null;

        if (! $ok) {
            $this->error('Failed: ' . $telegram->lastError);

            return self::FAILURE;
        }

        $info = $telegram->call('getWebhookInfo') ?? [];
        $this->info("Connected: {$url}");
        if (! empty($info['last_error_message'])) {
            $this->warn('Telegram reports: ' . $info['last_error_message']);
        }
        $telegram->sendWithButtons("🔗 <b>iCode Hub</b> پەیوەست بوو.\nئێستا دوگمەکان کاردەکەن. بنووسە /help بۆ ڕێنمایی.");

        return self::SUCCESS;
    }
}
