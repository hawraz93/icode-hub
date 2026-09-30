<?php

namespace App\Livewire\Admin;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class InvoicesManager extends Component
{
    use WithPagination;
    use WireUiActions;

    public string $search = '';
    public string $statusFilter = 'all';

    public bool $showModal = false;
    public ?int $editingId = null;

    // View Invoice Modal
    public bool $showViewModal = false;
    public ?Invoice $viewingInvoice = null;

    // Invoice Form Fields
    public ?int $client_id = null;
    public ?int $contract_id = null;
    public ?int $project_id = null;
    public string $invoice_number = '';
    public ?string $issue_date = null;
    public ?string $due_date = null;
    public float $discount = 0.00;
    public float $tax = 0.00;
    public float $paid_amount = 0.00;
    public string $currency = 'USD';
    public string $status = 'draft';
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
            'due_date' => 'required|date',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'currency' => 'required|string|max:10',
            'status' => 'required|in:draft,sent,paid,partial,overdue,cancelled',
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.service_type' => 'nullable|string',
        ];
    }

    public function mount(): void
    {
        $this->addItem();
    }

    public function addItem(): void
    {
        $this->items[] = [
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0.00,
            'service_type' => 'development',
        ];
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
        $this->paid_amount = (float) $invoice->paid_amount;
        $this->currency = $invoice->currency;
        $this->status = $invoice->status;
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
            ];
        }

        $this->showModal = true;
    }

    public function viewInvoice(int $id): void
    {
        $this->viewingInvoice = Invoice::with(['client', 'items', 'project'])->findOrFail($id);
        $this->showViewModal = true;
    }

    public function markAsPaid(int $id): void
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->markPaid();

        $this->notification()->send([
            'icon' => 'success',
            'title' => 'وەسڵ پاکتاوکرا',
            'description' => "وەسڵ ژمارە {$invoice->invoice_number} بە تەواوی درا.",
        ]);
    }

    public function save(): void
    {
        $this->validate();

        // Calculate Subtotal & Total
        $subtotal = 0;
        foreach ($this->items as $item) {
            $subtotal += ($item['quantity'] * $item['unit_price']);
        }

        $total = $subtotal - $this->discount + $this->tax;
        if ($total < 0) $total = 0;

        $invoiceData = [
            'client_id' => $this->client_id,
            'contract_id' => $this->contract_id,
            'project_id' => $this->project_id,
            'invoice_number' => $this->invoice_number,
            'issue_date' => $this->issue_date,
            'due_date' => $this->due_date,
            'subtotal' => $subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'total' => $total,
            'paid_amount' => $this->paid_amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'notes' => $this->notes,
            'terms' => $this->terms,
            'paid_at' => $this->status === 'paid' ? Carbon::now() : null,
        ];

        if ($this->editingId) {
            $invoice = Invoice::findOrFail($this->editingId);
            $invoice->update($invoiceData);
            $invoice->items()->delete();

            foreach ($this->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                    'service_type' => $item['service_type'] ?? 'development',
                ]);
            }

            $this->notification()->send([
                'icon' => 'success',
                'title' => 'وەسڵ نوێکرایەوە',
                'description' => 'زانیاری وەسڵ بە سەرکەوتوویی نوێکرایەوە.',
            ]);
        } else {
            $invoice = Invoice::create($invoiceData);
            foreach ($this->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                    'service_type' => $item['service_type'] ?? 'development',
                ]);
            }

            $this->notification()->send([
                'icon' => 'success',
                'title' => 'وەسڵ دروستکرا',
                'description' => 'وەسڵی نوێ بە سەرکەوتوویی دروستکرا.',
            ]);
        }

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
        if ($invoice) {
            $invoice->delete();

            $this->notification()->send([
                'icon' => 'success',
                'title' => 'سڕایەوە',
                'description' => 'وەسڵ بە سەرکەوتوویی سڕایەوە.',
            ]);
        }
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
        $this->paid_amount = 0.00;
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
                $q->where('invoice_number', 'like', "%{$this->search}%")
                  ->orWhereHas('client', function ($cq) {
                      $cq->where('name', 'like', "%{$this->search}%")
                         ->orWhere('business_name', 'like', "%{$this->search}%");
                  });
            })
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->latest('issue_date')
            ->paginate(10);

        $clients = Client::where('status', 'active')->orderBy('name')->get();
        $projects = Project::orderBy('title')->get();
        $contracts = Contract::orderBy('contract_number')->get();

        return view('livewire.admin.invoices-manager', [
            'invoices' => $invoices,
            'clients' => $clients,
            'projects' => $projects,
            'contracts' => $contracts,
        ])->layout('layouts.app', ['title' => 'بەڕێوەبردنی وەسڵەکان', 'header' => 'دروستکردن و بەڕێوەبردنی وەسڵ و پارەدان']);
    }
}
