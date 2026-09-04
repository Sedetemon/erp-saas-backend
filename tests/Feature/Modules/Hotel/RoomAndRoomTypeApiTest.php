<?php

namespace Tests\Feature\Modules\Hotel;

use App\Models\User;
use App\Modules\Hotel\Models\Room;
use App\Modules\Hotel\Models\RoomType;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;

class RoomAndRoomTypeApiTest extends TenantTestCase
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

    // ---------------------------------------------------------------
    // RoomType
    // ---------------------------------------------------------------

    public function test_it_creates_a_room_type(): void
    {
        $payload = [
            'name'               => 'Chambre Deluxe',
            'code'               => 'DLX',
            'base_price'         => 45000,
            'capacity_adults'    => 2,
            'capacity_children'  => 1,
            'amenities'          => ['wifi', 'air_conditioning'],
        ];

        $response = $this->headers()->postJson('/api/hotel/room-types', $payload);

        $response->assertCreated()
            ->assertJsonPath('code', 'DLX')
            ->assertJsonPath('base_price', 45000);

        $this->assertDatabaseHas('room_types', [
            'code' => 'DLX',
        ], 'tenant');
    }

    public function test_it_rejects_duplicate_room_type_code(): void
    {
        RoomType::factory()->create(['code' => 'STD']);

        $response = $this->headers()->postJson('/api/hotel/room-types', [
            'name'              => 'Chambre Standard 2',
            'code'              => 'STD',
            'base_price'        => 20000,
            'capacity_adults'   => 2,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['code']);
    }

    public function test_it_lists_room_types_with_rooms_count(): void
    {
        $roomType = RoomType::factory()->create();
        Room::factory()->count(3)->create(['room_type_id' => $roomType->id]);

        $response = $this->headers()->getJson('/api/hotel/room-types');

        $response->assertOk();

        $item = collect($response->json('data'))->firstWhere('id', $roomType->id);

        $this->assertSame(3, $item['rooms_count']);
    }

    public function test_it_updates_a_room_type(): void
    {
        $roomType = RoomType::factory()->create(['base_price' => 20000]);

        $response = $this->headers()->putJson("/api/hotel/room-types/{$roomType->id}", [
            'name'            => $roomType->name,
            'code'            => $roomType->code,
            'base_price'      => 30000,
            'capacity_adults' => $roomType->capacity_adults,
        ]);

        $response->assertOk()->assertJsonPath('base_price', 30000);
    }

    public function test_it_deletes_a_room_type(): void
    {
        $roomType = RoomType::factory()->create();

        $response = $this->headers()->deleteJson("/api/hotel/room-types/{$roomType->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('room_types', ['id' => $roomType->id], 'tenant');
    }

    // ---------------------------------------------------------------
    // Room
    // ---------------------------------------------------------------

    public function test_it_creates_a_room(): void
    {
        $roomType = RoomType::factory()->create();

        $response = $this->headers()->postJson('/api/hotel/rooms', [
            'room_type_id' => $roomType->id,
            'number'       => '101',
            'floor'        => '1',
        ]);

        $response->assertCreated()
            ->assertJsonPath('number', '101')
            ->assertJsonPath('room_type.id', $roomType->id);
    }

    public function test_it_rejects_duplicate_room_number(): void
    {
        $roomType = RoomType::factory()->create();
        Room::factory()->create(['room_type_id' => $roomType->id, 'number' => '202']);

        $response = $this->headers()->postJson('/api/hotel/rooms', [
            'room_type_id' => $roomType->id,
            'number'       => '202',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['number']);
    }

    public function test_it_filters_rooms_by_status(): void
    {
        $roomType = RoomType::factory()->create();
        Room::factory()->create(['room_type_id' => $roomType->id, 'status' => 'available']);
        Room::factory()->create(['room_type_id' => $roomType->id, 'status' => 'maintenance']);

        $response = $this->headers()->getJson('/api/hotel/rooms?status=maintenance');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('maintenance', $response->json('data.0.status'));
    }

    public function test_it_updates_room_status(): void
    {
        $roomType = RoomType::factory()->create();
        $room = Room::factory()->create(['room_type_id' => $roomType->id, 'status' => 'available']);

        $response = $this->headers()->putJson("/api/hotel/rooms/{$room->id}", [
            'room_type_id' => $roomType->id,
            'number'       => $room->number,
            'status'       => 'maintenance',
        ]);

        $response->assertOk()->assertJsonPath('status', 'maintenance');
    }

    public function test_it_deletes_a_room(): void
    {
        $roomType = RoomType::factory()->create();
        $room = Room::factory()->create(['room_type_id' => $roomType->id]);

        $response = $this->headers()->deleteJson("/api/hotel/rooms/{$room->id}");

        $response->assertStatus(204);
        $this->assertSoftDeleted('rooms', ['id' => $room->id], 'tenant');
    }

    // ---------------------------------------------------------------
    // Availability (RoomController::availability -> ReservationService::availableRooms)
    // ---------------------------------------------------------------

    public function test_availability_excludes_rooms_with_overlapping_confirmed_reservation(): void
    {
        $roomType = RoomType::factory()->create();
        $freeRoom = Room::factory()->create(['room_type_id' => $roomType->id, 'status' => 'available']);
        $bookedRoom = Room::factory()->create(['room_type_id' => $roomType->id, 'status' => 'available']);

        $guest = \App\Modules\Hotel\Models\Guest::factory()->create();

        app(\App\Modules\Hotel\Services\ReservationService::class)->createReservation(
            guest: $guest,
            checkIn: new \DateTime('2026-10-01'),
            checkOut: new \DateTime('2026-10-05'),
            roomBookings: [
                [
                    'room_type_id'   => $roomType->id,
                    'room_id'        => $bookedRoom->id,
                    'rate_per_night' => 20000,
                ],
            ],
        );

        $response = $this->headers()->getJson(
            "/api/hotel/room-types/{$roomType->id}/availability?check_in=2026-10-02&check_out=2026-10-04"
        );

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($freeRoom->id));
        $this->assertFalse($ids->contains($bookedRoom->id));
    }

    public function test_availability_excludes_rooms_under_maintenance(): void
    {
        $roomType = RoomType::factory()->create();
        Room::factory()->create(['room_type_id' => $roomType->id, 'status' => 'maintenance']);

        $response = $this->headers()->getJson(
            "/api/hotel/room-types/{$roomType->id}/availability?check_in=2026-10-02&check_out=2026-10-04"
        );

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_availability_rejects_check_out_before_check_in(): void
    {
        $roomType = RoomType::factory()->create();

        $response = $this->headers()->getJson(
            "/api/hotel/room-types/{$roomType->id}/availability?check_in=2026-10-05&check_out=2026-10-01"
        );

        $response->assertStatus(422)->assertJsonValidationErrors(['check_out']);
    }
}
