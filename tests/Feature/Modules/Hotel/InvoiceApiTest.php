<?php

namespace Tests\Feature\Modules\Hotel;

use App\Models\User;
use App\Modules\Hotel\Models\Guest;
use App\Modules\Hotel\Models\Invoice;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class InvoiceApiTest extends TenantTestCase
{
    protected User $user;
    protected Guest $guest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->guest = Guest::factory()->create();
    }

    protected function headers(): self
    {
        return $this->withHeader('X-Tenant', $this->tenant->slug);
    }

    protected function makeInvoice(array $overrides = []): Invoice
    {
        return Invoice::create(array_merge([
            'invoice_number' => 'FACT-' . strtoupper(\Illuminate\Support\Str::random(6)),
            'guest_id'       => $this->guest->id,
            'status'         => 'draft',
            'subtotal'       => 100000,
            'tax_amount'     => 0,
            'total'          => 100000,
        ], $overrides));
    }

    public function test_it_lists_invoices_filtered_by_status(): void
    {
        $this->makeInvoice(['status' => 'draft']);
        $this->makeInvoice(['status' => 'issued']);

        $response = $this->headers()->getJson('/api/hotel/invoices?status=issued');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('issued', $response->json('data.0.status'));
    }

    public function test_it_shows_an_invoice_with_relations(): void
    {
        $invoice = $this->makeInvoice();

        $response = $this->headers()->getJson("/api/hotel/invoices/{$invoice->id}");

        $response->assertOk()
            ->assertJsonPath('id', $invoice->id)
            ->assertJsonPath('guest.id', $this->guest->id)
            ->assertJsonPath('total', 100000)
            ->assertJsonPath('balance_due', 100000)
            ->assertJsonPath('amount_paid', 0);
    }

    public function test_it_issues_a_draft_invoice(): void
    {
        $invoice = $this->makeInvoice(['status' => 'draft']);

        $response = $this->headers()->postJson("/api/hotel/invoices/{$invoice->id}/issue");

        $response->assertOk()->assertJsonPath('status', 'issued');

        $this->assertDatabaseHas('invoices', [
            'id'     => $invoice->id,
            'status' => 'issued',
        ], 'tenant');

        $this->assertNotNull($response->json('issued_at'));
    }

    public function test_issuing_an_already_issued_invoice_does_not_change_issued_at(): void
    {
        $invoice = $this->makeInvoice(['status' => 'issued', 'issued_at' => now()->subDays(3)]);
        $originalIssuedAt = $invoice->issued_at->toDateTimeString();

        $response = $this->headers()->postJson("/api/hotel/invoices/{$invoice->id}/issue");

        $response->assertOk()
            ->assertJsonPath('status', 'issued')
            ->assertJsonPath('issued_at', $originalIssuedAt);
    }

    public function test_it_stores_a_direct_payment_on_an_invoice(): void
    {
        $invoice = $this->makeInvoice(['status' => 'issued', 'total' => 100000]);

        $response = $this->headers()->postJson("/api/hotel/invoices/{$invoice->id}/payments", [
            'amount'         => 40000,
            'payment_method' => 'card',
            'reference'      => 'INV-PAY-1',
        ]);

        $response->assertCreated()
            ->assertJsonPath('amount', 40000)
            ->assertJsonPath('method', 'card');

        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'amount'     => 40000,
            'method'     => 'card',
        ], 'tenant');
    }

    public function test_direct_invoice_payment_reduces_balance_due(): void
    {
        $invoice = $this->makeInvoice(['status' => 'issued', 'total' => 100000]);

        $this->headers()->postJson("/api/hotel/invoices/{$invoice->id}/payments", [
            'amount'         => 100000,
            'payment_method' => 'cash',
        ]);

        $response = $this->headers()->getJson("/api/hotel/invoices/{$invoice->id}");

        $response->assertOk()
            ->assertJsonPath('amount_paid', 100000)
            ->assertJsonPath('balance_due', 0);
    }

    public function test_it_rejects_payment_with_invalid_method(): void
    {
        $invoice = $this->makeInvoice();

        $response = $this->headers()->postJson("/api/hotel/invoices/{$invoice->id}/payments", [
            'amount'         => 10000,
            'payment_method' => 'crypto',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['payment_method']);
    }
}
