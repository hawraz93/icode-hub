<?php

namespace App\Livewire\Public;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Ticket;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

/**
 * Client self-service portal. The signed-in client lives only in the server-side session
 * (never in a public Livewire property), and every action re-checks it and the ownership of
 * the record it touches. Only selling prices, invoices, payments and service dates are shown.
 */
class ClientPortal extends Component
{
    use WireUiActions;

    public const SESSION_KEY = 'portal_client_id';

    private const LOCKOUT_SECONDS = 900;

    public string $accessCode = '';

    // Only IDs are public; the records are re-loaded through the signed-in client on every render.
    public ?int $selectedInvoiceId = null;
    public bool $showInvoiceModal = false;

    public ?int $selectedContractId = null;
    public bool $showContractModal = false;

    public bool $showTicketModal = false;
    public string $ticket_subject = '';
    public string $ticket_description = '';
    public string $ticket_priority = 'medium';

    protected function rules(): array
    {
        return [
            'ticket_subject' => 'required|string|max:255',
            'ticket_description' => 'required|string|max:5000',
            'ticket_priority' => 'required|in:low,medium,high,urgent',
        ];
    }

    #[Computed]
    public function client(): ?Client
    {
        $id = session(self::SESSION_KEY);
        if (! $id) {
            return null;
        }

        $client = Client::with([
            'subscriptions' => fn ($q) => $q->where('status', '!=', 'cancelled')->orderBy('expiry_date'),
            'invoices' => fn ($q) => $q->where('status', '!=', 'draft')->latest('issue_date'),
            'contracts' => fn ($q) => $q->where('status', '!=', 'draft'),
        ])->find($id);

        if (! $client || $client->status !== 'active') {
            session()->forget(self::SESSION_KEY);

            return null;
        }

        return $client;
    }

    public function login(): void
    {
        $this->validate(['accessCode' => 'required|string|max:100']);

        $key = 'portal-login:' . request()->ip();
        if (RateLimiter::tooManyAttempts($key, (int) config('portal.max_attempts', 5))) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            $this->addError('accessCode', "هەوڵی زۆر هەڵە درا. {$minutes} خولەکی تر هەوڵ بدەرەوە.");

            return;
        }

        $client = Client::findByPortalCode($this->accessCode);
        if (! $client) {
            RateLimiter::hit($key, self::LOCKOUT_SECONDS);
            $this->addError('accessCode', 'کۆدی پۆرتاڵ هەڵەیە.');

            return;
        }

        RateLimiter::clear($key);
        session()->regenerate();
        session()->put(self::SESSION_KEY, $client->id);
        $client->forceFill(['portal_last_login_at' => now()])->saveQuietly();
        $this->reset(['accessCode', 'selectedInvoiceId', 'selectedContractId', 'showInvoiceModal', 'showContractModal']);
        unset($this->client);

        $this->notification()->send([
            'icon' => 'success',
            'title' => 'بەخێربێیت!',
            'description' => "بەخێربێیت بەڕێز {$client->name}.",
        ]);
    }

    public function logout(): void
    {
        session()->forget(self::SESSION_KEY);
        session()->regenerate();
        $this->reset();
        unset($this->client);
    }

    public function viewInvoice(int $id): void
    {
        $client = $this->requireClient();
        // Scoped to this client: another client's ID is simply not found.
        $invoice = $client->invoices()->where('status', '!=', 'draft')->findOrFail($id);
        $this->selectedInvoiceId = $invoice->id;
        $this->showInvoiceModal = true;
    }

    public function viewContract(int $id): void
    {
        $client = $this->requireClient();
        $contract = $client->contracts()->where('status', '!=', 'draft')->findOrFail($id);
        $this->selectedContractId = $contract->id;
        $this->showContractModal = true;
    }

    public function submitTicket(): void
    {
        $client = $this->requireClient();
        $this->validate();

        Ticket::create([
            'client_id' => $client->id,
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
    }

    private function requireClient(): Client
    {
        $client = $this->client;
        abort_unless($client, 403);

        return $client;
    }

    public function render()
    {
        $client = $this->client;

        $invoice = $client && $this->selectedInvoiceId
            ? $client->invoices()->where('status', '!=', 'draft')->with(['items', 'payments', 'project'])->find($this->selectedInvoiceId)
            : null;
        $contract = $client && $this->selectedContractId
            ? $client->contracts()->where('status', '!=', 'draft')->find($this->selectedContractId)
            : null;

        return view('livewire.public.client-portal', [
            'client' => $client,
            'selectedInvoice' => $invoice,
            'selectedContract' => $contract,
        ])->layout('layouts.public', ['title' => 'پۆرتاڵی کڕیاران | iCode Group']);
    }
}
