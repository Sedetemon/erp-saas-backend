<?php

namespace Tests\Feature\Modules\Hotel;

use App\Models\User;
use App\Modules\Hotel\Models\Guest;
use App\Modules\Hotel\Models\Reservation;
use App\Modules\Hotel\Models\ReservationRoom;
use App\Modules\Hotel\Models\Room;
use App\Modules\Hotel\Models\RoomType;
use Laravel\Sanctum\Sanctum;
use Tests\TenantTestCase;
use Illuminate\Support\Facades\DB;

class ReservationApiTest extends TenantTestCase
{
    protected User $user;
    protected Guest $guest;
    protected RoomType $roomType;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        // Le tenant est déjà initialisé et migré grâce à TenantTestCase
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->guest = Guest::factory()->create();
        $this->roomType = RoomType::factory()->create();
        $this->room = Room::factory()->create([
            'room_type_id' => $this->roomType->id,
        ]);
    }

public function test_it_passes_validation_with_valid_reservation_payload(): void
{
    $payload = [
        'guest_id'       => $this->guest->id,
        'check_in_date'  => '2026-09-10',
        'check_out_date' => '2026-09-15',
        'rooms'          => [
            [
                'room_type_id'   => $this->roomType->id,
                'room_id'        => $this->room->id,
                'rate_per_night' => 25000,
            ],
        ],
    ];

    $response = $this
        ->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson('/api/hotel/reservations', $payload);


    $response->assertCreated();

    $this->assertDatabaseHas('reservations', [
        'guest_id'       => $this->guest->id,
        'check_in_date'  => '2026-09-10',
        'check_out_date' => '2026-09-15',
    ], 'tenant');
}

    public function test_it_fails_validation_when_check_out_date_is_before_check_in_date(): void
    {
        $payload = [
            'guest_id'       => $this->guest->id,
            'check_in_date'  => '2026-09-15',
            'check_out_date' => '2026-09-10',
            'rooms'          => [
                [
                    'room_type_id'   => $this->roomType->id,
                    'rate_per_night' => 20000,
                ],
            ],
        ];

        $response = $this
            ->withHeader('X-Tenant', $this->tenant->slug)
            ->postJson('/api/hotel/reservations', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['check_out_date']);
    }

    public function test_it_fails_validation_when_selected_room_is_already_booked(): void
    {
        $existingReservation = Reservation::factory()->create([
            'guest_id'       => $this->guest->id,
            'check_in_date'  => '2026-09-10',
            'check_out_date' => '2026-09-15',
            'status'         => 'confirmed',
        ]);

        ReservationRoom::factory()->create([
            'reservation_id' => $existingReservation->id,
            'room_type_id'   => $this->roomType->id,
            'room_id'        => $this->room->id,
            'rate_per_night' => 25000,
        ]);

        $payload = [
            'guest_id'       => Guest::factory()->create()->id,
            'check_in_date'  => '2026-09-12',
            'check_out_date' => '2026-09-18',
            'rooms'          => [
                [
                    'room_type_id'   => $this->roomType->id,
                    'room_id'        => $this->room->id,
                    'rate_per_night' => 25000,
                ],
            ],
        ];

        $response = $this
         ->withHeader('X-Tenant', $this->tenant->slug)
         ->postJson('/api/hotel/reservations', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['rooms.0.room_id']);
    }

    public function test_it_allows_booking_if_existing_reservation_is_cancelled(): void
{
    // 1. Réservation annulée liée à $this->guest
    $cancelledReservation = Reservation::factory()->create([
        'guest_id'       => $this->guest->id,
        'check_in_date'  => '2026-09-10',
        'check_out_date' => '2026-09-15',
        'status'         => 'cancelled',
    ]);

    ReservationRoom::factory()->create([
        'reservation_id' => $cancelledReservation->id,
        'room_type_id'   => $this->roomType->id,
        'room_id'        => $this->room->id,
        'rate_per_night' => 25000,
    ]);

    // 2. Nouveau client distinct pour la nouvelle réservation
    $newGuest = Guest::factory()->create();

    $payload = [
        'guest_id'       => $newGuest->id,
        'check_in_date'  => '2026-09-10',
        'check_out_date' => '2026-09-15',
        'rooms'          => [
            [
                'room_type_id'   => $this->roomType->id,
                'room_id'        => $this->room->id,
                'rate_per_night' => 25000,
            ],
        ],
    ];

    $response = $this
        ->withHeader('X-Tenant', $this->tenant->slug)
        ->postJson('/api/hotel/reservations', $payload);

    $response->assertCreated();

    // 3. Assertion précise sur $newGuest->id
    $this->assertDatabaseHas('reservations', [
        'guest_id'       => $newGuest->id,
        'check_in_date'  => '2026-09-10',
        'check_out_date' => '2026-09-15',
        'total'          => 125000,
        'balance'        => 125000,
    ], 'tenant');
}
}
