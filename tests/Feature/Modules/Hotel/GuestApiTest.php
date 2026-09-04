<?php

namespace Tests\Feature\Modules\Hotel;

use App\Models\User;
use App\Modules\Hotel\Models\Guest;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class GuestApiTest extends TenantTestCase
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

    public function test_it_creates_a_guest(): void
    {
        $payload = [
            'first_name' => 'Aïcha',
            'last_name'  => 'Koné',
            'email'      => 'aicha.kone@example.com',
            'phone'      => '+225 07 00 00 00 00',
        ];

        $response = $this->headers()->postJson('/api/hotel/guests', $payload);

        $response->assertCreated()
            ->assertJsonPath('first_name', 'Aïcha')
            ->assertJsonPath('full_name', 'Aïcha Koné');

        $this->assertDatabaseHas('guests', [
            'email' => 'aicha.kone@example.com',
        ], 'tenant');
    }

    public function test_it_rejects_guest_without_required_fields(): void
    {
        $response = $this->headers()->postJson('/api/hotel/guests', [
            'email' => 'sans-nom@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name']);
    }

    public function test_it_shows_a_guest(): void
    {
        $guest = Guest::factory()->create();

        $response = $this->headers()->getJson("/api/hotel/guests/{$guest->id}");

        $response->assertOk()->assertJsonPath('id', $guest->id);
    }

    public function test_it_searches_guests_by_name_or_email(): void
    {
        Guest::factory()->create(['first_name' => 'Moussa', 'last_name' => 'Diarra', 'email' => 'moussa@example.com']);
        Guest::factory()->create(['first_name' => 'Fatou', 'last_name' => 'Bamba', 'email' => 'fatou@example.com']);

        $response = $this->headers()->getJson('/api/hotel/guests?search=moussa');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('Moussa', $response->json('data.0.first_name'));
    }

    public function test_it_updates_a_guest(): void
    {
        $guest = Guest::factory()->create(['phone' => null]);

        $response = $this->headers()->putJson("/api/hotel/guests/{$guest->id}", [
            'first_name' => $guest->first_name,
            'last_name'  => $guest->last_name,
            'phone'      => '+225 01 02 03 04 05',
        ]);

        $response->assertOk()->assertJsonPath('phone', '+225 01 02 03 04 05');
    }

    public function test_it_deletes_a_guest(): void
    {
        $guest = Guest::factory()->create();

        $response = $this->headers()->deleteJson("/api/hotel/guests/{$guest->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('guests', ['id' => $guest->id], 'tenant');
    }
}
