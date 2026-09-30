<?php

namespace App\Livewire\Admin;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Server;
use App\Models\Subscription;
use Carbon\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public float $exchangeRate = 1500; // 1 USD = 1,500 IQD

    public function render()
    {
        // 1. Clients & Services
        $totalClients = Client::count();
        $activeClients = Client::where('status', 'active')->count();

        $activeSubscriptions = Subscription::where('status', 'active')->get();
        $totalActiveSubscriptionsCount = $activeSubscriptions->count();

        // 2. Client Revenue (Purely from paying clients)
        $totalAnnualRevenueUsd = 0;
        foreach ($activeSubscriptions as $sub) {
            $selling = (float) $sub->selling_price;
            $multiplier = match ($sub->billing_cycle) {
                'biennial' => 0.5,
                'semi_annual' => 2.0,
                'quarterly' => 4.0,
                'monthly' => 12.0,
                default => 1.0, // annual
            };
            $totalAnnualRevenueUsd += ($selling * $multiplier);
        }

        // 3. Company Expenses (Purely from Expenses table - Single Source of Truth)
        $allExpenses = Expense::all();
        $totalAnnualCostsUsd = 0;
        foreach ($allExpenses as $exp) {
            $amount = (float) $exp->amount;
            $amountUsd = $exp->currency === 'IQD' ? ($amount / $this->exchangeRate) : $amount;

            $multiplier = match ($exp->billing_cycle) {
                'monthly' => 12.0,
                'annual' => 1.0,
                default => 1.0, // one_time
            };
            $totalAnnualCostsUsd += ($amountUsd * $multiplier);
        }

        // 4. Net Profit
        $annualNetProfitUsd = max(0, $totalAnnualRevenueUsd - $totalAnnualCostsUsd);

        // IQD Equivalents
        $totalAnnualRevenueIqd = $totalAnnualRevenueUsd * $this->exchangeRate;
        $totalAnnualCostsIqd = $totalAnnualCostsUsd * $this->exchangeRate;
        $annualNetProfitIqd = $annualNetProfitUsd * $this->exchangeRate;

        // Monthly Figures
        $monthlyRevenueUsd = $totalAnnualRevenueUsd / 12;
        $monthlyCostsUsd = $totalAnnualCostsUsd / 12;
        $monthlyNetProfitUsd = $monthlyRevenueUsd - $monthlyCostsUsd;

        $monthlyRevenueIqd = $monthlyRevenueUsd * $this->exchangeRate;
        $monthlyCostsIqd = $monthlyCostsUsd * $this->exchangeRate;
        $monthlyNetProfitIqd = $monthlyNetProfitUsd * $this->exchangeRate;

        // Profit Margin
        $profitMargin = $totalAnnualRevenueUsd > 0 ? round(($annualNetProfitUsd / $totalAnnualRevenueUsd) * 100, 1) : 0;

        // 5. Subscriptions Expiring in Next 30 Days (Client Collections)
        $expiringSubscriptions = Subscription::with(['client', 'server'])
            ->openRenewals(30)
            ->orderBy('expiry_date', 'asc')
            ->get();

        // 6. Servers
        $activeServers = Server::where('status', 'active')->get();

        // 7. 12-Month Chart Simulation
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $chartRevenue = array_fill(0, 12, round($monthlyRevenueUsd, 2));
        $chartCosts = array_fill(0, 12, round($monthlyCostsUsd, 2));
        $chartProfit = array_fill(0, 12, round($monthlyNetProfitUsd, 2));

        return view('livewire.admin.dashboard', [
            'totalClients' => $totalClients,
            'activeClients' => $activeClients,
            'totalActiveSubscriptionsCount' => $totalActiveSubscriptionsCount,
            'totalAnnualRevenueUsd' => $totalAnnualRevenueUsd,
            'totalAnnualCostsUsd' => $totalAnnualCostsUsd,
            'annualNetProfitUsd' => $annualNetProfitUsd,
            'totalAnnualRevenueIqd' => $totalAnnualRevenueIqd,
            'totalAnnualCostsIqd' => $totalAnnualCostsIqd,
            'annualNetProfitIqd' => $annualNetProfitIqd,
            'monthlyRevenueUsd' => $monthlyRevenueUsd,
            'monthlyCostsUsd' => $monthlyCostsUsd,
            'monthlyNetProfitUsd' => $monthlyNetProfitUsd,
            'monthlyRevenueIqd' => $monthlyRevenueIqd,
            'monthlyCostsIqd' => $monthlyCostsIqd,
            'monthlyNetProfitIqd' => $monthlyNetProfitIqd,
            'profitMargin' => $profitMargin,
            'allExpenses' => $allExpenses,
            'expiringSubscriptions' => $expiringSubscriptions,
            'servers' => $activeServers,
            'months' => $months,
            'chartRevenue' => $chartRevenue,
            'chartCosts' => $chartCosts,
            'chartProfit' => $chartProfit,
        ])->layout('layouts.app', ['title' => 'داشبۆردی دارایی و کارگێڕی', 'header' => 'داشبۆردی سەرەکی داهات، خەرجی و قازانج']);
    }
}
