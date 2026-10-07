<?php

namespace App\Livewire\Admin;

use App\Models\Client;
use App\Models\Expense;
use App\Models\ExpenseSchedule;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Server;
use App\Models\Subscription;
use App\Support\Money;
use Carbon\Carbon;
use Livewire\Component;

/**
 * Financial overview. Every total is kept per currency: dollars and dinars are never converted or added.
 */
class Dashboard extends Component
{
    public function render()
    {
        $cur = fn ($x) => $x->currency;

        // Income: what clients pay per year for active services (biennial = half, monthly = x12...)
        $activeSubscriptions = Subscription::where('status', 'active')->get();
        $annualRevenue = Money::totals($activeSubscriptions, fn ($s) => $s->annual_selling, $cur);

        // Cost forecast: recurring plans only (each VPS is one plan, counted once). Ledger rows are
        // money already paid and are never annualised; servers without a plan yet (before
        // finance:backfill) fall back to their own price so nothing silently disappears.
        $ownServices = Server::with('schedule')->where('status', 'active')->orderBy('renewal_date')->get();
        $schedules = ExpenseSchedule::active()->get();
        $annualCosts = Money::totals(
            $schedules->map(fn ($s) => ['amount' => $s->annual_forecast, 'currency' => $s->currency])
                ->concat($ownServices->whereNull('schedule')->map(fn ($s) => ['amount' => $s->annual_cost, 'currency' => $s->currency])),
            fn ($x) => $x['amount'],
            fn ($x) => $x['currency'],
        );

        // Cash this month: money actually received and actually paid, by date.
        $monthFrom = Carbon::today()->startOfMonth()->toDateString();
        $monthTo = Carbon::today()->endOfMonth()->toDateString();
        $receivedThisMonth = Money::totals(Payment::receivedBetween($monthFrom, $monthTo)->get(['amount', 'currency']), fn ($p) => $p->amount, $cur);
        $spentThisMonth = Money::totals(Expense::posted()->whereBetween('expense_date', [$monthFrom, $monthTo])->get(['amount', 'currency']), fn ($e) => $e->amount, $cur);
        $expenses = Expense::posted()->orderByDesc('expense_date')->take(8)->get();

        $annualProfit = Money::subtract($annualRevenue, $annualCosts);
        $monthly = fn (array $totals) => array_map(fn ($v) => $v / 12, $totals);

        $unpaid = Subscription::with('client')->unpaid()->get();

        $expiringSubscriptions = Subscription::with(['client', 'server'])
            ->openRenewals(30)
            ->orderBy('expiry_date')
            ->get();

        return view('livewire.admin.dashboard', [
            'activeCount' => $activeSubscriptions->count(),
            'clientCount' => Client::where('status', 'active')->count(),
            'annualRevenue' => $annualRevenue,
            'annualCosts' => $annualCosts,
            'annualProfit' => $annualProfit,
            'monthlyRevenue' => $monthly($annualRevenue),
            'monthlyCosts' => $monthly($annualCosts),
            'monthlyProfit' => $monthly($annualProfit),
            'unpaid' => $unpaid,
            'unpaidTotals' => Money::totals($unpaid, fn ($s) => $s->selling_price, $cur),
            'ownServices' => $ownServices,
            'expenses' => $expenses,
            'receivedThisMonth' => $receivedThisMonth,
            'spentThisMonth' => $spentThisMonth,
            'cashDifference' => Money::subtract($receivedThisMonth, $spentThisMonth),
            'openDebt' => Money::totals(Invoice::open()->get(['total', 'paid_amount', 'currency']), fn ($i) => $i->remaining_balance, $cur),
            'reviewCount' => Expense::where('needs_review', true)->count() + Payment::where('needs_review', true)->count(),
            'expiringSubscriptions' => $expiringSubscriptions,
        ])->layout('layouts.app', ['title' => 'داشبۆردی دارایی و کارگێڕی', 'header' => 'داشبۆردی سەرەکی داهات، خەرجی و قازانج']);
    }
}
