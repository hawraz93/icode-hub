<?php

namespace Tests\Feature;

use App\Exceptions\FinanceException;
use App\Livewire\Admin\InvoicesManager;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\RenewalBot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentLedgerTest extends TestCase
{
    use RefreshDatabase;

    private PaymentService $payments;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->payments = app(PaymentService::class);
    }

    private function invoice(array $attrs = []): Invoice
    {
        $client = Client::create(['name' => 'Website client']);

        return Invoice::create(array_merge([
            'invoice_number' => 'INV-' . uniqid(), 'client_id' => $client->id,
            'issue_date' => '2026-10-07', 'due_date' => '2026-10-21',
            'subtotal' => 700, 'total' => 700, 'paid_amount' => 0, 'currency' => 'USD', 'status' => 'sent',
        ], $attrs));
    }

    public function test_development_plus_two_years_hosting_paid_in_two_instalments(): void
    {
        $client = Client::create(['name' => 'Website client']);
        Livewire::test(InvoicesManager::class)
            ->call('openModal')
            ->set('client_id', $client->id)
            ->set('due_date', '2026-10-21')
            ->set('issue_date', '2026-10-07')
            ->set('items.0.description', 'Website development')
            ->set('items.0.unit_price', 500)
            ->call('addItem')
            ->set('items.1.description', 'Hosting')
            ->set('items.1.service_type', 'hosting')
            ->set('items.1.billing_cycle', 'annual')
            ->set('items.1.quantity', 2)
            ->set('items.1.unit_price', 100)
            ->set('items.1.start_date', '2026-10-07')
            ->call('save')
            ->assertHasNoErrors();

        $invoice = Invoice::with('items')->firstOrFail();
        $this->assertSame('700.00', (string) $invoice->total);
        $hosting = $invoice->items->firstWhere('service_type', 'hosting');
        $this->assertSame('2028-10-07', $hosting->expiry_date->toDateString());
        $this->assertSame('2026-10-21', $invoice->due_date->toDateString()); // due date is not the hosting expiry

        $this->payments->record($invoice, 300, ['paid_on' => '2026-10-07']);
        $this->assertSame('partial', $invoice->fresh()->status);
        $this->payments->record($invoice, 400, ['paid_on' => '2026-11-01']);

        $invoice->refresh();
        $this->assertSame(2, $invoice->payments()->count());
        $this->assertSame('700.00', (string) $invoice->paid_amount);
        $this->assertSame(0.0, $invoice->remaining_balance);
        $this->assertSame('paid', $invoice->status);
        $this->assertSame('2028-10-07', $hosting->fresh()->expiry_date->toDateString()); // payments never move the expiry
    }

    public function test_overpayment_zero_negative_and_other_currency_are_refused(): void
    {
        $invoice = $this->invoice(['total' => 100, 'subtotal' => 100]);

        foreach ([[150, []], [0, []], [-5, []], [50, ['currency' => 'IQD']]] as [$amount, $opts]) {
            try {
                $this->payments->record($invoice, $amount, $opts);
                $this->fail("Payment of {$amount} should have been refused");
            } catch (FinanceException) {
            }
        }
        $this->assertSame(0, Payment::count());
        $this->assertSame('sent', $invoice->fresh()->status);
    }

    public function test_draft_and_cancelled_invoices_take_no_payment_and_cancelled_is_not_debt(): void
    {
        $draft = $this->invoice(['status' => 'draft']);
        $cancelled = $this->invoice(['status' => 'cancelled']);
        $open = $this->invoice();

        $this->expectExceptionCount(fn () => $this->payments->record($draft, 10));
        $this->expectExceptionCount(fn () => $this->payments->record($cancelled, 10));

        $this->assertSame([$open->id], Invoice::open()->pluck('id')->all());
    }

    public function test_paid_in_full_twice_records_one_payment_for_the_remaining_balance(): void
    {
        $invoice = $this->invoice();
        $this->payments->record($invoice, 200);

        $first = $invoice->markPaid();
        $second = $invoice->markPaid();

        $this->assertSame('500.00', (string) $first->amount);
        $this->assertNull($second);
        $this->assertSame(2, Payment::count());
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_same_idempotency_key_never_records_twice(): void
    {
        $invoice = $this->invoice();
        $a = $this->payments->record($invoice, 100, ['idempotency_key' => 'form-1']);
        $b = $this->payments->record($invoice, 100, ['idempotency_key' => 'form-1']);

        $this->assertSame($a->id, $b->id);
        $this->assertSame('100.00', (string) $invoice->fresh()->paid_amount);
    }

    public function test_reversal_keeps_the_original_and_reopens_the_balance(): void
    {
        $invoice = $this->invoice();
        $payment = $this->payments->record($invoice, 700);
        $this->assertSame('paid', $invoice->fresh()->status);

        $this->payments->reverse($payment, 'چەکەکە گەڕایەوە');

        $this->assertSame(2, Payment::count());
        $this->assertNotNull(Payment::find($payment->id));
        $this->assertSame('0.00', (string) $invoice->fresh()->paid_amount);
        $this->assertSame('sent', $invoice->fresh()->status);
        $this->expectExceptionCount(fn () => $this->payments->reverse($payment->fresh(), 'again'));
        // Cancelling now works because net payments are zero.
        PaymentService::assertCanCancel($invoice->fresh());
    }

    public function test_old_paid_amount_is_imported_once_without_inventing_a_date(): void
    {
        $invoice = $this->invoice(['paid_amount' => 200, 'status' => 'partial']);

        $this->payments->record($invoice, 100, ['paid_on' => '2026-10-10']);

        $legacy = Payment::where('source', 'legacy_import')->sole();
        $this->assertNull($legacy->paid_on);
        $this->assertTrue($legacy->needs_review);
        $this->assertSame('200.00', (string) $legacy->amount);
        $this->assertSame('300.00', (string) $invoice->fresh()->paid_amount);

        $this->payments->refresh($invoice);
        $this->assertSame(1, Payment::where('source', 'legacy_import')->count());
    }

    public function test_decimal_totals_are_exact(): void
    {
        $client = Client::create(['name' => 'Cents']);
        Livewire::test(InvoicesManager::class)
            ->call('openModal')
            ->set('client_id', $client->id)
            ->set('items.0.description', 'Small item')
            ->set('items.0.quantity', 3)
            ->set('items.0.unit_price', 0.1)
            ->set('discount', 0.05)
            ->call('save')
            ->assertHasNoErrors();

        $invoice = Invoice::firstOrFail();
        $this->assertSame('0.30', (string) $invoice->subtotal);
        $this->assertSame('0.25', (string) $invoice->total);
    }

    public function test_web_form_cannot_type_a_paid_amount_or_delete_a_paid_invoice(): void
    {
        $invoice = $this->invoice();
        $invoice->items()->create(['description' => 'Website', 'quantity' => 1, 'unit_price' => 700, 'total_price' => 700, 'service_type' => 'development']);
        $page = Livewire::test(InvoicesManager::class)
            ->call('openPayment', $invoice->id)
            ->assertSet('pay_amount', 700.0)
            ->set('pay_amount', 800)
            ->call('savePayment')
            ->assertHasErrors('pay_amount');
        $this->assertSame(0, Payment::count());

        $page->set('pay_amount', 700)->call('savePayment')->assertHasNoErrors();
        $page->call('savePayment'); // double submit of the same form
        $this->assertSame(1, Payment::count());

        Livewire::test(InvoicesManager::class)->call('delete', $invoice->id);
        $this->assertNotNull(Invoice::find($invoice->id));

        // Editing a paid invoice keeps it paid (status follows payments, not the form).
        Livewire::test(InvoicesManager::class)->call('edit', $invoice->id)->call('save')->assertHasNoErrors();
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_currency_of_an_invoice_with_payments_cannot_change(): void
    {
        $invoice = $this->invoice(['total' => 100, 'subtotal' => 100]);
        $invoice->items()->create(['description' => 'Hosting', 'quantity' => 1, 'unit_price' => 100, 'total_price' => 100, 'service_type' => 'hosting']);
        $this->payments->record($invoice, 50);

        Livewire::test(InvoicesManager::class)->call('edit', $invoice->id)->set('currency', 'IQD')->call('save')->assertHasErrors('currency');
        Livewire::test(InvoicesManager::class)->call('edit', $invoice->id)->set('discount', 80)->call('save')->assertHasErrors('discount');
        $this->assertSame('USD', $invoice->fresh()->currency);
    }

    public function test_dinar_invoice_never_shows_a_dollar_prefix(): void
    {
        $invoice = $this->invoice(['currency' => 'IQD', 'total' => 100000, 'subtotal' => 100000]);
        $invoice->items()->create(['description' => 'Domain', 'quantity' => 1, 'unit_price' => 100000, 'total_price' => 100000, 'service_type' => 'domain']);

        Livewire::test(InvoicesManager::class)
            ->call('viewInvoice', $invoice->id)
            ->assertSee('100,000 د.ع')
            ->assertDontSee('$100,000');
    }

    public function test_telegram_paid_button_pressed_twice_records_one_payment(): void
    {
        config(['services.telegram.bot_token' => 'TEST', 'services.telegram.chat_id' => '42']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 9]])]);
        $invoice = $this->invoice();
        $this->payments->record($invoice, 100);

        foreach (['cb1', 'cb2'] as $id) {
            $this->postJson('/telegram/webhook', ['callback_query' => [
                'id' => $id, 'data' => "v:{$invoice->id}", 'message' => ['message_id' => 9, 'chat' => ['id' => 42]],
            ]], ['X-Telegram-Bot-Api-Secret-Token' => RenewalBot::webhookSecret()])->assertOk();
        }

        $this->assertSame(2, Payment::count());
        $this->assertSame('600.00', (string) Payment::latest('id')->first()->amount);
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_search_does_not_bypass_the_status_filter(): void
    {
        $paid = $this->invoice(['invoice_number' => 'ICODE-INV-2026-100']);
        $this->payments->record($paid, 700);
        $this->invoice(['invoice_number' => 'ICODE-INV-2026-101']);

        Livewire::test(InvoicesManager::class)
            ->set('statusFilter', 'sent')
            ->set('search', 'ICODE-INV-2026-10')
            ->assertSee('ICODE-INV-2026-101')
            ->assertDontSee('ICODE-INV-2026-100');
    }

    private function expectExceptionCount(callable $fn): void
    {
        try {
            $fn();
        } catch (FinanceException) {
            $this->addToAssertionCount(1);

            return;
        }
        $this->fail('Expected a FinanceException');
    }
}
