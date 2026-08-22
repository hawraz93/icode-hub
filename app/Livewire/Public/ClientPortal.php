<?php

namespace App\Livewire\Public;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Contract;
use App\Models\Ticket;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

class ClientPortal extends Component
{
    use WireUiActions;

    public string $accessCode = '';
    public ?Client $client = null;

    // View Invoice / Contract
    public bool $showInvoiceModal = false;
    public ?Invoice $selectedInvoice = null;

    public bool $showContractModal = false;
    public ?Contract $selectedContract = null;

    // New Support Ticket Modal
    public bool $showTicketModal = false;
    public string $ticket_subject = '';
    public string $ticket_description = '';
    public string $ticket_priority = 'medium';

    protected function rules(): array
    {
        return [
            'ticket_subject' => 'required|string|max:255',
            'ticket_description' => 'required|string',
            'ticket_priority' => 'required|in:low,medium,high,urgent',
        ];
    }

    public function login(): void
    {
        $this->validate([
            'accessCode' => 'required|string',
        ]);

        $code = trim($this->accessCode);
        $client = Client::with(['subscriptions.server', 'invoices.items', 'contracts', 'tickets'])
            ->where('portal_access_code', $code)
            ->orWhere('phone', 'like', "%{$code}%")
            ->first();

        if ($client) {
            $this->client = $client;
            $this->notification()->send([
                'icon' => 'success',
                'title' => 'بەخێربێیت!',
                'description' => "بەخێربێیت بەڕێز {$client->name}.",
            ]);
        } else {
            $this->notification()->send([
                'icon' => 'error',
                'title' => 'کۆد یان ژمارە هەڵەیە!',
                'description' => 'کۆدی پۆرتاڵ یان ژمارەی مۆبایلەکە نەدۆزرایەوە.',
            ]);
        }
    }

    public function logout(): void
    {
        $this->client = null;
        $this->accessCode = '';
    }

    public function viewInvoice(int $id): void
    {
        $this->selectedInvoice = Invoice::with(['items', 'client'])->findOrFail($id);
        $this->showInvoiceModal = true;
    }

    public function viewContract(int $id): void
    {
        $this->selectedContract = Contract::with(['client'])->findOrFail($id);
        $this->showContractModal = true;
    }

    public function submitTicket(): void
    {
        $this->validate();

        Ticket::create([
            'client_id' => $this->client->id,
            'subject' => $this->ticket_subject,
            'description' => $this->ticket_description,
            'priority' => $this->ticket_priority,
            'status' => 'open',
        ]);

        $this->notification()->send([
            'icon' => 'success',
            'title' => 'تکت نێردرا!',
            'description' => 'داواکاری هاوکاری و تکتەکەت بە سەرکەوتوویی تۆمارکرا.',
        ]);

        $this->showTicketModal = false;
        $this->reset(['ticket_subject', 'ticket_description']);
        $this->client->load('tickets');
    }

    public function render()
    {
        return view('livewire.public.client-portal')
            ->layout('layouts.public', ['title' => 'پۆرتاڵی کڕیاران | iCode Group']);
    }
}
