<?php

namespace App\Livewire\Admin;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Project;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

class ContractsManager extends Component
{
    use WithPagination;
    use WireUiActions;

    public string $search = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    public bool $showViewModal = false;
    public ?Contract $viewingContract = null;

    // Form fields
    public ?int $client_id = null;
    public ?int $project_id = null;
    public string $contract_number = '';
    public string $title = '';
    public string $terms = '';
    public $total_amount = 0.00; // untyped: an emptied number input sends ""
    public string $currency = 'USD';
    public ?string $start_date = null;
    public ?string $end_date = null;
    public string $status = 'active';
    public bool $signed_by_client = true;
    public string $client_signature_name = '';
    public string $notes = '';

    protected function rules(): array
    {
        return [
            'client_id' => 'required|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'contract_number' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'terms' => 'required|string',
            'total_amount' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'status' => 'required|in:draft,active,completed,cancelled',
            'signed_by_client' => 'boolean',
            'client_signature_name' => 'nullable|string|max:255',
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
        $contract = Contract::findOrFail($id);
        $this->editingId = $contract->id;
        $this->client_id = $contract->client_id;
        $this->project_id = $contract->project_id;
        $this->contract_number = $contract->contract_number;
        $this->title = $contract->title;
        $this->terms = $contract->terms ?? '';
        $this->total_amount = (float) $contract->total_amount;
        $this->currency = $contract->currency;
        $this->start_date = $contract->start_date ? $contract->start_date->format('Y-m-d') : null;
        $this->end_date = $contract->end_date ? $contract->end_date->format('Y-m-d') : null;
        $this->status = $contract->status;
        $this->signed_by_client = (bool) $contract->signed_by_client;
        $this->client_signature_name = $contract->client_signature_name ?? '';
        $this->notes = $contract->notes ?? '';

        $this->showModal = true;
    }

    public function viewContract(int $id): void
    {
        $this->viewingContract = Contract::with(['client', 'project', 'invoices'])->findOrFail($id);
        $this->showViewModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();
        if ($validated['project_id'] && (int) Project::whereKey($validated['project_id'])->value('client_id') !== (int) $validated['client_id']) {
            $this->addError('project_id', 'ئەم پڕۆژەیە هی ئەم کڕیارە نییە.');

            return;
        }
        $validated['signed_at'] = $this->signed_by_client ? Carbon::now() : null;

        if ($this->editingId) {
            $contract = Contract::findOrFail($this->editingId);
            $contract->update($validated);
            $this->notification()->send([
                'icon' => 'success',
                'title' => 'عەقد نوێکرایەوە',
                'description' => 'زانیاری عەقد بە سەرکەوتوویی نوێکرایەوە.',
            ]);
        } else {
            Contract::create($validated);
            $this->notification()->send([
                'icon' => 'success',
                'title' => 'عەقد دروستکرا',
                'description' => 'عەقدی نوێ بە سەرکەوتوویی تۆمارکرا.',
            ]);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $contract = Contract::find($id);
        if (!$contract) {
            return;
        }

        $this->dialog()->confirm([
            'title' => 'سڕینەوەی عەقد',
            'description' => "ئایا دڵنیایت لە سڕینەوەی عەقدی ژمارە «{$contract->contract_number}»؟",
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
        $contract = Contract::find($id);
        if ($contract) {
            $contract->delete();

            $this->notification()->send([
                'icon' => 'success',
                'title' => 'سڕایەوە',
                'description' => 'عەقد بە سەرکەوتوویی سڕایەوە.',
            ]);
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->client_id = null;
        $this->project_id = null;
        $this->contract_number = 'ICODE-CNT-' . date('Y') . '-' . str_pad((Contract::count() + 1), 3, '0', STR_PAD_LEFT);
        $this->title = 'عەقدی پەرەپێدان و ڕێککەوتنی سۆفتوێر';
        $this->terms = "ماددەی یەکەم: لایەنی یەکەم (iCode Group) پابەند دەبێت بە پەرەپێدان و دابینکردنی سیستەمەکە بەپێی تایبەتمەندییە دیاریکراوەکان.\nماددەی دووەم: لایەنی دووەم پابەند دەبێت بە پێدانی گوژمەی دیاریکراو بەپێی وەسڵەکانی پێشەکی و کۆتایی.\nماددەی سێیەم: مافی خاوەندارێتی و پاراستنی نهێنی داتاکان پارێزراو دەبێت لە لایەن هەردوو لایەنەوە.\nماددەی چوارەم: پشتگیری و نوێکردنەوەی ساڵانە بەپێی ڕێککەوتنی نوێ دەبێت پاش تەواوبوونی ساڵی یەکەم.";
        $this->total_amount = 0.00;
        $this->currency = 'USD';
        $this->start_date = Carbon::now()->format('Y-m-d');
        $this->end_date = Carbon::now()->addYear()->format('Y-m-d');
        $this->status = 'active';
        $this->signed_by_client = true;
        $this->client_signature_name = '';
        $this->notes = '';
    }

    public function render()
    {
        $contracts = Contract::with(['client', 'project'])
            ->when($this->search, function ($q) {
                $q->where('contract_number', 'like', "%{$this->search}%")
                  ->orWhere('title', 'like', "%{$this->search}%")
                  ->orWhereHas('client', function ($cq) {
                      $cq->where('name', 'like', "%{$this->search}%")
                         ->orWhere('business_name', 'like', "%{$this->search}%");
                  });
            })
            ->latest('start_date')
            ->paginate(10);

        $clients = Client::where('status', 'active')->orderBy('name')->get();
        $projects = $this->client_id ? Project::active()->where('client_id', $this->client_id)->orderBy('title')->get() : Project::active()->whereNotNull('client_id')->orderBy('title')->get();

        return view('livewire.admin.contracts-manager', [
            'contracts' => $contracts,
            'clients' => $clients,
            'projects' => $projects,
        ])->layout('layouts.app', ['title' => 'بەڕێوەبردنی عەقدەکان', 'header' => 'عەقد و ڕێککەوتننامەی کاری نوێ']);
    }
}
