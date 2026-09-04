<?php

namespace Database\Factories\Module\Hotel;

use App\Modules\Hotel\Models\Guest;
use App\Modules\Hotel\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        $checkIn = fake()->dateTimeBetween('+1 day', '+30 days');
        $nights = fake()->numberBetween(1, 7);

        return [
            'reservation_number' => 'RES-' . now()->format('Y') . '-' . fake()->unique()->numerify('#####'),
            'guest_id' => Guest::factory(),
            'check_in_date' => $checkIn->format('Y-m-d'),
            'check_out_date' => $checkIn
                ->modify("+{$nights} days")
                ->format('Y-m-d'),
            'adults' => fake()->numberBetween(1, 4),
            'children' => fake()->numberBetween(0, 2),
            'status' => 'pending',
            'source' => 'direct',
            'notes' => null,
            'created_by' => null,
        ];
    }
}
