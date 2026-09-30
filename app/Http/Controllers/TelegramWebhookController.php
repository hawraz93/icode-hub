<?php

namespace App\Http\Controllers;

use App\Services\RenewalBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, RenewalBot $bot)
    {
        // Telegram echoes the secret we registered with setWebhook in this header.
        if (! hash_equals(RenewalBot::webhookSecret(), (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            abort(403);
        }

        try {
            $bot->handleUpdate($request->all());
        } catch (\Throwable $e) {
            // Always answer 200, otherwise Telegram retries the same update forever.
            Log::error('Telegram webhook failed', ['error' => $e->getMessage()]);
        }

        return response()->json(['ok' => true]);
    }
}
