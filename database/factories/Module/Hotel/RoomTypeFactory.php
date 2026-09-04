<?php

namespace Database\Factories\Module\Hotel;

use App\Modules\Hotel\Models\RoomType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomType>
 */
class RoomTypeFactory extends Factory
{
    protected $model = RoomType::class;

    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->lexify('???'));

        return [
            'name' => 'Chambre ' . fake()->randomElement([
                'Standard',
                'Deluxe',
                'Suite',
                'Familiale',
            ]),
            'code' => $code,
            'description' => fake()->optional()->sentence(),
            'base_price' => fake()->numberBetween(15000, 150000),
            'capacity_adults' => fake()->numberBetween(1, 4),
            'capacity_children' => fake()->numberBetween(0, 3),
            'amenities' => [
                'wifi',
                'air_conditioning',
            ],
            'is_active' => true,
        ];
    }
}
