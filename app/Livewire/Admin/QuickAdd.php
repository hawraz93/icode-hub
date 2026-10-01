<?php

namespace App\Livewire\Admin;

use App\Models\Client;
use App\Services\QuickRenewal;
use Illuminate\Support\Str;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

class QuickAdd extends Component
{
    use WireUiActions;

    public string $input = '';

    /** @var array<int, array<string, mixed>> */
    public array $drafts = [];

    public bool $showNewClient = false;
    public string $newClientName = '';
    public string $newClientPhone = '';

    public function read(QuickRenewal $quick): void
    {
        $lines = array_filter(array_map('trim', preg_split('/\R/u', $this->input)));
        if (count($lines) > 50) {
            $this->addError('input', 'زۆرترین ٥٠ دێڕ بە یەکجار.');

            return;
        }

        foreach ($lines as $line) {
            $draft = $quick->draft($line);
            if ($draft && ($draft['domain'] || $draft['amount'])) {
                $this->drafts[] = $draft;
            }
        }

        $this->input = '';
    }

    public function pickDate(int $i, string $date): void
    {
        if (isset($this->drafts[$i])) {
            $this->drafts[$i]['expiry'] = $date;
            $this->drafts[$i]['date_source'] = 'typed';
        }
    }

    public function remove(int $i): void
    {
        unset($this->drafts[$i]);
        $this->drafts = array_values($this->drafts);
    }

    public function createClient(): void
    {
        $this->validate([
            'newClientName' => 'required|string|max:255',
            'newClientPhone' => 'nullable|string|max:50',
        ], [], ['newClientName' => 'ناوی کڕیار']);

        $client = ($this->newClientPhone ? Client::findByPhone($this->newClientPhone) : null) ?? Client::create([
            'name' => $this->newClientName,
            'phone' => $this->newClientPhone ?: null,
            'whatsapp' => $this->newClientPhone ?: null,
            'status' => 'active',
            'portal_access_code' => 'CL-' . strtoupper(Str::random(6)),
        ]);

        // Give it to every row that has no client yet.
        foreach ($this->drafts as $i => $d) {
            if (empty($d['client_id'])) {
                $this->drafts[$i]['client_id'] = $client->id;
            }
        }

        $this->reset('newClientName', 'newClientPhone', 'showNewClient');
    }

    public function save(QuickRenewal $quick)
    {
        $this->resetErrorBag();
        foreach ($this->drafts as $i => $d) {
            if (empty($d['client_id'])) {
                $this->addError("drafts.{$i}.client_id", 'کڕیار هەڵبژێرە');
            }
            if (empty($d['expiry'])) {
                $this->addError("drafts.{$i}.expiry", 'بەروار بنووسە');
            }
        }
        if ($this->getErrorBag()->isNotEmpty() || empty($this->drafts)) {
            return null;
        }

        foreach ($this->drafts as $d) {
            $d['name'] = QuickRenewal::serviceName($d['type'], $d['domain'] ?: $d['name']);
            $sub = $quick->save($d, (int) $d['client_id']);
            if (empty($d['paid'])) {
                $sub->update(['is_paid' => false, 'paid_at' => null]);
            }
        }

        $count = count($this->drafts);
        $this->drafts = [];
        session()->flash('quick_added', $count);

        return $this->redirectRoute('admin.renewals', navigate: false);
    }

    public function render()
    {
        return view('livewire.admin.quick-add', [
            'clients' => Client::where('status', 'active')->orderBy('name')->get(),
        ])->layout('layouts.app', ['title' => 'زیادکردنی خێرا', 'header' => 'زیادکردنی خێرا']);
    }
}
