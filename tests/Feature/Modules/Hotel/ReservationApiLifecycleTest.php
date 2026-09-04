<?php

namespace Tests\Feature\Modules\Hotel;

use App\Models\User;
use App\Modules\Hotel\Models\Guest;
use App\Modules\Hotel\Models\Room;
use App\Modules\Hotel\Models\RoomType;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class ReservationApiLifecycleTest extends TenantTestCase
{
    protected User $user;
    protected Guest $guest;
    protected RoomType $roomType;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->guest = Guest::factory()->create();
        $this->roomType = RoomType::factory()->create();
        $this->room = Room::factory()->create([
            'room_type_id' => $this->roomType->id,
            'status'       => 'available',
        ]);
    }

    /**
     * Crée une réservation via l'API réelle et retourne son id.
     */
    protected function createReservationViaApi(float $ratePerNight = 25000, ?Room $room = null): string
    {
        $room ??= $this->room;

        $payload = [
            'guest_id'       => $this->guest->id,
            'check_in_date'  => '2026-09-10',
            'check_out_date' => '2026-09-15', // 5 nuits
            'rooms'          => [
                [
                    'room_type_id'   => $this->roomType->id,
                    'room_id'        => $room->id,
                    'rate_per_night' => $ratePerNight,
                ],
            ],
        ];

        $response = $this
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/hotel/reservations', $payload);

        $response->assertCreated();

        return $response->json('id');
    }

    public function test_index_returns_paginated_reservations(): void
    {
        $secondRoom = Room::factory()->create([
            'room_type_id' => $this->roomType->id,
            'status'       => 'available',
        ]);

        $this->createReservationViaApi();
        $this->createReservationViaApi(25000, $secondRoom);

        $response = $this
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson('/api/hotel/reservations');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_show_returns_single_reservation_with_relations(): void
    {
        $reservationId = $this->createReservationViaApi();

        $response = $this
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson("/api/hotel/reservations/{$reservationId}");

        $response->assertOk()
            ->assertJsonPath('id', $reservationId)
            ->assertJsonPath('guest.id', $this->guest->id)
            ->assertJsonPath('total', 125000);
    }

    public function test_check_in_endpoint_updates_status_and_room(): void
    {
        $reservationId = $this->createReservationViaApi();

        $response = $this
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson("/api/hotel/reservations/{$reservationId}/check-in");

        $response->assertOk()
            ->assertJsonPath('status', 'checked_in');

        $this->assertDatabaseHas('rooms', [
            'id'     => $this->room->id,
            'status' => 'occupied',
        ], 'tenant');
    }

    public function test_check_out_endpoint_without_payment_leaves_balance_due(): void
    {
        $reservationId = $this->createReservationViaApi(25000); // total = 125000

        $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson("/api/hotel/reservations/{$reservationId}/check-in");

        $response = $this
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson("/api/hotel/reservations/{$reservationId}/check-out");

        $response->assertOk()
            ->assertJsonPath('status', 'checked_out')
            ->assertJsonPath('balance', 125000);

        $this->assertDatabaseHas('rooms', [
            'id'     => $this->room->id,
            'status' => 'cleaning',
        ], 'tenant');
    }

    public function test_check_out_endpoint_with_payment_method_settles_balance(): void
    {
        $reservationId = $this->createReservationViaApi(25000); // total = 125000

        $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson("/api/hotel/reservations/{$reservationId}/check-in");

        $response = $this
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson("/api/hotel/reservations/{$reservationId}/check-out", [
                'payment_method' => 'cash',
            ]);

        $response->assertOk()
            ->assertJsonPath('status', 'checked_out')
            ->assertJsonPath('balance', 0);

        $this->assertDatabaseHas('payments', [
            'reservation_id' => $reservationId,
            'amount'         => 125000,
            'method'         => 'cash',
        ], 'tenant');
    }

    public function test_check_out_endpoint_rejects_invalid_payment_method(): void
    {
        $reservationId = $this->createReservationViaApi();

        $response = $this
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson("/api/hotel/reservations/{$reservationId}/check-out", [
                'payment_method' => 'bitcoin',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    public function test_cancel_endpoint_releases_room(): void
    {
        $reservationId = $this->createReservationViaApi();

        $response = $this
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson("/api/hotel/reservations/{$reservationId}/cancel");

        $response->assertOk()
            ->assertJsonPath('status', 'cancelled');

        $this->assertDatabaseHas('rooms', [
            'id'     => $this->room->id,
            'status' => 'available',
        ], 'tenant');
    }

    public function test_add_payment_endpoint_reduces_balance(): void
    {
        $reservationId = $this->createReservationViaApi(25000); // total = 125000

        $response = $this
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson("/api/hotel/reservations/{$reservationId}/payments", [
                'amount'         => 50000,
                'payment_method' => 'mobile_money',
                'reference'      => 'REF-API-001',
            ]);

        $response->assertCreated()
            ->assertJsonPath('reservation.balance', 75000)
            ->assertJsonPath('payment.amount', 50000)
            ->assertJsonPath('payment.method', 'mobile_money');

        $this->assertDatabaseHas('payments', [
            'reservation_id' => $reservationId,
            'amount'         => 50000,
            'reference'      => 'REF-API-001',
        ], 'tenant');
    }

    public function test_add_payment_endpoint_rejects_missing_amount(): void
    {
        $reservationId = $this->createReservationViaApi();

        $response = $this
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson("/api/hotel/reservations/{$reservationId}/payments", [
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_ledger_endpoint_lists_charge_and_payment_entries(): void
    {
        $reservationId = $this->createReservationViaApi(25000); // total = 125000

        $this->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson("/api/hotel/reservations/{$reservationId}/payments", [
                'amount'         => 50000,
                'payment_method' => 'card',
            ]);

        $response = $this
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->getJson("/api/hotel/reservations/{$reservationId}/ledger");

        $response->assertOk();

        $types = collect($response->json('data'))->pluck('type');

        $this->assertTrue($types->contains('charge'));
        $this->assertTrue($types->contains('payment'));
    }
}
