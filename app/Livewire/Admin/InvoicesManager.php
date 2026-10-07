<?php

namespace App\Livewire\Admin;

use App\Exceptions\FinanceException;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Subscription;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Support\Decimal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class InvoicesManager extends Component
{
    use WithPagination;
    use WireUiActions;

    #[Url]
    public string $search = '';
    #[Url]
    public string $statusFilter = 'all';

    public bool $showModal = false;
    public ?int $editingId = null;

    // View Invoice Modal
    public bool $showViewModal = false;
    public ?int $viewingId = null;
    public string $reverseReason = '';

    // Record Payment Modal
    public bool $showPaymentModal = false;
    public ?int $paymentInvoiceId = null;
    public $pay_amount = null; // untyped: an emptied number input sends ""
    public ?string $pay_date = null;
    public string $pay_method = 'cash';
    public string $pay_reference = '';
    public string $pay_notes = '';
    public string $payKey = ''; // one key per opened form: a double submit records one payment

    // Invoice Form Fields
    public ?int $client_id = null;
    public ?int $contract_id = null;
    public ?int $project_id = null;
    public string $invoice_number = '';
    public ?string $issue_date = null;
    public ?string $due_date = null;
    public $discount = 0.00; // untyped: an emptied number input sends ""
    public $tax = 0.00; // untyped: an emptied number input sends ""
    public string $currency = 'USD';
    public string $status = 'sent'; // draft, sent or cancelled; partial/paid follow from payments
    public string $payment_method = 'FIB / FastPay / کاش';
    public string $notes = '';
    public string $terms = 'تکایە پێش بەرواری دیاریکراو پاکتاوی ئەم وەسڵە بکەن.';

    // Dynamic Line Items
    public array $items = [];

    protected function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'project_id' => 'nullable|exists:projects,id',
            'invoice_number' => 'required|string|max:50',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'currency' => 'required|in:USD,IQD',
            'status' => 'required|in:draft,sent,cancelled',
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.service_type' => 'required|in:development,hosting,domain,vps,email,maintenance,license,other',
            'items.*.billing_cycle' => 'required|in:one_time,monthly,annual',
            'items.*.start_date' => 'nullable|required_unless:items.*.billing_cycle,one_time|date',
            'items.*.expiry_date' => 'nullable|date|after:items.*.start_date',
            'items.*.custom_period' => 'boolean',
            'items.*.period_note' => 'nullable|string|max:255',
            'items.*.subscription_id' => 'nullable|integer',
            'items.*.create_service' => 'boolean',
        ];
    }

    /**
     * ?new=1&client=5&project=9 (from a project page) opens the form with client and project filled.
     */
    public function mount(): void
    {
        $this->addItem();

        if (request()->boolean('new')) {
            $this->openModal();
            $project = request()->integer('project') ? Project::find(request()->integer('project')) : null;
            $this->client_id = $project?->client_id ?? (request()->integer('client') ?: null);
            $this->project_id = $project?->client_id ? $project->id : null;
        }
    }

    public function updatedClientId(): void
    {
        // Project, contract and services must belong to the chosen client.
        $this->project_id = null;
        $this->contract_id = null;
        foreach ($this->items as $i => $item) {
            $this->items[$i]['subscription_id'] = null;
            $this->items[$i]['service_period_id'] = null;
        }
    }

    public function addItem(): void
    {
        $this->items[] = [
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0.00,
            'service_type' => 'development',
            'billing_cycle' => 'one_time',
            'start_date' => null,
            'expiry_date' => null,
            'custom_period' => false,
            'period_note' => '',
            'subscription_id' => null,
            'service_period_id' => null,
            'create_service' => false,
        ];
    }

    public function updatedItems($value, $key): void
    {
        [$index, $field] = explode('.', $key, 2);
        if (!in_array($field, ['billing_cycle', 'quantity', 'start_date', 'custom_period'], true)) return;
        $item = $this->items[$index];
        if (! empty($item['custom_period'])) return; // dates typed by hand, with a reason
        if ($item['billing_cycle'] === 'one_time') {
            $this->items[$index]['start_date'] = null;
            $this->items[$index]['expiry_date'] = null;
            return;
        }
        if (empty($item['start_date']) || !is_numeric($item['quantity']) || (float) $item['quantity'] < 1 || (float) $item['quantity'] != (int) $item['quantity']) return;
        try {
            $start = Carbon::parse($item['start_date']);
            $end = $item['billing_cycle'] === 'annual'
                ? $start->addYearsNoOverflow((int) $item['quantity'])
                : $start->addMonthsNoOverflow((int) $item['quantity']);
            $this->items[$index]['expiry_date'] = $end->format('Y-m-d');
        } catch (\Exception $e) {
            $this->addError("items.$index.start_date", 'ژمارە یان بەروارێکی دروست بنووسە.');
        }
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function openModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $invoice = Invoice::with('items')->findOrFail($id);
        $this->editingId = $invoice->id;
        $this->client_id = $invoice->client_id;
        $this->contract_id = $invoice->contract_id;
        $this->project_id = $invoice->project_id;
        $this->invoice_number = $invoice->invoice_number;
        $this->issue_date = $invoice->issue_date->format('Y-m-d');
        $this->due_date = $invoice->due_date->format('Y-m-d');
        $this->discount = (float) $invoice->discount;
        $this->tax = (float) $invoice->tax;
        $this->currency = $invoice->currency;
        $this->status = in_array($invoice->status, ['draft', 'cancelled'], true) ? $invoice->status : 'sent';
        $this->payment_method = $invoice->payment_method ?? 'FIB / FastPay / کاش';
        $this->notes = $invoice->notes ?? '';
        $this->terms = $invoice->terms ?? '';

        $this->items = [];
        foreach ($invoice->items as $item) {
            $this->items[] = [
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'service_type' => $item->service_type ?? 'development',
                'billing_cycle' => $item->billing_cycle ?? 'one_time',
                'start_date' => $item->start_date?->format('Y-m-d'),
                'expiry_date' => $item->expiry_date?->format('Y-m-d'),
                'custom_period' => (bool) $item->custom_period,
                'period_note' => $item->period_note ?? '',
                'subscription_id' => $item->subscription_id,
                'service_period_id' => $item->service_period_id,
                'create_service' => false,
            ];
        }

        $this->showModal = true;
    }

    public function viewInvoice(int $id): void
    {
        $this->viewingId = Invoice::findOrFail($id)->id;
        $this->reverseReason = '';
        $this->showViewModal = true;
    }

    /** "Paid in full": records only the remaining balance; a second click records nothing. */
    public function markAsPaid(int $id, PaymentService $payments): void
    {
        $invoice = Invoice::findOrFail($id);
        try {
            $payment = $payments->payRemaining($invoice, ['source' => 'manual']);
        } catch (FinanceException $e) {
            $this->notifyError($e->getMessage());

            return;
        }

        $this->notification()->send([
            'icon' => 'success',
            'title' => 'وەسڵ پاکتاوکرا',
            'description' => $payment
                ? "وەسڵ ژمارە {$invoice->invoice_number} بە تەواوی درا."
                : "وەسڵ ژمارە {$invoice->invoice_number} پێشتر پاکتاو کرابوو.",
        ]);
    }

    public function openPayment(int $id): void
    {
        $invoice = Invoice::findOrFail($id);
        app(PaymentService::class)->refresh($invoice);

        $this->paymentInvoiceId = $invoice->id;
        $this->pay_amount = max(0, $invoice->remaining_balance);
        $this->pay_date = today()->toDateString();
        $this->pay_method = 'cash';
        $this->pay_reference = '';
        $this->pay_notes = '';
        $this->payKey = 'web:' . Str::uuid();
        $this->resetErrorBag();
        $this->showPaymentModal = true;
    }

    public function savePayment(PaymentService $payments): void
    {
        $this->validate([
            'pay_amount' => 'required|numeric|gt:0',
            'pay_date' => 'required|date|before_or_equal:today',
            'pay_method' => 'required|in:' . implode(',', array_keys(Payment::METHODS)),
            'pay_reference' => 'nullable|string|max:255',
            'pay_notes' => 'nullable|string|max:1000',
        ], [], ['pay_amount' => 'بڕ', 'pay_date' => 'بەروار']);

        $invoice = Invoice::findOrFail($this->paymentInvoiceId);
        try {
            $payments->record($invoice, $this->pay_amount, [
                'paid_on' => $this->pay_date,
                'method' => $this->pay_method,
                'reference' => $this->pay_reference ?: null,
                'notes' => $this->pay_notes ?: null,
                'currency' => $invoice->currency,
                'idempotency_key' => $this->payKey,
            ]);
        } catch (FinanceException $e) {
            $this->addError('pay_amount', $e->getMessage());

            return;
        }

        $this->showPaymentModal = false;
        $this->notification()->send([
            'icon' => 'success',
            'title' => 'پارەدان تۆمارکرا',
            'description' => "وەسڵی {$invoice->invoice_number}.",
        ]);
    }

    public function reversePayment(int $paymentId, PaymentService $payments): void
    {
        $payment = Payment::findOrFail($paymentId);
        try {
            $payments->reverse($payment, $this->reverseReason);
        } catch (FinanceException $e) {
            $this->notifyError($e->getMessage());

            return;
        }
        $this->reverseReason = '';
        $this->notification()->send(['icon' => 'success', 'title' => 'پارەدان گەڕێندرایەوە', 'description' => 'تۆماری پێچەوانە زیادکرا؛ ئەسڵەکەی ماوە.']);
    }

    public function save(InvoiceService $invoices): void
    {
        $this->validate();

        $header = [
            'client_id' => $this->client_id,
            'contract_id' => $this->contract_id,
            'project_id' => $this->project_id,
            'invoice_number' => $this->invoice_number,
            'issue_date' => $this->issue_date,
            'due_date' => $this->due_date,
            'discount' => $this->discount ?: 0,
            'tax' => $this->tax ?: 0,
            'currency' => $this->currency,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'notes' => $this->notes,
            'terms' => $this->terms,
        ];

        // Same rules as every other caller: ownership, periods from start + duration, edit locks.
        $existing = $this->editingId ? Invoice::findOrFail($this->editingId) : null;
        $invoices->save($existing, $header, $this->items);

        $this->notification()->send([
            'icon' => 'success',
            'title' => $existing ? 'وەسڵ نوێکرایەوە' : 'وەسڵ دروستکرا',
            'description' => $existing ? 'زانیاری وەسڵ بە سەرکەوتوویی نوێکرایەوە.' : 'وەسڵی نوێ بە سەرکەوتوویی دروستکرا.',
        ]);

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $invoice = Invoice::find($id);
        if (!$invoice) {
            return;
        }

        $this->dialog()->confirm([
            'title' => 'سڕینەوەی وەسڵ',
            'description' => "ئایا دڵنیایت لە سڕینەوەی وەسڵی ژمارە «{$invoice->invoice_number}»؟",
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
        $invoice = Invoice::find($id);
        if (! $invoice) {
            return;
        }
        // Money history is never hard-deleted: cancel the invoice instead.
        if ($invoice->payments()->exists() || Decimal::of($invoice->paid_amount)->isGreaterThan(0) || $invoice->status === 'paid') {
            $this->notifyError('ئەم وەسڵە پارەی لەسەر تۆمارکراوە و ناسڕدرێتەوە. ئەگەر پێویستە، پارەدانەکان بگەڕێنەوە و وەسڵەکە هەڵبوەشێنەوە.');

            return;
        }

        $invoice->delete();
        $this->notification()->send([
            'icon' => 'success',
            'title' => 'سڕایەوە',
            'description' => 'وەسڵ بە سەرکەوتوویی سڕایەوە.',
        ]);
    }

    private function notifyError(string $message): void
    {
        $this->notification()->send(['icon' => 'error', 'title' => 'ڕێگەپێنەدراوە', 'description' => $message]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->client_id = null;
        $this->contract_id = null;
        $this->project_id = null;
        $this->invoice_number = Invoice::generateNextInvoiceNumber();
        $this->issue_date = Carbon::now()->format('Y-m-d');
        $this->due_date = Carbon::now()->addDays(15)->format('Y-m-d');
        $this->discount = 0.00;
        $this->tax = 0.00;
        $this->currency = 'USD';
        $this->status = 'sent';
        $this->payment_method = 'FIB / FastPay / کاش';
        $this->notes = '';
        $this->terms = 'تکایە پێش بەرواری دیاریکراو پاکتاوی ئەم وەسڵە بکەن.';
        $this->items = [];
        $this->addItem();
    }

    public function render()
    {
        $invoices = Invoice::with(['client', 'items', 'project'])
            ->when($this->search, function ($q) {
                // Grouped, so the status filter below still applies to every search match.
                $q->where(fn ($w) => $w->where('invoice_number', 'like', "%{$this->search}%")
                  ->orWhereHas('client', function ($cq) {
                      $cq->where('name', 'like', "%{$this->search}%")
                         ->orWhere('business_name', 'like', "%{$this->search}%");
                  }));
            })
            ->when($this->statusFilter === 'overdue', fn ($q) => $q->open()->whereDate('due_date', '<', today()))
            ->when(! in_array($this->statusFilter, ['all', 'overdue'], true), fn($q) => $q->where('status', $this->statusFilter))
            ->latest('issue_date')
            ->paginate(10);

        $clients = Client::where('status', 'active')->orderBy('name')->get();
        // Only the chosen client's projects, contracts and services can be linked.
        $projects = $this->client_id ? Project::active()->where('client_id', $this->client_id)->orderBy('title')->get() : collect();
        $contracts = $this->client_id ? Contract::where('client_id', $this->client_id)->orderBy('contract_number')->get() : collect();
        $services = $this->client_id ? Subscription::where('client_id', $this->client_id)->where('status', '!=', 'cancelled')->orderBy('name')->get() : collect();

        return view('livewire.admin.invoices-manager', [
            'invoices' => $invoices,
            'clients' => $clients,
            'projects' => $projects,
            'contracts' => $contracts,
            'services' => $services,
            'viewingInvoice' => $this->viewingId ? Invoice::with(['client', 'items', 'project', 'payments.reversal'])->find($this->viewingId) : null,
            'paymentInvoice' => $this->paymentInvoiceId ? Invoice::find($this->paymentInvoiceId) : null,
            'paymentMethods' => Payment::METHODS,
        ])->layout('layouts.app', ['title' => 'بەڕێوەبردنی وەسڵەکان', 'header' => 'دروستکردن و بەڕێوەبردنی وەسڵ و پارەدان']);
    }
}
