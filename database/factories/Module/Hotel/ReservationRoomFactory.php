<?php

namespace Database\Factories\Module\Hotel;

use App\Modules\Hotel\Models\Reservation;
use App\Modules\Hotel\Models\ReservationRoom;
use App\Modules\Hotel\Models\Room;
use App\Modules\Hotel\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReservationRoom>
 */
class ReservationRoomFactory extends Factory
{
    protected $model = ReservationRoom::class;

    public function definition(): array
    {
        $roomType = RoomType::factory()->create();

        $room = Room::factory()->create([
            'room_type_id' => $roomType->id,
        ]);

        $nights = fake()->numberBetween(1, 7);
        $rate = fake()->numberBetween(15000, 150000);

        return [
            'reservation_id' => Reservation::factory(),
            'room_id' => $room->id,
            'room_type_id' => $roomType->id,
            'rate_per_night' => $rate,
            'nights' => $nights,
            'subtotal' => $rate * $nights,
        ];
    }
}
