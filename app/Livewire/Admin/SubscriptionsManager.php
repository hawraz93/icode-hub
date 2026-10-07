<?php

namespace App\Livewire\Admin;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Server;
use App\Models\Subscription;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class SubscriptionsManager extends Component
{
    use WithPagination;
    use WireUiActions;

    public string $search = '';
    public string $typeFilter = 'all';
    #[Url(except: 'all')]
    public string $statusFilter = 'all';
    public string $yearFilter = 'all';

    // Modal Form Properties
    public bool $showModal = false;
    public ?int $editingId = null;

    public ?int $client_id = null;
    public ?int $server_id = null;
    public string $name = '';
    public string $type = 'hosting';
    public string $domain_name = '';
    public string $provider = 'Godaddy';
    public $cost_price = 0.00; // untyped: an emptied number input sends ""
    public $selling_price = 100.00; // untyped: an emptied number input sends ""
    public string $currency = 'USD';
    public string $billing_cycle = 'annual';
    public bool $is_paid = true;
    public ?string $start_date = null;
    public ?string $expiry_date = null;
    public bool $auto_renew = true;
    public string $status = 'active';
    public int $reminder_days_before = 30;
    public string $notes = '';

    // Quick Inline Client Modal
    public bool $showQuickClientModal = false;
    public string $quick_client_name = '';
    public string $quick_client_business_name = '';
    public string $quick_client_phone = '';
    public string $quick_client_city = 'هەولێر';

    protected function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'server_id' => 'nullable|exists:servers,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'domain_name' => 'nullable|string|max:255',
            'provider' => 'nullable|string|max:255',
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'currency' => 'required|in:USD,IQD',
            'billing_cycle' => 'required|in:monthly,quarterly,semi_annual,annual,biennial',
            'is_paid' => 'boolean',
            'start_date' => 'required|date',
            'expiry_date' => 'required|date|after_or_equal:start_date',
            'auto_renew' => 'boolean',
            'status' => 'required|in:active,expired,grace_period,cancelled',
            'reminder_days_before' => 'required|integer|min:1|max:90',
            'notes' => 'nullable|string',
        ];
    }

    public function updatedType(): void
    {
        $domain = strtolower((string) Subscription::normalizeDomain($this->domain_name));
        
        switch ($this->type) {
            case 'bundle':
                $this->name = $domain ? "دۆمەین و هۆستینگی {$domain}" : "دۆمەین و هۆستینگ";
                $this->provider = $this->provider ?: 'Namecheap';
                if ($this->selling_price == 0) $this->selling_price = 100.00;
                break;
            case 'domain':
                $this->name = $domain ? "دۆمەینی {$domain}" : "دۆمەین";
                $this->provider = $this->provider ?: 'Namecheap';
                $this->server_id = null;
                if ($this->selling_price == 0) $this->selling_price = 20.00;
                break;
            case 'hosting':
                $this->name = $domain ? "هۆستینگی {$domain}" : "هۆستینگی وێب";
                $defaultServer = Server::where('status', 'active')->first();
                $this->server_id = $this->server_id ?: $defaultServer?->id;
                if ($this->selling_price == 0) $this->selling_price = 80.00;
                break;
            case 'email':
                $this->name = $domain ? "ئیمەیڵی بزنس ({$domain})" : "ئیمەیڵی بزنس";
                $this->provider = 'Zoho / Google Workspace';
                if ($this->selling_price == 0) $this->selling_price = 35.00;
                break;
            case 'vps':
                $this->name = "سێرڤەری تایبەت (VPS)";
                $this->provider = 'Hetzner Cloud';
                if ($this->selling_price == 0) $this->selling_price = 250.00;
                break;
            case 'license':
                $this->name = "مۆڵەتنامەی بەرنامە (License)";
                $this->provider = 'iCode Group';
                $this->server_id = null;
                if ($this->selling_price == 0) $this->selling_price = 100.00;
                break;
            default:
                break;
        }
    }

    public function updatedDomainName(): void
    {
        $domain = strtolower((string) Subscription::normalizeDomain($this->domain_name));
        if ($domain) {
            // Auto fill or enhance service name based on type
            if ($this->type === 'domain') {
                $this->name = "دۆمەینی {$domain}";
            } elseif ($this->type === 'hosting') {
                $this->name = "هۆستینگی {$domain}";
            } elseif ($this->type === 'email') {
                $this->name = "ئیمەیڵی بزنس ({$domain})";
            } else {
                $this->name = "دۆمەین و هۆستینگی {$domain}";
            }

            // Auto detect provider
            if (str_ends_with($domain, '.krd') || str_ends_with($domain, '.iq')) {
                $this->provider = 'KRD Registry / دەرەکی';
            } elseif (empty($this->provider) || $this->provider === 'Namecheap') {
                $this->provider = 'Namecheap';
            }

            // Auto default server if empty
            if (empty($this->server_id) && in_array($this->type, ['bundle', 'hosting'])) {
                $defaultServer = Server::where('status', 'active')->first();
                if ($defaultServer) {
                    $this->server_id = $defaultServer->id;
                }
            }
        }
    }

    public function updatedStartDate(): void
    {
        $this->calculateExpiryDate();
    }

    public function updatedBillingCycle(): void
    {
        $this->calculateExpiryDate();
    }

    public function calculateExpiryDate(): void
    {
        if (!$this->start_date) {
            return;
        }

        try {
            $start = Carbon::parse($this->start_date);
            $this->expiry_date = match ($this->billing_cycle) {
                'monthly' => $start->copy()->addMonth()->format('Y-m-d'),
                'quarterly' => $start->copy()->addMonths(3)->format('Y-m-d'),
                'semi_annual' => $start->copy()->addMonths(6)->format('Y-m-d'),
                'biennial' => $start->copy()->addYears(2)->format('Y-m-d'),
                default => $start->copy()->addYear()->format('Y-m-d'),
            };
        } catch (\Exception $e) {
            // Keep existing date
        }
    }

    public function openQuickClientModal(): void
    {
        $this->quick_client_name = '';
        $this->quick_client_business_name = '';
        $this->quick_client_phone = '';
        $this->quick_client_city = 'هەولێر';
        $this->showQuickClientModal = true;
    }

    public function saveQuickClient(): void
    {
        $this->validate([
            'quick_client_name' => 'required|string|max:255',
            'quick_client_business_name' => 'nullable|string|max:255',
            'quick_client_phone' => 'required|string|max:50',
            'quick_client_city' => 'nullable|string|max:100',
        ]);

        // Same number already on file (e.g. a double tap while saving): select it instead of duplicating.
        if ($existing = Client::findByPhone($this->quick_client_phone)) {
            $this->client_id = $existing->id;
            $this->showQuickClientModal = false;
            $this->notification()->send([
                'icon' => 'info',
                'title' => 'کڕیار پێشتر هەبوو',
                'description' => "«{$existing->display_name}» هەمان ژمارەی هەیە و هەڵبژێردرا.",
            ]);

            return;
        }

        $client = Client::create([
            'name' => $this->quick_client_name,
            'business_name' => $this->quick_client_business_name,
            'phone' => $this->quick_client_phone,
            'whatsapp' => $this->quick_client_phone,
            'city' => $this->quick_client_city ?: 'هەولێر',
            'status' => 'active',
        ]);

        $this->client_id = $client->id;
        $this->showQuickClientModal = false;

        $this->notification()->send([
            'icon' => 'success',
            'title' => 'کڕیار زیادکرا!',
            'description' => "کڕیار «{$client->displayName}» بە سەرکەوتوویی زیادکرا و هەڵبژێردرا.",
        ]);
    }

    public function openModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $sub = Subscription::findOrFail($id);
        $this->editingId = $sub->id;
        $this->client_id = $sub->client_id;
        $this->server_id = $sub->server_id;
        $this->name = $sub->name;
        $this->type = $sub->type;
        $this->domain_name = $sub->domain_name ?? '';
        $this->provider = $sub->provider ?? '';
        $this->cost_price = (float) $sub->cost_price;
        $this->selling_price = (float) $sub->selling_price;
        $this->currency = $sub->currency;
        $this->billing_cycle = $sub->billing_cycle;
        $this->is_paid = (bool) $sub->is_paid;
        $this->start_date = $sub->start_date ? $sub->start_date->format('Y-m-d') : null;
        $this->expiry_date = $sub->expiry_date ? $sub->expiry_date->format('Y-m-d') : null;
        $this->auto_renew = (bool) $sub->auto_renew;
        $this->status = $sub->status;
        $this->reminder_days_before = (int) $sub->reminder_days_before;
        $this->notes = $sub->notes ?? '';

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            $sub = Subscription::findOrFail($this->editingId);
            $validated['paid_at'] = $validated['is_paid'] ? ($sub->paid_at ?? now()) : null;
            $sub->update($validated);
            $this->notification()->send([
                'icon' => 'success',
                'title' => 'نوێکرایەوە',
                'description' => 'زانیاری خزمەتگوزاری بە سەرکەوتوویی نوێکرایەوە.',
            ]);
        } else {
            $validated['paid_at'] = $validated['is_paid'] ? now() : null;
            Subscription::create($validated);
            $this->notification()->send([
                'icon' => 'success',
                'title' => 'تۆمارکرا',
                'description' => 'خزمەتگوزاری/دۆمەینی نوێ بە سەرکەوتوویی تۆمارکرا.',
            ]);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function togglePaid(int $id): void
    {
        $sub = Subscription::findOrFail($id);
        $sub->is_paid ? $sub->markUnpaid() : $sub->markPaid();

        $this->notification()->send([
            'icon' => $sub->is_paid ? 'success' : 'warning',
            'title' => $sub->is_paid ? 'پارە وەرگیرا' : 'پارەی نەداوە',
            'description' => "«{$sub->name}» · {$sub->selling_label}",
        ]);
    }

    public function createRenewalInvoice(int $id): void
    {
        $sub = Subscription::findOrFail($id);
        $invoiceNumber = $sub->createRenewalInvoice()->invoice_number;

        $this->notification()->send([
            'icon' => 'success',
            'title' => 'وەسڵ دروستکرا!',
            'description' => "وەسڵی نوێکردنەوە ژمارە {$invoiceNumber} بە سەرکەوتوویی دروستکرا.",
        ]);
    }

    public function checkUptime(int $id): void
    {
        $sub = Subscription::findOrFail($id);
        if (!$sub->domain_name) {
            $this->notification()->send([
                'icon' => 'warning',
                'title' => 'دۆمەین نییە',
                'description' => 'ئەم تۆمارە ناوی دۆمەینی تێدا نییە بۆ پشکنین.',
            ]);
            return;
        }

        $domain = trim($sub->domain_name);
        $url = str_starts_with($domain, 'http') ? $domain : "https://{$domain}";

        try {
            $start = microtime(true);
            $response = \Illuminate\Support\Facades\Http::timeout(5)->withoutVerifying()->get($url);
            $duration = round((microtime(true) - $start) * 1000);

            $statusCode = $response->status();
            $isUp = $response->successful() || $response->redirect();

            $statusText = $isUp ? "سەرکەوتوو (HTTP {$statusCode}) لە {$duration}ms" : "کێشە هەیە (HTTP {$statusCode})";

            $this->notification()->send([
                'icon' => $isUp ? 'success' : 'error',
                'title' => $isUp ? 'وێبسایتەکە بەردەستە و کاردەکات! 🟢' : 'وێبسایت بەردەست نییە! 🔴',
                'description' => "دۆخی {$domain}: {$statusText}",
            ]);
        } catch (\Exception $e) {
            $this->notification()->send([
                'icon' => 'error',
                'title' => 'پەیوەندی سەرکەوتوو نەبوو! 🔴',
                'description' => "دۆخی {$domain}: بەردەست نییە. هۆکار: " . Str::limit($e->getMessage(), 60),
            ]);
        }
    }

    public function confirmRenew(int $id): void
    {
        $sub = Subscription::find($id);
        if (!$sub) {
            return;
        }

        $cycleText = match ($sub->billing_cycle) {
            'monthly' => '١ مانگ',
            'quarterly' => '٣ مانگ',
            'semi_annual' => '٦ مانگ',
            'biennial' => '٢ ساڵ',
            default => '١ ساڵ',
        };

        $this->dialog()->confirm([
            'title' => 'نوێکردنەوەی خزمەتگوزاری',
            'description' => "ئایا دڵنیایت لە درێژکردنەوە و نوێکردنەوەی «{$sub->name}» بۆ ماوەی {$cycleText}ی تر؟ بەرواری بەسەرچوون خۆکار نوێ دەبێتەوە.",
            'icon' => 'question',
            'accept' => [
                'label' => 'بەڵێ، نوێی بکەرەوە',
                'method' => 'renewSubscription',
                'params' => $id,
                'color' => 'primary',
            ],
            'reject' => [
                'label' => 'پاشگەزبوونەوە',
            ],
        ]);
    }

    public function renewSubscription(int $id): void
    {
        $sub = Subscription::findOrFail($id);
        $newExpiry = $sub->renew();

        $this->notification()->send([
            'icon' => 'success',
            'title' => 'خزمەتگوزاری نوێکرایەوە! 🎉',
            'description' => "بەرواری بەسەرچوونی «{$sub->name}» درێژکرایەوە بۆ {$newExpiry->format('Y-m-d')}.",
        ]);
    }

    public function confirmDelete(int $id): void
    {
        $sub = Subscription::find($id);
        if (!$sub) {
            return;
        }

        $this->dialog()->confirm([
            'title' => 'سڕینەوەی خزمەتگوزاری',
            'description' => "ئایا دڵنیایت لە سڕینەوەی «{$sub->name}»؟",
            'icon' => 'error',
            'accept' => [
                'label' => 'بەڵێ، بیسڕەوە',
                'method' => 'delete',
                'params' => $id,
                'color' => 'negative',
            ],
            'reject' => [
                'label' => 'پەشیمانبوونەوە',
            ],
        ]);
    }

    public function delete(int $id): void
    {
        $sub = Subscription::find($id);
        if ($sub) {
            $sub->delete();

            $this->notification()->send([
                'icon' => 'success',
                'title' => 'سڕایەوە',
                'description' => 'خزمەتگوزاری بە سەرکەوتوویی سڕایەوە.',
            ]);
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->client_id = null;
        $defaultServer = Server::where('status', 'active')->first();
        $this->server_id = $defaultServer?->id;
        $this->name = '';
        $this->type = 'hosting';
        $this->domain_name = '';
        $this->provider = 'Namecheap';
        $this->cost_price = 0.00;
        $this->selling_price = 100.00;
        $this->currency = 'USD';
        $this->billing_cycle = 'annual';
        $this->is_paid = true;
        $this->start_date = Carbon::now()->format('Y-m-d');
        $this->expiry_date = Carbon::now()->addYear()->format('Y-m-d');
        $this->auto_renew = true;
        $this->status = 'active';
        $this->reminder_days_before = 30;
        $this->notes = '';
    }

    public function render()
    {
        $allActive = Subscription::where('status', 'active')->get();
        $price = fn ($s) => $s->selling_price;
        $cur = fn ($s) => $s->currency;
        $totalHostingRevenue = Money::totals($allActive->whereIn('type', ['hosting', 'bundle', 'vps']), $price, $cur);
        $totalDomainEmailRevenue = Money::totals($allActive->whereIn('type', ['domain', 'email']), $price, $cur);
        $unpaidTotals = Money::totals(Subscription::unpaid()->get(), $price, $cur);
        $unpaidCount = Subscription::unpaid()->count();
        // Extract available years for filter (Database agnostic)
        $availableYears = Subscription::whereNotNull('expiry_date')
            ->get()
            ->map(fn($s) => $s->expiry_date->format('Y'))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        if (empty($availableYears)) {
            $availableYears = ['2026', '2027', '2028'];
        }

        $query = Subscription::with(['client', 'server'])
            ->when($this->search, function ($q) {
                $q->where(function ($sq) {
                    $sq->where('name', 'like', "%{$this->search}%")
                        ->orWhere('domain_name', 'like', "%{$this->search}%")
                        ->orWhereHas('client', function ($cq) {
                            $cq->where('name', 'like', "%{$this->search}%")
                                ->orWhere('business_name', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->typeFilter !== 'all', fn($q) => $q->where('type', $this->typeFilter))
            ->when($this->statusFilter === 'unpaid', fn($q) => $q->where('is_paid', false)->where('status', '!=', 'cancelled'))
            ->when(! in_array($this->statusFilter, ['all', 'unpaid'], true), fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->yearFilter !== 'all', fn($q) => $q->whereYear('expiry_date', $this->yearFilter));

        $filteredSubscriptions = (clone $query)->get();
        $filteredProfit = Money::subtract(
            Money::totals($filteredSubscriptions, $price, $cur),
            Money::totals($filteredSubscriptions, fn ($s) => $s->cost_price, $cur),
        );

        $subscriptions = $query->orderBy('expiry_date', 'asc')->paginate(12);

        $clients = Client::where('status', 'active')->orderBy('name')->get();
        $servers = Server::where('status', 'active')->orderBy('name')->get();

        return view('livewire.admin.subscriptions-manager', [
            'subscriptions' => $subscriptions,
            'clients' => $clients,
            'servers' => $servers,
            'totalHostingRevenue' => $totalHostingRevenue,
            'totalDomainEmailRevenue' => $totalDomainEmailRevenue,
            'unpaidTotals' => $unpaidTotals,
            'unpaidCount' => $unpaidCount,
            'availableYears' => $availableYears,
            'filteredProfit' => $filteredProfit,
        ])->layout('layouts.app', ['title' => 'بەڕێوەبردنی هۆستینگ و دۆمەین', 'header' => 'چاودێری دۆمەین، هۆستینگ، ئیمەیڵ و نوێکردنەوەکان']);
    }
}
