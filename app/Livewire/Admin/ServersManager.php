<?php

namespace App\Livewire\Admin;

use App\Models\Server;
use Carbon\Carbon;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

class ServersManager extends Component
{
    use WireUiActions;

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $provider = 'Hetzner Cloud';
    public string $ip_address = '';
    public string $location = 'Germany';
    public string $specs = '';
    public float $cost = 0.00;
    public string $currency = 'USD';
    public string $billing_cycle = 'monthly';
    public ?string $purchase_date = null;
    public ?string $renewal_date = null;
    public string $status = 'active';
    public bool $auto_renew = true;
    public string $notes = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'provider' => 'nullable|string|max:255',
            'ip_address' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'specs' => 'nullable|string|max:255',
            'cost' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'billing_cycle' => 'required|in:monthly,quarterly,semi_annual,annual',
            'purchase_date' => 'nullable|date',
            'renewal_date' => 'required|date',
            'status' => 'required|in:active,suspended,terminated',
            'auto_renew' => 'boolean',
            'notes' => 'nullable|string',
        ];
    }

    public function openModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $server = Server::findOrFail($id);
        $this->editingId = $server->id;
        $this->name = $server->name;
        $this->provider = $server->provider ?? '';
        $this->ip_address = $server->ip_address ?? '';
        $this->location = $server->location ?? '';
        $this->specs = $server->specs ?? '';
        $this->cost = (float) $server->cost;
        $this->currency = $server->currency;
        $this->billing_cycle = $server->billing_cycle;
        $this->purchase_date = $server->purchase_date ? $server->purchase_date->format('Y-m-d') : null;
        $this->renewal_date = $server->renewal_date ? $server->renewal_date->format('Y-m-d') : null;
        $this->status = $server->status;
        $this->auto_renew = (bool) $server->auto_renew;
        $this->notes = $server->notes ?? '';

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            $server = Server::findOrFail($this->editingId);
            $server->update($validated);
            $this->notification()->send([
                'icon' => 'success',
                'title' => 'سێرڤەر نوێکرایەوە',
                'description' => 'زانیاری سێرڤەر بە سەرکەوتوویی نوێکرایەوە.',
            ]);
        } else {
            Server::create($validated);
            $this->notification()->send([
                'icon' => 'success',
                'title' => 'سێرڤەر زیادکرا',
                'description' => 'سێرڤەری نوێ بە سەرکەوتوویی تۆمارکرا.',
            ]);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmRenewServer(int $id): void
    {
        $server = Server::find($id);
        if (!$server) {
            return;
        }

        $cycleText = match ($server->billing_cycle) {
            'annual' => '١ ساڵ',
            'semi_annual' => '٦ مانگ',
            'quarterly' => '٣ مانگ',
            default => '١ مانگ',
        };

        $this->dialog()->confirm([
            'title' => 'نوێکردنەوەی بەرواری سێرڤەر',
            'description' => "ئایا دڵنیایت لە تۆمارکردنی نوێکردنەوەی سێرڤەری «{$server->name}» بۆ ماوەی {$cycleText}ی تر؟",
            'icon' => 'question',
            'accept' => [
                'label' => 'بەڵێ، بەروار نوێبکەرەوە',
                'method' => 'renewServer',
                'params' => $id,
                'color' => 'primary',
            ],
            'reject' => [
                'label' => 'پاشگەزبوونەوە',
            ],
        ]);
    }

    public function renewServer(int $id): void
    {
        $server = Server::findOrFail($id);
        $newRenewal = $server->renew();

        $this->notification()->send([
            'icon' => 'success',
            'title' => 'سێرڤەر نوێکرایەوە!',
            'description' => "بەرواری نوێکردنەوەی سێرڤەری {$server->name} درێژکرایەوە بۆ {$newRenewal->format('Y-m-d')}.",
        ]);
    }

    public function confirmDelete(int $id): void
    {
        $server = Server::find($id);
        if (!$server) {
            return;
        }

        $this->dialog()->confirm([
            'title' => 'سڕینەوەی سێرڤەر',
            'description' => "ئایا دڵنیایت لە سڕینەوەی سێرڤەری «{$server->name}»؟",
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
        $server = Server::find($id);
        if ($server) {
            $server->delete();

            $this->notification()->send([
                'icon' => 'success',
                'title' => 'سڕایەوە',
                'description' => 'سێرڤەر بە سەرکەوتوویی سڕایەوە.',
            ]);
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->provider = 'Hetzner Cloud';
        $this->ip_address = '';
        $this->location = 'Germany';
        $this->specs = '4 vCPU, 8 GB RAM, 160 GB NVMe';
        $this->cost = 0.00;
        $this->currency = 'USD';
        $this->billing_cycle = 'monthly';
        $this->purchase_date = Carbon::now()->format('Y-m-d');
        $this->renewal_date = Carbon::now()->addMonth()->format('Y-m-d');
        $this->status = 'active';
        $this->auto_renew = true;
        $this->notes = '';
    }

    public function render()
    {
        $servers = Server::with(['subscriptions.client'])->orderBy('renewal_date', 'asc')->get();

        $totalMonthlyCost = $servers->where('status', 'active')->sum(function($s) {
            return $s->billing_cycle === 'annual' ? ($s->cost / 12) : $s->cost;
        });

        return view('livewire.admin.servers-manager', [
            'servers' => $servers,
            'totalMonthlyCost' => $totalMonthlyCost,
        ])->layout('layouts.app', ['title' => 'بەڕێوەبردنی سێرڤەر و VPS', 'header' => 'چاودێری سێرڤەر، VPS و خەرجی ژێرخان']);
    }
}
