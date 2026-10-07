<?php

namespace Tests\Feature;

use App\Livewire\Admin\ClientsManager;
use App\Livewire\Public\ClientPortal;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Server;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientPortalSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function client(string $name, array $attrs = []): Client
    {
        return Client::create(array_merge(['name' => $name, 'phone' => '07501234567', 'status' => 'active'], $attrs));
    }

    private function invoiceFor(Client $client, string $number): Invoice
    {
        return Invoice::create([
            'invoice_number' => $number, 'client_id' => $client->id, 'issue_date' => '2026-10-01', 'due_date' => '2026-10-15',
            'subtotal' => 100, 'total' => 100, 'currency' => 'USD', 'status' => 'sent',
        ]);
    }

    public function test_a_phone_number_or_part_of_one_never_logs_in(): void
    {
        $this->client('Ali', ['portal_access_code' => 'CL-AB12CD']);

        foreach (['07501234567', '1234567', '4567', 'CL-AB1', 'cl-ab12cd'] as $attempt) {
            Livewire::test(ClientPortal::class)->set('accessCode', $attempt)->call('login')->assertHasErrors('accessCode');
            $this->assertNull(session(ClientPortal::SESSION_KEY));
        }
    }

    public function test_legacy_code_works_until_a_new_code_is_issued(): void
    {
        $client = $this->client('Ali', ['portal_access_code' => 'CL-AB12CD']);
        Livewire::test(ClientPortal::class)->set('accessCode', 'CL-AB12CD')->call('login')->assertHasNoErrors();
        $this->assertSame($client->id, session(ClientPortal::SESSION_KEY));
        session()->flush();

        $this->actingAs(User::factory()->create());
        $code = Livewire::test(ClientsManager::class)->call('rotatePortalCode', $client->id)->get('issuedPortalCode');

        $client->refresh();
        $this->assertNull($client->portal_access_code);
        $this->assertSame(hash('sha256', $code), $client->portal_token_hash); // only the hash is stored
        $this->assertGreaterThanOrEqual(30, strlen($code));

        Livewire::test(ClientPortal::class)->set('accessCode', 'CL-AB12CD')->call('login')->assertHasErrors('accessCode');
        Livewire::test(ClientPortal::class)->set('accessCode', $code)->call('login')->assertHasNoErrors();
        $this->assertSame($client->id, session(ClientPortal::SESSION_KEY));
    }

    public function test_inactive_or_revoked_clients_cannot_log_in(): void
    {
        $client = $this->client('Ali');
        $code = $client->rotatePortalToken();
        $client->update(['status' => 'inactive']);
        Livewire::test(ClientPortal::class)->set('accessCode', $code)->call('login')->assertHasErrors('accessCode');

        $client->update(['status' => 'active']);
        $client->revokePortalAccess();
        Livewire::test(ClientPortal::class)->set('accessCode', $code)->call('login')->assertHasErrors('accessCode');
    }

    public function test_client_a_cannot_open_client_b_records(): void
    {
        $a = $this->client('Client A');
        $b = $this->client('Client B', ['phone' => '07709999999']);
        $this->invoiceFor($a, 'INV-A-1');
        $theirs = $this->invoiceFor($b, 'INV-B-SECRET');
        $theirContract = Contract::create(['contract_number' => 'CNT-B', 'client_id' => $b->id, 'title' => 'B contract', 'start_date' => '2026-01-01', 'status' => 'active']);

        session([ClientPortal::SESSION_KEY => $a->id]);
        $page = Livewire::test(ClientPortal::class)->assertSee('INV-A-1')->assertDontSee('INV-B-SECRET');

        try {
            $page->call('viewInvoice', $theirs->id);
            $this->fail('Another client invoice was opened');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
        }
        try {
            $page->call('viewContract', $theirContract->id);
            $this->fail('Another client contract was opened');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
        }

        // Tampering with the public ID property does not reveal the record either.
        Livewire::test(ClientPortal::class)->set('selectedInvoiceId', $theirs->id)->set('showInvoiceModal', true)
            ->assertDontSee('INV-B-SECRET');
    }

    public function test_actions_require_a_signed_in_client(): void
    {
        $other = $this->client('Victim');
        $invoice = $this->invoiceFor($other, 'INV-V-1');

        Livewire::test(ClientPortal::class)
            ->set('ticket_subject', 'Hi')->set('ticket_description', 'Help')
            ->call('submitTicket')
            ->assertForbidden();
        Livewire::test(ClientPortal::class)->call('viewInvoice', $invoice->id)->assertForbidden();

        $this->assertSame(0, Ticket::count());
    }

    public function test_ticket_goes_to_the_session_client_only(): void
    {
        $a = $this->client('Client A');
        session([ClientPortal::SESSION_KEY => $a->id]);

        Livewire::test(ClientPortal::class)->set('ticket_subject', 'Email down')->set('ticket_description', 'Help')->call('submitTicket');

        $this->assertSame([$a->id], Ticket::pluck('client_id')->all());
    }

    public function test_portal_never_shows_internal_costs_ip_or_credentials(): void
    {
        $client = $this->client('Client A');
        $server = Server::create(['name' => 'Secret VPS', 'ip_address' => '203.0.113.77', 'cost' => 20, 'renewal_date' => '2026-12-01']);
        Subscription::create([
            'client_id' => $client->id, 'server_id' => $server->id, 'name' => 'Hosting', 'type' => 'hosting', 'domain_name' => 'a.com',
            'cost_price' => 37.5, 'selling_price' => 100, 'start_date' => '2026-01-01', 'expiry_date' => '2027-01-01',
            'credentials_note' => 'cpanel pass hunter2', 'notes' => 'internal note xyz',
        ]);
        session([ClientPortal::SESSION_KEY => $client->id]);

        Livewire::test(ClientPortal::class)
            ->assertSee('a.com')
            ->assertDontSee('203.0.113.77')
            ->assertDontSee('Secret VPS')
            ->assertDontSee('hunter2')
            ->assertDontSee('internal note xyz')
            ->assertDontSee('37.5');
    }

    public function test_repeated_wrong_codes_are_rate_limited(): void
    {
        $client = $this->client('Ali');
        $code = $client->rotatePortalToken();

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(ClientPortal::class)->set('accessCode', 'ICP-WRONG-' . $i . 'xxxxxxxx')->call('login');
        }
        Livewire::test(ClientPortal::class)->set('accessCode', $code)->call('login')->assertHasErrors('accessCode');
        $this->assertNull(session(ClientPortal::SESSION_KEY));
    }

    public function test_public_quote_form_does_not_hand_out_portal_access(): void
    {
        Livewire::test(\App\Livewire\Public\PortfolioHome::class)
            ->set('req_name', 'Lead')->set('req_phone', '07500000000')->set('req_service', 'web')->set('req_message', 'hi')
            ->call('submitQuote');

        $lead = Client::where('name', 'Lead')->first();
        if ($lead) {
            $this->assertFalse($lead->has_portal_access);
        } else {
            $this->markTestSkipped('Quote form did not create a client in this configuration.');
        }
    }
}
