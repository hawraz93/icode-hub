<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\ExpenseSchedule;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Server;
use App\Models\Subscription;
use App\Services\ExpenseService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\ServicePeriodService;
use App\Support\Decimal;
use App\Support\Money;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Moves existing data into the new finance model without inventing money or dates:
 *  - every Server gets exactly one ExpenseSchedule (no historical expenses are created from it);
 *  - an invoice's old paid_amount becomes one imported payment (paid_on stays null if unknown);
 *  - old expenses marked monthly/annual are flagged needs_review, never auto-converted.
 * Safe to run repeatedly: a second run changes nothing. Always start with --dry-run.
 */
class FinanceBackfill extends Command
{
    protected $signature = 'finance:backfill
                            {--dry-run : Report what would change without writing anything}
                            {--report= : Path (under storage/app) for the JSON review report}';

    protected $description = 'Import existing invoices, servers and expenses into the payments / expense-schedule ledger (idempotent)';

    public function handle(ExpenseService $expenses, PaymentService $payments, ServicePeriodService $periods): int
    {
        $dry = (bool) $this->option('dry-run');
        $this->info($dry ? '— DRY RUN: هیچ شتێک ناگۆڕدرێت —' : '— Applying backfill —');

        $before = $this->snapshot();
        $review = [];

        // 1. Servers -> one schedule each
        $serversWithout = Server::whereDoesntHave('schedule')->get();
        $this->line("Servers without an expense schedule: {$serversWithout->count()}");
        foreach ($serversWithout as $server) {
            if (! $dry) {
                $expenses->syncServerSchedule($server);
            }
        }

        // 2. Invoices -> opening payments
        $plans = Invoice::whereDoesntHave('payments')->get()
            ->map(fn (Invoice $inv) => ['invoice' => $inv, 'plan' => PaymentService::openingBalancePlan($inv)])
            ->filter(fn ($x) => $x['plan'] !== null);
        $unknownDate = $plans->filter(fn ($x) => $x['plan']['paid_on'] === null)->count();
        $flagged = $plans->filter(fn ($x) => $x['plan']['needs_review'])->count();
        // "paid" status with a smaller paid_amount: the imported amount is the full total (flagged).
        $adjusted = $plans->filter(fn ($x) => ! Decimal::of($x['plan']['amount'])->isEqualTo(Decimal::of($x['invoice']->paid_amount)))->count();
        $this->line("Invoices whose paid_amount becomes an imported payment: {$plans->count()} (unknown date: {$unknownDate}, needs review: {$flagged})");
        $this->line('  amounts: ' . Money::formatTotals(Money::totals($plans, fn ($x) => $x['plan']['amount'], fn ($x) => $x['plan']['currency']), '0'));
        foreach ($plans as $x) {
            if ($x['plan']['needs_review']) {
                $review[] = ['type' => 'payment_import', 'invoice' => $x['invoice']->invoice_number, 'amount' => $x['plan']['amount'], 'currency' => $x['plan']['currency'], 'note' => $x['plan']['notes']];
            }
            if (! $dry) {
                DB::transaction(fn () => $payments->refresh($x['invoice']));
            }
        }

        // 3. Legacy recurring-looking expenses -> needs_review (never auto-converted)
        $ambiguous = Expense::whereIn('billing_cycle', ['monthly', 'annual', 'biennial', 'quarterly', 'semi_annual'])
            ->whereNull('expense_schedule_id')->whereNull('review_note')->where('needs_review', false)->get();
        $this->line("Old expenses marked monthly/annual (meaning unclear, flagged for review): {$ambiguous->count()}");
        foreach ($ambiguous as $e) {
            $review[] = ['type' => 'expense_cycle', 'expense_id' => $e->id, 'title' => $e->title, 'amount' => (string) $e->amount, 'currency' => $e->currency, 'billing_cycle' => $e->billing_cycle];
            if (! $dry) {
                $e->forceFill(['needs_review' => true])->save();
            }
        }

        // 3b. Services -> their current period (price kept as stored: a biennial price is the 2-year price)
        $withoutPeriod = Subscription::whereDoesntHave('periods')->whereNotNull('start_date')->whereNotNull('expiry_date')->get();
        $odd = $withoutPeriod->filter(fn ($s) => ServicePeriodService::importPlan($s)['notes'] !== null);
        $this->line("Services without a period history (current period imported): {$withoutPeriod->count()} (length differs from billing cycle: {$odd->count()})");
        foreach ($odd as $s) {
            $review[] = ['type' => 'service_period_length', 'subscription_id' => $s->id, 'name' => $s->name, 'start' => $s->start_date?->toDateString(), 'expiry' => $s->expiry_date?->toDateString(), 'billing_cycle' => $s->billing_cycle];
        }
        if (! $dry) {
            $withoutPeriod->each(fn ($s) => $periods->importCurrent($s));
        }

        // 3c. cost_basis, only where the old data is unambiguous
        $unknown = Subscription::where('cost_basis', 'unknown')->get();
        $shared = $unknown->filter(fn ($s) => $s->server_id && Decimal::of($s->cost_price)->isZero());
        $direct = $unknown->filter(fn ($s) => ! $s->server_id && Decimal::of($s->cost_price)->isGreaterThan(0));
        $conflict = $unknown->filter(fn ($s) => $s->server_id && Decimal::of($s->cost_price)->isGreaterThan(0));
        $this->line("Cost basis: shared VPS {$shared->count()} · direct purchase {$direct->count()} · unclear (VPS + own price, left unknown) {$conflict->count()}");
        foreach ($conflict as $s) {
            $review[] = ['type' => 'cost_basis_unclear', 'subscription_id' => $s->id, 'name' => $s->name, 'cost_price' => (string) $s->cost_price];
        }
        if (! $dry) {
            Subscription::whereKey($shared->modelKeys())->update(['cost_basis' => 'shared_infrastructure']);
            Subscription::whereKey($direct->modelKeys())->update(['cost_basis' => 'direct_purchase']);
        }

        // 3d. Issued invoices keep the client details as of now, so later renames do not rewrite them.
        $noSnapshot = Invoice::whereNotIn('status', ['draft'])->whereNull('client_snapshot')->with('client')->get();
        $this->line("Issued invoices without a client snapshot (captured now): {$noSnapshot->count()}");
        if (! $dry) {
            $noSnapshot->each(fn ($inv) => $inv->forceFill(['client_snapshot' => InvoiceService::clientSnapshot($inv->client) + ['captured_at' => now()->toDateString()]])->saveQuietly());
        }

        // 3e. Project links are suggested, never guessed.
        $candidates = Subscription::whereNull('project_id')->get()->filter(function ($s) {
            return Project::active()->where('client_id', $s->client_id)->count() === 1;
        });
        $this->line("Services that could belong to their client's only project (report only): {$candidates->count()}");
        foreach ($candidates as $s) {
            $review[] = ['type' => 'project_link_candidate', 'subscription_id' => $s->id, 'name' => $s->name, 'project_id' => Project::active()->where('client_id', $s->client_id)->value('id')];
        }
        $undatedItems = InvoiceItem::whereIn('billing_cycle', ['monthly', 'annual'])->where(fn ($q) => $q->whereNull('start_date')->orWhereNull('expiry_date'))->count();
        $this->line("Recurring invoice lines without a period (report only): {$undatedItems}");

        // 4. Report-only checks: nothing is changed for these.
        $dupes = $this->duplicateVpsCandidates();
        $this->line("Possible duplicate VPS expenses (report only, nothing deleted): " . count($dupes));
        array_push($review, ...$dupes);

        $paidWithoutMoney = Subscription::where('is_paid', true)->whereDoesntHave('invoices')->count();
        $this->line("Services flagged 'paid' with no invoice/payment (no cash is created for them): {$paidWithoutMoney}");

        $badCurrency = Invoice::whereNotIn('currency', ['USD', 'IQD'])->pluck('invoice_number')->all();
        $badDates = Invoice::whereColumn('due_date', '<', 'issue_date')->pluck('invoice_number')->all();
        $badPeriods = Subscription::whereColumn('expiry_date', '<=', 'start_date')->pluck('id')->all();
        $orphanServer = Subscription::whereNotNull('server_id')->whereDoesntHave('server')->pluck('id')->all();
        $this->line('Invoices with unknown currency: ' . count($badCurrency) . ' · due before issue: ' . count($badDates)
            . ' · services with expiry <= start: ' . count($badPeriods) . ' · services linked to a missing server: ' . count($orphanServer));
        foreach ($badCurrency as $n) { $review[] = ['type' => 'invoice_currency', 'invoice' => $n]; }
        foreach ($badDates as $n) { $review[] = ['type' => 'invoice_due_before_issue', 'invoice' => $n]; }
        foreach ($badPeriods as $id) { $review[] = ['type' => 'service_period', 'subscription_id' => $id]; }
        foreach ($orphanServer as $id) { $review[] = ['type' => 'service_missing_server', 'subscription_id' => $id]; }

        // Rows flagged on an earlier run and still waiting for a human.
        foreach (Payment::with('invoice')->where('needs_review', true)->whereNotIn('invoice_id', $plans->pluck('invoice.id'))->get() as $p) {
            $review[] = ['type' => 'payment_import', 'invoice' => $p->invoice?->invoice_number, 'amount' => (string) $p->amount, 'currency' => $p->currency, 'note' => $p->notes];
        }
        foreach (Expense::where('needs_review', true)->whereNotIn('id', $ambiguous->pluck('id'))->get() as $e) {
            $review[] = ['type' => 'expense_cycle', 'expense_id' => $e->id, 'title' => $e->title, 'amount' => (string) $e->amount, 'currency' => $e->currency, 'billing_cycle' => $e->billing_cycle];
        }

        // 5. Before / after comparison
        $after = $dry ? $before : $this->snapshot();
        $this->newLine();
        $this->table(['check', 'before', 'after'], [
            ['invoices', $before['invoices'], $after['invoices']],
            ['invoice totals', $before['invoice_totals'], $after['invoice_totals']],
            ['invoice paid_amount', $before['paid_amounts'], $after['paid_amounts']],
            ['payments (net)', $before['payments'], $after['payments']],
            ['expenses (rows / posted sum)', $before['expenses'], $after['expenses']],
            ['expense schedules', $before['schedules'], $after['schedules']],
            ['service periods', $before['service_periods'], $after['service_periods']],
        ]);

        $failures = 0;
        if (! $dry) {
            if ($adjusted && $before['paid_amounts'] !== $after['paid_amounts']) {
                $this->warn("paid_amount changed on {$adjusted} invoice(s) marked «paid» without the full amount; they are listed for review.");
            }
            foreach (array_merge(['invoices', 'invoice_totals', 'expenses'], $adjusted ? [] : ['paid_amounts']) as $key) {
                if ($before[$key] !== $after[$key]) {
                    $this->error("Mismatch in {$key}: before {$before[$key]} / after {$after[$key]}");
                    $failures++;
                }
            }
            // Every invoice's cached paid_amount must equal its payment rows.
            foreach (Invoice::with('payments')->get() as $inv) {
                if (! Decimal::of($inv->paid_amount)->isEqualTo(Decimal::sum($inv->payments->pluck('amount'))) && $inv->payments->isNotEmpty()) {
                    $this->error("Invoice {$inv->invoice_number}: paid_amount {$inv->paid_amount} ≠ payments " . Decimal::sum($inv->payments->pluck('amount')));
                    $failures++;
                }
            }
        }

        $path = $this->option('report') ?: 'finance-backfill-' . now()->format('Ymd-His') . ($dry ? '-dry' : '') . '.json';
        Storage::disk('local')->put($path, json_encode(['dry_run' => $dry, 'before' => $before, 'after' => $after, 'needs_review' => $review], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info(count($review) . ' record(s) need manual review → storage/app/private/' . $path);

        return $failures ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<string, string|int>
     */
    private function snapshot(): array
    {
        $fmt = fn (array $t) => Money::formatTotals($t, '0');
        $invoices = Invoice::get(['total', 'paid_amount', 'currency']);

        return [
            'invoices' => $invoices->count(),
            'invoice_totals' => $fmt(Money::totals($invoices, fn ($i) => $i->total, fn ($i) => $i->currency)),
            'paid_amounts' => $fmt(Money::totals($invoices, fn ($i) => $i->paid_amount, fn ($i) => $i->currency)),
            'payments' => Payment::count() . ' / ' . $fmt(Money::totals(Payment::get(['amount', 'currency']), fn ($p) => $p->amount, fn ($p) => $p->currency)),
            'expenses' => Expense::count() . ' / ' . $fmt(Money::totals(Expense::posted()->get(['amount', 'currency']), fn ($e) => $e->amount, fn ($e) => $e->currency)),
            'schedules' => ExpenseSchedule::count(),
            'service_periods' => \App\Models\ServicePeriod::count(),
        ];
    }

    /**
     * Expense rows that may duplicate a server's cost (same amount and currency, title or vendor
     * mentioning the server or its provider). Reported only; a human decides.
     *
     * @return array<int, array<string, mixed>>
     */
    private function duplicateVpsCandidates(): array
    {
        $found = [];
        foreach (Server::all() as $server) {
            $needles = array_filter([mb_strtolower($server->name), mb_strtolower((string) $server->provider)], fn ($n) => mb_strlen($n) >= 3);
            Expense::posted()->whereNull('expense_schedule_id')
                ->where('currency', $server->currency)
                ->where('amount', $server->cost)
                ->get()
                ->filter(fn ($e) => collect($needles)->contains(fn ($n) => str_contains(mb_strtolower($e->title . ' ' . $e->vendor), $n)))
                ->each(function ($e) use (&$found, $server) {
                    $found[] = ['type' => 'duplicate_vps_candidate', 'expense_id' => $e->id, 'title' => $e->title, 'server' => $server->name, 'amount' => (string) $e->amount, 'currency' => $e->currency];
                });
        }

        return $found;
    }
}
