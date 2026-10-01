<?php

namespace App\Livewire\Admin;

use App\Models\Expense;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class ExpensesManager extends Component
{
    use WithPagination;
    use WireUiActions;

    public string $search = '';
    public string $categoryFilter = 'all';
    public string $currencyFilter = 'all';

    // Modal Form Properties
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $title = '';
    public string $category = 'software_ai';
    public $amount = 0.00; // untyped: an emptied number input sends ""
    public string $currency = 'USD';
    public string $billing_cycle = 'monthly';
    public ?string $expense_date = null;
    public string $payment_method = 'card';
    public string $vendor = '';
    public string $notes = '';

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'category' => 'required|in:infrastructure,software_ai,telecom,transport,office,marketing,other',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:USD,IQD',
            'billing_cycle' => 'required|in:one_time,monthly,annual',
            'expense_date' => 'required|date',
            'payment_method' => 'required|in:cash,fastpay,fib,zaincash,card',
            'vendor' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
    }

    public function mount(): void
    {
        $this->expense_date = Carbon::now()->format('Y-m-d');
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $expense = Expense::findOrFail($id);
        $this->editingId = $expense->id;
        $this->title = $expense->title;
        $this->category = $expense->category;
        $this->amount = (float) $expense->amount;
        $this->currency = $expense->currency;
        $this->billing_cycle = $expense->billing_cycle;
        $this->expense_date = $expense->expense_date->format('Y-m-d');
        $this->payment_method = $expense->payment_method;
        $this->vendor = $expense->vendor ?? '';
        $this->notes = $expense->notes ?? '';

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            $expense = Expense::findOrFail($this->editingId);
            $expense->update($validated);
            $this->notification()->send([
                'icon' => 'success',
                'title' => 'خەرجی نوێکرایەوە',
                'description' => 'زانیاری خەرجییەکە بە سەرکەوتوویی نوێکرایەوە.',
            ]);
        } else {
            Expense::create($validated);
            $this->notification()->send([
                'icon' => 'success',
                'title' => 'خەرجی تۆمارکرا',
                'description' => 'خەرجییە نوێیەکە بە سەرکەوتوویی تۆمارکرا.',
            ]);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $expense = Expense::find($id);
        if (!$expense) {
            return;
        }

        $this->dialog()->confirm([
            'title' => 'سڕینەوەی خەرجی',
            'description' => "ئایا دڵنیایت لە سڕینەوەی خەرجی «{$expense->title}» بە بڕی {$expense->amount} {$expense->currency}؟",
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
        $expense = Expense::find($id);
        if ($expense) {
            $expense->delete();
            $this->notification()->send([
                'icon' => 'success',
                'title' => 'سڕایەوە',
                'description' => 'تۆماری خەرجی بە سەرکەوتوویی سڕایەوە.',
            ]);
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->category = 'software_ai';
        $this->amount = 0.00;
        $this->currency = 'USD';
        $this->billing_cycle = 'monthly';
        $this->expense_date = Carbon::now()->format('Y-m-d');
        $this->payment_method = 'card';
        $this->vendor = '';
        $this->notes = '';
    }

    public function render()
    {
        $query = Expense::query()
            ->when($this->search, function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhere('vendor', 'like', "%{$this->search}%")
                  ->orWhere('notes', 'like', "%{$this->search}%");
            })
            ->when($this->categoryFilter !== 'all', fn($q) => $q->where('category', $this->categoryFilter))
            ->when($this->currencyFilter !== 'all', fn($q) => $q->where('currency', $this->currencyFilter));

        $expenses = $query->orderBy('expense_date', 'desc')->paginate(15);

        // Financial KPIs
        $totalUsd = Expense::where('currency', 'USD')->sum('amount');
        $totalIqd = Expense::where('currency', 'IQD')->sum('amount');

        return view('livewire.admin.expenses-manager', [
            'expenses' => $expenses,
            'totalUsd' => $totalUsd,
            'totalIqd' => $totalIqd,
        ])->layout('layouts.app', ['title' => 'بەڕێوەبردنی خەرجییەکان', 'header' => 'چاودێری و تۆماری مەسرووفاتی کۆمپانیای iCode']);
    }
}
