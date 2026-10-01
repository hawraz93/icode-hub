<?php

namespace App\Livewire\Admin;

use App\Models\ActivityReminder;
use App\Models\Server;
use App\Models\Subscription;
use App\Support\Money;
use Livewire\Attributes\Url;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

class RenewalRadar extends Component
{
    use WireUiActions;

    public const WINDOW_DAYS = 90;

    public const FILTERS = [
        'all' => ['label' => 'هەمووی', 'types' => null],
        'domain' => ['label' => 'دۆمەین', 'types' => ['domain', 'bundle']],
        'hosting' => ['label' => 'هۆستینگ', 'types' => ['hosting', 'bundle', 'vps']],
        'email' => ['label' => 'ئیمەیڵی بزنس', 'types' => ['email']],
        'other' => ['label' => 'هی تر', 'types' => ['license', 'maintenance', 'other']],
    ];

    public const URGENCY = [
        'late' => ['title' => 'بەسەرچووە', 'hex' => '#e11d48', 'soft' => 'bg-rose-50 text-rose-700', 'dot' => 'bg-rose-600'],
        'week' => ['title' => 'ئەم هەفتەیە', 'hex' => '#ea580c', 'soft' => 'bg-orange-50 text-orange-700', 'dot' => 'bg-orange-500'],
        'month' => ['title' => 'ئەم مانگە', 'hex' => '#d97706', 'soft' => 'bg-amber-50 text-amber-700', 'dot' => 'bg-amber-500'],
        'later' => ['title' => 'دواتر', 'hex' => '#059669', 'soft' => 'bg-emerald-50 text-emerald-700', 'dot' => 'bg-emerald-500'],
    ];

    public string $filter = 'all';

    #[Url(as: 'open', except: null)]
    public ?int $selectedId = null;

    public static function urgencyOf(int $days): string
    {
        return match (true) {
            $days < 0 => 'late',
            $days <= 7 => 'week',
            $days <= 30 => 'month',
            default => 'later',
        };
    }

    public function setFilter(string $filter): void
    {
        $this->filter = array_key_exists($filter, self::FILTERS) ? $filter : 'all';
    }

    /**
     * Tapping a completed step undoes it (and everything after); tapping a pending step completes it.
     */
    public function setStage(int $id, int $stage): void
    {
        $sub = Subscription::findOrFail($id);
        $stage = max(Subscription::STAGE_NOTIFIED, min(Subscription::STAGE_PAID, $stage));
        $new = $sub->renewal_stage >= $stage ? $stage - 1 : $stage;

        $sub->update(['renewal_stage' => $new, 'stage_updated_at' => now()]);

        if ($new === Subscription::STAGE_PAID) {
            $this->log($sub, 'renewal_paid', 'system', 'پارەی نوێکردنەوە وەرگیرا');
        }
    }

    public function markNotified(int $id, string $lang = 'ku'): void
    {
        $sub = Subscription::with('client')->findOrFail($id);

        if ($sub->renewal_stage < Subscription::STAGE_NOTIFIED) {
            $sub->update(['renewal_stage' => Subscription::STAGE_NOTIFIED, 'stage_updated_at' => now()]);
        }

        $this->log($sub, 'subscription_renewal', 'whatsapp', $sub->whatsappMessage($lang), $sub->client?->whatsapp_number);
    }

    public function renew(int $id): void
    {
        $sub = Subscription::findOrFail($id);
        $newExpiry = $sub->renew();
        $this->selectedId = null;

        $this->notification()->send([
            'icon' => 'success',
            'title' => 'نوێکرایەوە',
            'description' => "«{$sub->name}» تا {$newExpiry->format('Y-m-d')} نوێکرایەوە.",
        ]);
    }

    public function useRegistryDate(int $id): void
    {
        $sub = Subscription::findOrFail($id);
        if (! $sub->registry_expiry_date) {
            return;
        }

        $sub->update([
            'expiry_date' => $sub->registry_expiry_date->format('Y-m-d'),
            'status' => $sub->registry_expiry_date->isPast() ? 'expired' : 'active',
        ]);

        $this->notification()->send([
            'icon' => 'success',
            'title' => 'بەروار نوێکرایەوە',
            'description' => "بەرواری «{$sub->name}» بوو بە {$sub->registry_expiry_date->format('Y-m-d')} (وەک تۆمارگە).",
        ]);
    }

