<?php

namespace App\Livewire\Admin;

use App\Models\ActivityReminder;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Support\Money;
use Livewire\Component;

/**
 * One project as the centre of the work: client, services and their periods, invoices,
 * payments, contracts, direct costs and history. Amounts stay per currency.
 * Shared VPS cost is not allocated here, so no "real profit" figure is shown.
 */
class ProjectShow extends Component
{
    public Project $project;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    public function archive(): void
    {
        $this->project->update(['archived_at' => now(), 'is_public' => false]);
    }

    public function unarchive(): void
    {
        $this->project->update(['archived_at' => null]);
    }

    public function render()
    {
        $project = $this->project->load([
            'client',
            'contracts',
            'subscriptions' => fn ($q) => $q->with(['server', 'periods'])->orderBy('expiry_date'),
        ]);

        $invoices = Invoice::with(['items', 'payments'])->where('project_id', $project->id)->latest('issue_date')->get();
        $billed = $invoices->whereNotIn('status', ['draft', 'cancelled']);
        $cur = fn ($x) => $x->currency;
        $payments = Payment::with('invoice')->whereIn('invoice_id', $invoices->modelKeys())->orderByDesc('paid_on')->get();
        $directCosts = Expense::posted()->where('project_id', $project->id)->orderByDesc('expense_date')->get();

        $oneTimeLines = $billed->flatMap(fn ($i) => $i->items->where('billing_cycle', 'one_time')->map(fn ($item) => ['item' => $item, 'currency' => $i->currency]));

        return view('livewire.admin.project-show', [
            'project' => $project,
            'invoices' => $invoices,
            'payments' => $payments,
            'invoiced' => Money::totals($billed, fn ($i) => $i->total, $cur),
            'received' => Money::totals($billed, fn ($i) => $i->paid_amount, $cur),
            'balance' => Money::totals($billed->whereIn('status', ['sent', 'partial', 'overdue']), fn ($i) => $i->remaining_balance, $cur),
            'buildPrice' => Money::totals($oneTimeLines, fn ($x) => $x['item']->total_price, fn ($x) => $x['currency']),
            'directCosts' => $directCosts,
            'directCostTotals' => Money::totals($directCosts, fn ($e) => $e->amount, $cur),
            'history' => ActivityReminder::whereIn('subscription_id', $project->subscriptions->modelKeys())
                ->orWhereIn('invoice_id', $invoices->modelKeys())
                ->latest()->take(20)->get(),
        ])->layout('layouts.app', ['title' => $project->title, 'header' => 'پڕۆژە: ' . $project->title]);
    }
}
