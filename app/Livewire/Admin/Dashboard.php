<?php

namespace App\Livewire\Admin;

use App\Models\Client;
use App\Models\Expense;
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

        // Expenses: your own services (servers, domains, email...) + recorded expenses
        $ownServices = Server::where('status', 'active')->orderBy('renewal_date')->get();
        $expenses = Expense::orderByDesc('expense_date')->get();
        $yearStart = Carbon::today()->startOfYear();
        $annualCosts = Money::totals(
            $ownServices->map(fn ($s) => ['amount' => $s->annual_cost, 'currency' => $s->currency])
                ->concat($expenses->map(fn ($e) => [
                    'amount' => match ($e->billing_cycle) {
                        'monthly' => (float) $e->amount * 12,
                        'annual' => (float) $e->amount,
                        // one-time costs count in the year they were paid
                        default => $e->expense_date && $e->expense_date->gte($yearStart) ? (float) $e->amount : 0,
                    },
                    'currency' => $e->currency,
                ])),
            fn ($x) => $x['amount'],
            fn ($x) => $x['currency'],
        );

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
            'expenses' => $expenses->take(8),
            'expiringSubscriptions' => $expiringSubscriptions,
        ])->layout('layouts.app', ['title' => 'داشبۆردی دارایی و کارگێڕی', 'header' => 'داشبۆردی سەرەکی داهات، خەرجی و قازانج']);
    }
}
