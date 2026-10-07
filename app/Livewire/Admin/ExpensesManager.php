<?php

namespace App\Livewire\Admin;

use App\Exceptions\FinanceException;
use App\Models\Expense;
use App\Models\ExpenseSchedule;
use App\Models\Project;
use App\Services\ExpenseService;
use App\Support\Money;
use Carbon\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

/**
 * One expenses page, two ideas kept apart:
 *  - "recorded": the ledger of money actually paid (each row counts once, never ×12);
 *  - "recurring": plans (VPS, AI, internet...) with price, cycle and next due date, used for forecasts.
 */
class ExpensesManager extends Component
{
    use WithPagination;
    use WireUiActions;

    public const CATEGORIES = [
        'infrastructure' => 'سێرڤەر و ژێرخان',
        'software_ai' => 'AI و نەرمەکاڵا',
        'telecom' => 'ئینتەرنێت و پەیوەندی',
        'transport' => 'بەنزین و هاتووچۆ',
        'office' => 'کەرەستە و شوێن',
        'marketing' => 'ڕیکلام و مارکێتینگ',
        'other' => 'خەرجی تر',
    ];

    public const METHODS = [
        'cash' => 'کاش',
        'fastpay' => 'FastPay',
        'fib' => 'FIB',
        'zaincash' => 'ZainCash',
        'card' => 'ماستەرکارد / ڤیزا',
    ];

    #[Url(as: 'tab')]
    public string $tab = 'recorded';

    public string $search = '';
    public string $categoryFilter = 'all';
    public string $currencyFilter = 'all';
    public string $statusFilter = 'posted';

    // ------- one-off expense form (ledger)
    public bool $showModal = false;
    public ?int $editingId = null;
    public string $title = '';
    public string $category = 'software_ai';
    public $amount = 0.00; // untyped: an emptied number input sends ""
    public string $currency = 'USD';
    public ?string $expense_date = null;
    public string $payment_method = 'card';
    public string $vendor = '';
    public string $reference = '';
    public ?int $project_id = null;
    public string $notes = '';

    // ------- void
    public ?int $voidingId = null;
    public string $voidReason = '';

    // ------- recurring plan form
    public bool $showScheduleModal = false;
    public ?int $scheduleId = null;
    public string $s_title = '';
    public string $s_category = 'software_ai';
    public string $s_vendor = '';
    public string $s_currency = 'USD';
    public $s_amount = 0.00;
    public string $s_cycle_unit = 'month';
    public $s_cycle_count = 1;
    public ?string $s_next_due_on = null;
    public string $s_payment_method = 'card';
    public string $s_status = 'active';
    public bool $s_auto_renew = true;
    public string $s_notes = '';

