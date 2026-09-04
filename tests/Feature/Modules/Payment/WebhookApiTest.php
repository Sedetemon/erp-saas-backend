<?php

namespace Tests\Feature\Modules\Payment;

use App\Modules\Payment\Events\PaymentFailed;
use App\Modules\Payment\Events\PaymentSucceeded;
use App\Modules\Payment\Models\Transaction;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TenantTestCase;

class WebhookApiTest extends TenantTestCase
{
    protected function createPendingTransaction(string $provider, string $reference): Transaction
    {
        return Transaction::on('tenant')->create([
            'tenant_id'          => $this->tenant->id,
            'entity_type'        => 'reservation',
            'entity_id'          => (string) Str::uuid(),
            'provider'           => $provider,
            'provider_reference' => $reference,
            'amount'             => 50000,
            'status'             => 'pending',
        ]);
    }

    public function test_webhook_marks_transaction_as_succeeded(): void
    {
        Event::fake([PaymentSucceeded::class, PaymentFailed::class]);

        $transaction = $this->createPendingTransaction('orange_money', 'MM-REF-001');

        // Le contrôleur détecte que le tenant est déjà initialisé (par
        // TenantTestCase::setUp()) et ne referme pas ce contexte : la
        // transaction ouverte par le test reste visible tout du long.
        $response = $this->postJson(
            "/api/webhook/orange_money/{$this->tenant->slug}",
            [
                'reference' => 'MM-REF-001',
                'status'    => 'succeeded',
            ]
        );

        $response->assertOk()->assertJsonPath('status', 'ok');

        $this->assertDatabaseHas('payment_transactions', [
            'id'     => $transaction->id,
            'status' => 'succeeded',
        ], 'tenant');

        Event::assertDispatched(PaymentSucceeded::class);
        Event::assertNotDispatched(PaymentFailed::class);
    }

    public function test_webhook_marks_transaction_as_failed(): void
    {
        Event::fake([PaymentSucceeded::class, PaymentFailed::class]);

        $transaction = $this->createPendingTransaction('card', 'CARD-REF-002');

        $response = $this->postJson(
            "/api/webhook/card/{$this->tenant->slug}",
            [
                'reference' => 'CARD-REF-002',
                'status'    => 'failed',
                'reason'    => 'insufficient_funds',
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas('payment_transactions', [
            'id'     => $transaction->id,
            'status' => 'failed',
        ], 'tenant');

        Event::assertDispatched(PaymentFailed::class);
    }

    public function test_webhook_with_unknown_tenant_slug_is_ignored_gracefully(): void
    {
        $response = $this->postJson(
            '/api/webhook/orange_money/tenant-qui-nexiste-pas',
            [
                'reference' => 'MM-REF-999',
                'status'    => 'succeeded',
            ]
        );

        $response->assertOk()->assertJsonPath('status', 'ignored');
    }

    public function test_webhook_logs_every_call_regardless_of_tenant_validity(): void
    {
        $this->postJson(
            '/api/webhook/orange_money/tenant-qui-nexiste-pas',
            ['reference' => 'MM-REF-XYZ', 'status' => 'succeeded']
        );

        $this->assertDatabaseHas('webhook_logs', [
            'provider' => 'orange_money',
        ], 'landlord');
    }

    public function test_webhook_does_not_require_authentication(): void
    {
        // Aucun Sanctum::actingAs supplémentaire, aucun header X-Tenant :
        // représentatif d'un appel réel venant d'un serveur de paiement externe.
        $response = $this->postJson(
            "/api/webhook/manual/{$this->tenant->slug}",
            ['reference' => 'inconnu', 'status' => 'succeeded']
        );

        $response->assertOk();
    }
}
