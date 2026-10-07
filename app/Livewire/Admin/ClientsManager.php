<?php

namespace App\Livewire\Admin;

use App\Models\Client;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class ClientsManager extends Component
{
    use WithPagination;
    use WireUiActions;

    public string $search = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $business_name = '';
    public string $email = '';
    public string $phone = '';
    public string $whatsapp = '';
    public string $city = 'هەولێر';
    public string $address = '';
    public string $notes = '';
    public string $status = 'active';

    /** Plain portal code, shown once right after it is issued. Only its hash is stored. */
    public ?string $issuedPortalCode = null;
    public ?string $issuedPortalFor = null;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ];
    }

    public function openModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $client = Client::findOrFail($id);
        $this->editingId = $client->id;
        $this->name = $client->name;
        $this->business_name = $client->business_name ?? '';
        $this->email = $client->email ?? '';
        $this->phone = $client->phone ?? '';
        $this->whatsapp = $client->whatsapp ?? '';
        $this->city = $client->city ?? 'هەولێر';
        $this->address = $client->address ?? '';
        $this->notes = $client->notes ?? '';
        $this->status = $client->status;

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $duplicate = Client::findByPhone($validated['phone'], $this->editingId)
            ?? (filled($validated['whatsapp'] ?? null) ? Client::findByPhone($validated['whatsapp'], $this->editingId) : null);
        if ($duplicate) {
            $this->addError('phone', "ئەم ژمارەیە پێشتر بۆ «{$duplicate->display_name}» تۆمارکراوە.");

            return;
        }

        if ($this->editingId) {
            $client = Client::findOrFail($this->editingId);
            $client->update($validated);
            $this->notification()->send([
                'icon' => 'success',
                'title' => 'کڕیار نوێکرایەوە',
                'description' => 'زانیاری کڕیار بە سەرکەوتوویی نوێکرایەوە.',
            ]);
        } else {
            $client = Client::create($validated);
            $this->showIssuedCode($client, $client->rotatePortalToken());
        }

        $this->showModal = false;
        $this->resetForm();
    }

    /**
     * Issue a new portal code (the old one, including a legacy CL-XXXXXX code, stops working).
     */
    public function rotatePortalCode(int $id): void
    {
        $client = Client::findOrFail($id);
        $this->showIssuedCode($client, $client->rotatePortalToken());
    }

    public function revokePortalCode(int $id): void
    {
        $client = Client::findOrFail($id);
        $client->revokePortalAccess();

        $this->notification()->send([
            'icon' => 'warning',
            'title' => 'دەستگەیشتنی پۆرتاڵ داخرا',
            'description' => "{$client->display_name} ئیتر ناتوانێت بچێتە پۆرتاڵ.",
        ]);
    }

    public function closeIssuedCode(): void
    {
        $this->reset(['issuedPortalCode', 'issuedPortalFor']);
    }

    private function showIssuedCode(Client $client, string $code): void
    {
        $this->issuedPortalCode = $code;
        $this->issuedPortalFor = $client->display_name;
    }

    public function confirmDelete(int $id): void
    {
        $client = Client::find($id);
        if (!$client) {
            return;
        }

        $clientName = $client->business_name ? "{$client->business_name} ({$client->name})" : $client->name;

        $this->dialog()->confirm([
            'title' => 'سڕینەوەی کڕیار',
            'description' => "ئایا دڵنیایت لە سڕینەوەی کڕیار «{$clientName}»؟",
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
        $client = Client::find($id);
        if ($client && $client->invoices()->exists()) {
            // Deleting would cascade away invoices and payment history; deactivate instead.
            $this->notification()->send([
                'icon' => 'error',
                'title' => 'ناسڕدرێتەوە',
                'description' => 'ئەم کڕیارە وەسڵی هەیە و مێژووی دارایی دەپارێزرێت. لە جیاتی سڕینەوە بیکە بە ناچالاک.',
            ]);

            return;
        }
        if ($client) {
            $client->delete();

            $this->notification()->send([
                'icon' => 'success',
                'title' => 'سڕایەوە',
                'description' => 'کڕیار بە سەرکەوتوویی سڕایەوە.',
            ]);
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->business_name = '';
        $this->email = '';
        $this->phone = '';
        $this->whatsapp = '';
        $this->city = 'هەولێر';
        $this->address = '';
        $this->notes = '';
        $this->status = 'active';
    }

    public function render()
    {
        $clients = Client::with(['invoices', 'subscriptions', 'contracts'])
            ->when($this->search, function ($q) {
                $q->where(fn ($w) => $w->where('name', 'like', "%{$this->search}%")
                  ->orWhere('business_name', 'like', "%{$this->search}%")
                  ->orWhere('phone', 'like', "%{$this->search}%"));
            })
            ->latest()
            ->paginate(10);

        return view('livewire.admin.clients-manager', [
            'clients' => $clients,
        ])->layout('layouts.app', ['title' => 'بەڕێوەبردنی کڕیاران', 'header' => 'لیستی کڕیاران و کۆمپانیاکان']);
    }
}