    // ------- pay a plan
    public bool $showPayModal = false;
    public ?int $payScheduleId = null;
    public ?string $pay_period_start = null;
    public $pay_amount = null;
    public ?string $pay_date = null;
    public string $pay_method = 'card';
    public string $pay_reference = '';
    public string $pay_notes = '';

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'category' => 'required|in:' . implode(',', array_keys(self::CATEGORIES)),
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:USD,IQD',
            'expense_date' => 'required|date',
            'payment_method' => 'required|in:' . implode(',', array_keys(self::METHODS)),
            'vendor' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
            'notes' => 'nullable|string',
        ];
    }

    public function mount(): void
    {
        $this->expense_date = Carbon::now()->format('Y-m-d');
        $this->tab = in_array($this->tab, ['recorded', 'recurring'], true) ? $this->tab : 'recorded';
    }

    public function updating($name): void
    {
        if (in_array($name, ['search', 'categoryFilter', 'currencyFilter', 'statusFilter'], true)) {
            $this->resetPage();
        }
    }

    // ================================================================ ledger

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
        $this->expense_date = $expense->expense_date->format('Y-m-d');
        $this->payment_method = array_key_exists($expense->payment_method, self::METHODS) ? $expense->payment_method : 'cash';
        $this->vendor = $expense->vendor ?? '';
        $this->reference = $expense->reference ?? '';
        $this->project_id = $expense->project_id;
        $this->notes = $expense->notes ?? '';

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            $expense = Expense::findOrFail($this->editingId);
            // Amount, currency and date of a recorded payment are corrected by voiding and re-entering,
            // so the history shows what changed. Descriptive fields can be fixed in place.
            unset($validated['amount'], $validated['currency'], $validated['expense_date']);
            $expense->update($validated);
            $this->notification()->send([
                'icon' => 'success',
                'title' => 'خەرجی نوێکرایەوە',
                'description' => 'زانیاری خەرجییەکە نوێکرایەوە.',
            ]);
        } else {
            Expense::create($validated + ['billing_cycle' => 'one_time', 'status' => 'posted']);
            $this->notification()->send([
                'icon' => 'success',
                'title' => 'خەرجی تۆمارکرا',
                'description' => 'خەرجییە نوێیەکە بە سەرکەوتوویی تۆمارکرا.',
            ]);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function askVoid(int $id): void
    {
        $this->voidingId = Expense::findOrFail($id)->id;
        $this->voidReason = '';
    }

    public function confirmVoid(ExpenseService $service): void
    {
        $this->validate(['voidReason' => 'required|string|max:255'], [], ['voidReason' => 'هۆکار']);
        $service->void(Expense::findOrFail($this->voidingId), $this->voidReason);
        $this->voidingId = null;
        $this->notification()->send(['icon' => 'success', 'title' => 'هەڵوەشێنرایەوە', 'description' => 'تۆمارەکە ماوە بەڵام ئیتر لە کۆی خەرجی هەژمار ناکرێت.']);
    }

    /** Legacy row with billing_cycle monthly/annual: was it one payment or a plan? The user decides. */
    public function resolveLegacy(int $id, bool $createSchedule, ExpenseService $service): void
    {
        $schedule = $service->resolveLegacy(Expense::findOrFail($id), $createSchedule);
        $this->notification()->send([
            'icon' => 'success',
            'title' => 'پشکنرا',
            'description' => $schedule ? "پلانی دووبارەی «{$schedule->title}» دروستکرا؛ ئەم تۆمارە یەکەم پارەدانیەتی." : 'وەک پارەدانی یەکجار هێڵرایەوە.',
        ]);
    }

    // ============================================================ recurring

    public function openScheduleModal(?int $id = null): void
    {
        $this->resetScheduleForm();
        if ($id) {
            $s = ExpenseSchedule::whereNull('server_id')->findOrFail($id);
            $this->scheduleId = $s->id;
            $this->s_title = $s->title;
            $this->s_category = $s->category;
            $this->s_vendor = $s->vendor ?? '';
            $this->s_currency = $s->currency;
            $this->s_amount = (float) $s->amount_per_cycle;
            $this->s_cycle_unit = $s->cycle_unit;
            $this->s_cycle_count = $s->cycle_count;
            $this->s_next_due_on = $s->next_due_on->toDateString();
            $this->s_payment_method = $s->payment_method ?: 'card';
            $this->s_status = $s->status;
            $this->s_auto_renew = (bool) $s->auto_renew;
            $this->s_notes = $s->notes ?? '';
        }
        $this->showScheduleModal = true;
    }

    public function saveSchedule(): void
    {
        $data = $this->validate([
            's_title' => 'required|string|max:255',
            's_category' => 'required|in:' . implode(',', array_keys(self::CATEGORIES)),
            's_vendor' => 'nullable|string|max:255',
            's_currency' => 'required|in:USD,IQD',
            's_amount' => 'required|numeric|gt:0',
            's_cycle_unit' => 'required|in:month,year',
            's_cycle_count' => 'required|integer|min:1|max:60',
            's_next_due_on' => 'required|date',
            's_payment_method' => 'required|in:' . implode(',', array_keys(self::METHODS)),
            's_status' => 'required|in:active,paused,ended',
            's_auto_renew' => 'boolean',
            's_notes' => 'nullable|string',
        ]);

        $attributes = [
            'title' => $data['s_title'],
            'category' => $data['s_category'],
            'vendor' => $data['s_vendor'] ?: null,
            'currency' => $data['s_currency'],
            'amount_per_cycle' => $data['s_amount'],
            'cycle_unit' => $data['s_cycle_unit'],
            'cycle_count' => (int) $data['s_cycle_count'],
            'next_due_on' => $data['s_next_due_on'],
            'payment_method' => $data['s_payment_method'],
            'status' => $data['s_status'],
            'auto_renew' => $data['s_auto_renew'],
            'notes' => $data['s_notes'] ?: null,
        ];

        if ($this->scheduleId) {
            ExpenseSchedule::whereNull('server_id')->findOrFail($this->scheduleId)->update($attributes);
        } else {
            ExpenseSchedule::create($attributes + ['starts_on' => $data['s_next_due_on']]);
        }

        $this->showScheduleModal = false;
        $this->notification()->send(['icon' => 'success', 'title' => 'پلان پاشەکەوت کرا', 'description' => 'پلان پارەدان نییە؛ کاتێک پارەکەت دا «پارەدرا» دابگرە.']);
    }

    public function openPay(int $scheduleId): void
    {
        $s = ExpenseSchedule::findOrFail($scheduleId);
        $this->payScheduleId = $s->id;
        $this->pay_period_start = $s->next_due_on->toDateString();
        $this->pay_amount = (float) $s->amount_per_cycle;
        $this->pay_date = today()->toDateString();
        $this->pay_method = array_key_exists((string) $s->payment_method, self::METHODS) ? $s->payment_method : 'card';
        $this->pay_reference = '';
        $this->pay_notes = '';
        $this->resetErrorBag();
        $this->showPayModal = true;
    }

    public function confirmPay(ExpenseService $service): void
    {
        $this->validate([
            'pay_period_start' => 'required|date',
            'pay_amount' => 'required|numeric|gt:0',
            'pay_date' => 'required|date|before_or_equal:today',
            'pay_method' => 'required|in:' . implode(',', array_keys(self::METHODS)),
            'pay_reference' => 'nullable|string|max:255',
            'pay_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $expense = $service->paySchedule(ExpenseSchedule::findOrFail($this->payScheduleId), [
                'period_start' => $this->pay_period_start,
                'amount' => $this->pay_amount,
                'expense_date' => $this->pay_date,
                'payment_method' => $this->pay_method,
                'reference' => $this->pay_reference ?: null,
                'notes' => $this->pay_notes ?: null,
            ]);
        } catch (FinanceException $e) {
            $this->addError('pay_amount', $e->getMessage());

            return;
        }

        $this->showPayModal = false;
        $this->notification()->send([
            'icon' => 'success',
            'title' => 'پارەدان تۆمارکرا',
            'description' => "{$expense->title}: {$expense->period_start->toDateString()} → {$expense->period_end->toDateString()}",
        ]);
    }

    // ============================================================== helpers

    private function resetForm(): void
    {
        $this->resetErrorBag();
        $this->editingId = null;
        $this->title = '';
        $this->category = 'software_ai';
        $this->amount = 0.00;
        $this->currency = 'USD';
        $this->expense_date = Carbon::now()->format('Y-m-d');
        $this->payment_method = 'card';
        $this->vendor = '';
        $this->reference = '';
        $this->project_id = null;
        $this->notes = '';
    }

    private function resetScheduleForm(): void
    {
        $this->resetErrorBag();
        $this->scheduleId = null;
        $this->s_title = '';
        $this->s_category = 'software_ai';
        $this->s_vendor = '';
        $this->s_currency = 'USD';
        $this->s_amount = 0.00;
        $this->s_cycle_unit = 'month';
        $this->s_cycle_count = 1;
        $this->s_next_due_on = today()->toDateString();
        $this->s_payment_method = 'card';
        $this->s_status = 'active';
        $this->s_auto_renew = true;
        $this->s_notes = '';
    }

    public function render()
    {
        $expenses = Expense::with(['schedule', 'project'])
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('vendor', 'like', "%{$this->search}%")
                ->orWhere('notes', 'like', "%{$this->search}%")))
            ->when($this->categoryFilter !== 'all', fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->currencyFilter !== 'all', fn ($q) => $q->where('currency', $this->currencyFilter))
            ->when($this->statusFilter === 'posted', fn ($q) => $q->posted())
            ->when($this->statusFilter === 'void', fn ($q) => $q->where('status', 'void'))
            ->when($this->statusFilter === 'review', fn ($q) => $q->where('needs_review', true))
            ->orderByDesc('expense_date')->orderByDesc('id')
            ->paginate(15);

        $posted = fn (string $from, string $to) => Money::totals(
            Expense::posted()->whereBetween('expense_date', [$from, $to])->get(['amount', 'currency']),
            fn ($e) => $e->amount, fn ($e) => $e->currency,
        );

        $schedules = ExpenseSchedule::with('server.subscriptions.client')
            ->orderByRaw("case status when 'active' then 0 when 'paused' then 1 else 2 end")
            ->orderBy('next_due_on')
            ->get();
        $active = $schedules->where('status', 'active');

        return view('livewire.admin.expenses-manager', [
            'expenses' => $expenses,
            'paidThisMonth' => $posted(today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()),
            'paidThisYear' => $posted(today()->startOfYear()->toDateString(), today()->endOfYear()->toDateString()),
            'reviewCount' => Expense::where('needs_review', true)->count(),
            'schedules' => $schedules,
            'annualForecast' => Money::totals($active, fn ($s) => $s->annual_forecast, fn ($s) => $s->currency),
            'due30' => Money::totals($active->filter(fn ($s) => $s->next_due_on->lte(today()->addDays(30))), fn ($s) => $s->amount_per_cycle, fn ($s) => $s->currency),
            'payingSchedule' => $this->payScheduleId ? ExpenseSchedule::find($this->payScheduleId) : null,
            'payPeriodEnd' => $this->payScheduleId && $this->pay_period_start
                ? rescue(fn () => ExpenseSchedule::find($this->payScheduleId)?->periodEndFrom($this->pay_period_start)->toDateString(), null, false)
                : null,
            'projects' => Project::orderBy('title')->get(['id', 'title']),
            'categories' => self::CATEGORIES,
            'methods' => self::METHODS,
            'cycleUnits' => ['month' => 'مانگ', 'year' => 'ساڵ'],
        ])->layout('layouts.app', ['title' => 'بەڕێوەبردنی خەرجییەکان', 'header' => 'خەرجی تۆمارکراو و پلانی خەرجی دووبارە']);
    }
}
