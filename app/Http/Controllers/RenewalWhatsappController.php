<?php

namespace App\Http\Controllers;

use App\Models\ActivityReminder;
use App\Models\Subscription;
use Illuminate\Http\Request;

/**
 * Target of the "📤" button in Telegram (signed URL, no login needed):
 * records that the client was notified, then opens WhatsApp with the message.
 */
class RenewalWhatsappController extends Controller
{
    public function __invoke(Request $request, Subscription $subscription)
    {
        $lang = $request->query('lang') === 'ar' ? 'ar' : 'ku';
        $url = $subscription->whatsappUrl($lang);
        abort_unless($url, 404, 'ئەم کڕیارە ژمارەی واتسئاپی نییە.');

        if ($subscription->renewal_stage < Subscription::STAGE_NOTIFIED) {
            $subscription->update(['renewal_stage' => Subscription::STAGE_NOTIFIED, 'stage_updated_at' => now()]);
        }

        ActivityReminder::create([
            'client_id' => $subscription->client_id,
            'subscription_id' => $subscription->id,
            'type' => 'subscription_renewal',
            'channel' => 'whatsapp',
            'recipient' => $subscription->client?->whatsapp_number,
            'message' => $subscription->whatsappMessage($lang),
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return redirect()->away($url);
    }
}
