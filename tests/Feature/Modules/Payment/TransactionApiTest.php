<?php

namespace Tests\Feature\Modules\Payment;

use App\Models\User;
use App\Modules\Payment\Models\Transaction;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class TransactionApiTest extends TenantTestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    protected function headers(): self
    {
        return $this->withHeader('X-Tenant', $this->tenant->slug);
    }

    public function test_it_initiates_a_mobile_money_payment(): void
    {
        $entityId = (string) \Illuminate\Support\Str::uuid();

        $response = $this->headers()->postJson('/api/payments/initiate', [
            'entity_type' => 'reservation',
            'entity_id'   => $entityId, // pas de contrainte FK sur payment_transactions.entity_id
            'provider'    => 'orange_money',
            'amount'      => 50000,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('payment_transactions', [
            'entity_type' => 'reservation',
            'provider'    => 'orange_money',
            'status'      => 'pending',
        ], 'tenant');

        $transaction = Transaction::on('tenant')->where('entity_id', $entityId)->firstOrFail();

        $this->assertStringStartsWith('MM-', $transaction->provider_reference);
    }

    public function test_it_initiates_a_card_payment(): void
    {
        $entityId = (string) \Illuminate\Support\Str::uuid();

        $response = $this->headers()->postJson('/api/payments/initiate', [
            'entity_type' => 'invoice',
            'entity_id'   => $entityId,
            'provider'    => 'card',
            'amount'      => 75000,
        ]);

        $response->assertCreated();

        $transaction = Transaction::on('tenant')->where('entity_id', $entityId)->firstOrFail();

        $this->assertStringStartsWith('CARD-', $transaction->provider_reference);
    }

    public function test_it_initiates_a_manual_payment(): void
    {
        $entityId = (string) \Illuminate\Support\Str::uuid();

        $response = $this->headers()->postJson('/api/payments/initiate', [
            'entity_type' => 'order',
            'entity_id'   => $entityId,
            'provider'    => 'manual',
            'amount'      => 10000,
        ]);

        $response->assertCreated();

        $transaction = Transaction::on('tenant')->where('entity_id', $entityId)->firstOrFail();

        $this->assertStringStartsWith('manual-', $transaction->provider_reference);
    }

    public function test_it_rejects_invalid_entity_type(): void
    {
        $response = $this->headers()->postJson('/api/payments/initiate', [
            'entity_type' => 'unknown_thing',
            'entity_id'   => (string) \Illuminate\Support\Str::uuid(),
            'provider'    => 'card',
            'amount'      => 10000,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['entity_type']);
    }

    public function test_it_rejects_invalid_provider(): void
    {
        $response = $this->headers()->postJson('/api/payments/initiate', [
            'entity_type' => 'order',
            'entity_id'   => (string) \Illuminate\Support\Str::uuid(),
            'provider'    => 'paypal',
            'amount'      => 10000,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['provider']);
    }

    public function test_it_rejects_negative_or_zero_amount(): void
    {
        $response = $this->headers()->postJson('/api/payments/initiate', [
            'entity_type' => 'order',
            'entity_id'   => (string) \Illuminate\Support\Str::uuid(),
            'provider'    => 'manual',
            'amount'      => 0,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['amount']);
    }

    public function test_it_lists_transactions_paginated(): void
    {
        Transaction::on('tenant')->create([
            'tenant_id'   => $this->tenant->id,
            'entity_type' => 'order',
            'entity_id'   => (string) \Illuminate\Support\Str::uuid(),
            'provider'    => 'manual',
            'amount'      => 10000,
            'status'      => 'pending',
        ]);

        Transaction::on('tenant')->create([
            'tenant_id'   => $this->tenant->id,
            'entity_type' => 'order',
            'entity_id'   => (string) \Illuminate\Support\Str::uuid(),
            'provider'    => 'manual',
            'amount'      => 20000,
            'status'      => 'succeeded',
        ]);

        $response = $this->headers()->getJson('/api/payments/transactions');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_it_shows_a_single_transaction(): void
    {
        $transaction = Transaction::on('tenant')->create([
            'tenant_id'   => $this->tenant->id,
            'entity_type' => 'order',
            'entity_id'   => (string) \Illuminate\Support\Str::uuid(),
            'provider'    => 'manual',
            'amount'      => 10000,
            'status'      => 'pending',
        ]);

        $response = $this->headers()->getJson("/api/payments/transactions/{$transaction->id}");

        $response->assertOk()->assertJsonPath('data.id', $transaction->id);
    }

    public function test_it_returns_404_for_unknown_transaction(): void
    {
        $response = $this->headers()->getJson('/api/payments/transactions/' . \Illuminate\Support\Str::uuid());

        $response->assertStatus(404);
    }
}
