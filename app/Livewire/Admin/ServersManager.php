<?php

namespace App\Livewire\Admin;

use App\Exceptions\FinanceException;
use App\Models\Server;
use App\Services\ExpenseService;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

class ServersManager extends Component
{
    use WireUiActions;

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $kind = 'server';
    public string $provider = 'Hetzner Cloud';
    public string $ip_address = '';
    public string $location = 'Germany';
    public string $specs = '';
    public $cost = 0.00; // untyped: an emptied number input sends ""
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
            'kind' => 'required|in:' . implode(',', array_keys(Server::KINDS)),
            'provider' => 'nullable|string|max:255',
            'ip_address' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'specs' => 'nullable|string|max:255',
            'cost' => 'required|numeric|min:0',
            'currency' => 'required|in:USD,IQD',
            'billing_cycle' => 'required|in:' . implode(',', array_keys(Server::CYCLES)),
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
        $this->kind = $server->kind ?: 'server';
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

        // The server and its expense schedule (synced in Server::saved) are written together.
        DB::transaction(fn () => $this->editingId
            ? Server::findOrFail($this->editingId)->update($validated)
            : Server::create($validated));

        if ($this->editingId) {
            $this->notification()->send([
                'icon' => 'success',
                'title' => 'سێرڤەر نوێکرایەوە',
                'description' => 'زانیاری سێرڤەر بە سەرکەوتوویی نوێکرایەوە.',
            ]);
        } else {
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

        $schedule = $server->schedule ?? app(ExpenseService::class)->syncServerSchedule($server);
        $start = $schedule->next_due_on;
        $end = $schedule->periodEndFrom($start);

        $this->dialog()->confirm([
            'title' => 'پارەی ئەم ماوەیە درا؟',
            'description' => "«{$server->name}»: " . Money::format((float) $schedule->amount_per_cycle, $schedule->currency)
                . " بۆ ماوەی {$start->toDateString()} → {$end->toDateString()} وەک خەرجیی ئەمڕۆ تۆمار دەکرێت و کاتی داهاتوو دەبێتە {$end->toDateString()}."
                . ' بۆ بڕ یان ماوەی جیاواز لە تابی «خەرجی دووبارە» «پارەدرا» بەکاربهێنە.',
            'icon' => 'question',
            'accept' => [
                'label' => 'بەڵێ، پارەدرا',
                'method' => 'renewServer',
                'params' => [$id, $start->toDateString()],
                'color' => 'primary',
            ],
            'reject' => [
                'label' => 'پاشگەزبوونەوە',
            ],
        ]);
    }

    /**
     * @param  string|null  $periodStart  the period shown in the dialog; repeating the action pays it only once
     */
    public function renewServer(int $id, ?string $periodStart = null): void
    {
        $server = Server::findOrFail($id);
        $schedule = $server->schedule ?? app(ExpenseService::class)->syncServerSchedule($server);
        try {
            // One period, one expense row; a second click for the same period records nothing new.
            $expense = app(ExpenseService::class)->paySchedule($schedule, array_filter(['period_start' => $periodStart]));
        } catch (FinanceException $e) {
            $this->notification()->send(['icon' => 'error', 'title' => 'تۆمار نەکرا', 'description' => $e->getMessage()]);

            return;
        }

        $this->notification()->send([
            'icon' => 'success',
            'title' => 'پارەدان تۆمارکرا',
            'description' => "{$server->name}: " . Money::format((float) $expense->amount, $expense->currency)
                . " · کاتی داهاتوو {$server->fresh()->renewal_date->format('Y-m-d')}.",
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
        if ($server && $server->expenses()->exists()) {
            // Paid history stays; stop the plan instead of deleting it.
            $server->update(['status' => 'terminated']);
            $this->notification()->send([
                'icon' => 'info',
                'title' => 'وەستێنرا',
                'description' => 'ئەم سێرڤەرە خەرجیی تۆمارکراوی هەیە، بۆیە نەسڕایەوە و وەک «کۆتایی هاتوو» دیاریکرا.',
            ]);

            return;
        }
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
        $this->kind = 'server';
        $this->provider = '';
        $this->ip_address = '';
        $this->location = '';
        $this->specs = '';
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
        $servers = Server::with(['subscriptions.client', 'schedule'])->orderBy('renewal_date', 'asc')->get();

        // Forecast from the schedules (the single source); never added to paid expenses.
        $active = $servers->where('status', 'active');
        $annualTotals = Money::totals($active, fn ($s) => $s->schedule?->annual_forecast ?? $s->annual_cost, fn ($s) => $s->currency);
        $monthlyTotals = array_map(fn ($v) => $v / 12, $annualTotals);

        return view('livewire.admin.servers-manager', [
            'servers' => $servers,
            'annualTotals' => $annualTotals,
            'monthlyTotals' => $monthlyTotals,
        ])->layout('layouts.app', ['title' => 'خزمەتگوزارییەکانی خۆم', 'header' => 'خزمەتگوزارییەکانی خۆم (خەرجی)']);
    }
}