    public function renewServer(int $id): void
    {
        $server = Server::findOrFail($id);
        $next = $server->renew();

        ActivityReminder::create([
            'server_id' => $server->id,
            'type' => 'server_renewed',
            'channel' => 'system',
            'message' => "نوێکرایەوە تا {$next->format('Y-m-d')}",
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->notification()->send([
            'icon' => 'success',
            'title' => 'سێرڤەر نوێکرایەوە',
            'description' => "{$server->name} تا {$next->format('Y-m-d')}.",
        ]);
    }

    public function createInvoice(int $id): void
    {
        $invoice = Subscription::findOrFail($id)->createRenewalInvoice();

        $this->notification()->send([
            'icon' => 'success',
            'title' => 'وەسڵ دروستکرا',
            'description' => "وەسڵی ژمارە {$invoice->invoice_number} دروستکرا.",
        ]);
    }

    public function cancel(int $id): void
    {
        $sub = Subscription::findOrFail($id);
        $sub->update(['status' => 'cancelled']);
        $this->log($sub, 'subscription_cancelled', 'system', 'کڕیار نایەوێت نوێی بکاتەوە');
        $this->selectedId = null;

        $this->notification()->send([
            'icon' => 'info',
            'title' => 'لە لیستەکە لابرا',
            'description' => "«{$sub->name}» وەک هەڵوەشاوە تۆمارکرا.",
        ]);
    }

    public function togglePaid(int $id): void
    {
        $sub = Subscription::findOrFail($id);
        $sub->is_paid ? $sub->markUnpaid() : $sub->markPaid();

        $this->notification()->send([
            'icon' => $sub->is_paid ? 'success' : 'warning',
            'title' => $sub->is_paid ? 'پارە وەرگیرا' : 'پارەی نەداوە',
            'description' => ($sub->client?->business_name ?: $sub->client?->name) . " · {$sub->selling_label}",
        ]);
    }
    private function log(Subscription $sub, string $type, string $channel, string $message, ?string $recipient = null): void
    {
        ActivityReminder::create([
            'client_id' => $sub->client_id,
            'subscription_id' => $sub->id,
            'type' => $type,
            'channel' => $channel,
            'recipient' => $recipient,
            'message' => $message,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function render()
    {
        $all = Subscription::with(['client', 'server'])
            ->openRenewals(self::WINDOW_DAYS)
            ->orderBy('expiry_date')
            ->get();

        $counts = collect(self::FILTERS)->map(fn ($f) => $f['types'] ? $all->whereIn('type', $f['types'])->count() : $all->count());

        $types = self::FILTERS[$this->filter]['types'];
        $visible = $types ? $all->whereIn('type', $types) : $all;
        $groups = collect(array_keys(self::URGENCY))
            ->mapWithKeys(fn ($k) => [$k => $visible->filter(fn ($s) => self::urgencyOf($s->days_until_expiry) === $k)->values()])
            ->filter(fn ($items) => $items->isNotEmpty());

        $next30 = $all->filter(fn ($s) => $s->days_until_expiry <= 30);
        $toCollect = $next30->where('renewal_stage', '<', Subscription::STAGE_PAID);
        $price = fn ($s) => $s->selling_price;
        $cost = fn ($s) => $s->cost_price;
        $cur = fn ($s) => $s->currency;

        // Clients who have not paid (simple flag, no due date), across all services
        $unpaid = Subscription::with('client')->unpaid()->orderBy('updated_at')->get();

        $summary = [
            'collect' => Money::totals($toCollect, $price, $cur),
            'collect_clients' => $toCollect->pluck('client_id')->unique()->count(),
            'pay' => Money::totals($next30, $cost, $cur),
            'pay_count' => $next30->count(),
            'profit' => Money::subtract(Money::totals($next30, $price, $cur), Money::totals($next30, $cost, $cur)),
            'unpaid' => Money::totals($unpaid, $price, $cur),
            'paid_not_renewed' => $all->where('renewal_stage', Subscription::STAGE_PAID)->count(),
        ];
        // Pins on the 90-day runway; items sharing a day stack upward.
        $seen = [];
        $pins = $all->map(function ($s) use (&$seen) {
            $day = max(0, min(self::WINDOW_DAYS, $s->days_until_expiry));
            $stack = $seen[$day] = ($seen[$day] ?? -1) + 1;

            return [
                'id' => $s->id,
                'right' => round($day / self::WINDOW_DAYS * 100, 2),
                'stack' => min($stack, 2),
                'hex' => self::URGENCY[self::urgencyOf($s->days_until_expiry)]['hex'],
                'label' => ($s->domain_name ?: $s->name) . ' · ' . $s->expiry_status_text,
            ];
        });

        $selected = $this->selectedId ? $all->firstWhere('id', $this->selectedId) ?? Subscription::with(['client', 'server'])->find($this->selectedId) : null;
        $history = $selected
            ? ActivityReminder::where('subscription_id', $selected->id)->latest()->take(4)->get()
            : collect();

        $servers = Server::where('status', 'active')
            ->whereDate('renewal_date', '<=', now()->addDays(30)->toDateString())
            ->orderBy('renewal_date')
            ->get();

        return view('livewire.admin.renewal-radar', [
            'servers' => $servers,
            'unpaid' => $unpaid,
            'groups' => $groups,
            'counts' => $counts,
            'summary' => $summary,
            'pins' => $pins,
            'selected' => $selected,
            'history' => $history,
            'isEmpty' => $all->isEmpty(),
        ])->layout('layouts.app', ['title' => 'ڕاداری نوێکردنەوە', 'header' => 'ڕاداری نوێکردنەوە']);
    }
}
