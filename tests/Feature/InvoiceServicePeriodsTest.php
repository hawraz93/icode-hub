<?php

namespace Tests\Feature;

use App\Livewire\Admin\InvoicesManager;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvoiceServicePeriodsTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_year_hosting_is_billed_alongside_one_time_development(): void
    {
        $client = Client::create(['name' => 'Website client']);
        Livewire::test(InvoicesManager::class)
            ->call('openModal')
            ->set('client_id', $client->id)
            ->set('items.0.description', 'Website development')
            ->set('items.0.unit_price', 500)
            ->call('addItem')
            ->set('items.1.description', 'Hosting')
            ->set('items.1.service_type', 'hosting')
            ->set('items.1.billing_cycle', 'annual')
            ->set('items.1.quantity', 2)
            ->set('items.1.unit_price', 100)
            ->set('items.1.start_date', '2026-10-07')
            ->assertSet('items.1.expiry_date', '2028-10-07')
            ->call('save')
            ->assertHasNoErrors();

        $invoice = Invoice::with('items')->firstOrFail();
        $this->assertEquals(700, $invoice->total);
        $hosting = $invoice->items->firstWhere('service_type', 'hosting');
        $this->assertEquals(200, $hosting->total_price);
        $this->assertSame('2028-10-07', $hosting->expiry_date->format('Y-m-d'));
        $this->assertNull($invoice->items->firstWhere('service_type', 'development')->expiry_date);
    }

    public function test_recurring_service_cannot_expire_before_it_starts(): void
    {
        Livewire::test(InvoicesManager::class)
            ->call('openModal')
            ->set('client_id', Client::create(['name' => 'Client'])->id)
            ->set('items.0.description', 'Hosting')
            ->set('items.0.billing_cycle', 'annual')
            ->set('items.0.start_date', '2026-10-07')
            ->set('items.0.expiry_date', '2026-10-06')
            ->call('save')
            ->assertHasErrors(['items.0.expiry_date']);
        $this->assertSame(0, Invoice::count());
    }
}
