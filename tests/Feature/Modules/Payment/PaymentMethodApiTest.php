<?php

namespace Tests\Feature\Modules\Payment;

use App\Models\User;
use App\Modules\Payment\Models\PaymentMethod;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class PaymentMethodApiTest extends TenantTestCase
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

    public function test_it_creates_a_payment_method(): void
    {
        $response = $this->headers()->postJson('/api/payment-methods', [
            'provider'   => 'orange_money',
            'last_four'  => '1234',
            'brand'      => 'orange',
            'is_default' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.provider', 'orange_money')
            ->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('payment_methods', [
            'user_id'  => $this->user->id,
            'provider' => 'orange_money',
        ], 'tenant');
    }

    public function test_creating_a_second_default_method_unsets_the_previous_default(): void
    {
        $first = PaymentMethod::create([
            'tenant_id'  => $this->tenant->id,
            'user_id'    => $this->user->id,
            'provider'   => 'orange_money',
            'is_default' => true,
        ]);

        $this->headers()->postJson('/api/payment-methods', [
            'provider'   => 'card',
            'last_four'  => '4242',
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('payment_methods', [
            'id'         => $first->id,
            'is_default' => false,
        ], 'tenant');
    }

    public function test_it_rejects_invalid_provider(): void
    {
        $response = $this->headers()->postJson('/api/payment-methods', [
            'provider' => 'bitcoin',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['provider']);
    }

    public function test_it_lists_methods_ordered_by_default_first(): void
    {
        PaymentMethod::create([
            'tenant_id'  => $this->tenant->id,
            'user_id'    => $this->user->id,
            'provider'   => 'wave',
            'is_default' => false,
        ]);

        PaymentMethod::create([
            'tenant_id'  => $this->tenant->id,
            'user_id'    => $this->user->id,
            'provider'   => 'card',
            'is_default' => true,
        ]);

        $response = $this->headers()->getJson('/api/payment-methods');

        $response->assertOk();
        $this->assertSame('card', $response->json('data.0.provider'));
    }

    public function test_it_deletes_a_method_and_promotes_another_to_default(): void
    {
        $default = PaymentMethod::create([
            'tenant_id'  => $this->tenant->id,
            'user_id'    => $this->user->id,
            'provider'   => 'orange_money',
            'is_default' => true,
        ]);

        $other = PaymentMethod::create([
            'tenant_id'  => $this->tenant->id,
            'user_id'    => $this->user->id,
            'provider'   => 'card',
            'is_default' => false,
        ]);

        $response = $this->headers()->deleteJson("/api/payment-methods/{$default->id}");

        $response->assertStatus(204);

        $this->assertDatabaseHas('payment_methods', [
            'id'         => $other->id,
            'is_default' => true,
        ], 'tenant');
    }

    public function test_it_sets_a_method_as_default(): void
    {
        $methodA = PaymentMethod::create([
            'tenant_id'  => $this->tenant->id,
            'user_id'    => $this->user->id,
            'provider'   => 'orange_money',
            'is_default' => true,
        ]);

        $methodB = PaymentMethod::create([
            'tenant_id'  => $this->tenant->id,
            'user_id'    => $this->user->id,
            'provider'   => 'card',
            'is_default' => false,
        ]);

        $response = $this->headers()->postJson("/api/payment-methods/{$methodB->id}/default");

        $response->assertOk()->assertJsonPath('data.is_default', true);

        $this->assertDatabaseHas('payment_methods', ['id' => $methodA->id, 'is_default' => false], 'tenant');
        $this->assertDatabaseHas('payment_methods', ['id' => $methodB->id, 'is_default' => true], 'tenant');
    }

    public function test_a_user_cannot_delete_another_users_payment_method(): void
    {
        $otherUser = User::factory()->create();

        $method = PaymentMethod::create([
            'tenant_id'  => $this->tenant->id,
            'user_id'    => $otherUser->id,
            'provider'   => 'card',
            'is_default' => true,
        ]);

        $response = $this->headers()->deleteJson("/api/payment-methods/{$method->id}");

        $response->assertStatus(404);
    }
}
