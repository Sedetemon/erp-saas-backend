<?php

namespace Database\Factories\Module\Hotel;

use App\Modules\Hotel\Models\Room;
use App\Modules\Hotel\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'room_type_id' => RoomType::factory(),
            'number' => fake()->unique()->numerify('###'),
            'floor' => (string) fake()->numberBetween(0, 10),
            'status' => 'available',
            'notes' => null,
        ];
    }
}
