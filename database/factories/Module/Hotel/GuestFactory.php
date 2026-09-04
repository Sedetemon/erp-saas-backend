<?php

namespace Database\Factories\Module\Hotel;

use App\Modules\Hotel\Models\Guest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guest>
 */
class GuestFactory extends Factory
{
    protected $model = Guest::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->numerify('+225 0# ## ## ## ##'),
            'nationality' => 'Ivoirienne',
            'document_type' => 'CNI',
            'document_number' => fake()->unique()->bothify('CI-########'),
            'address' => fake()->optional()->address(),
            'notes' => null,
        ];
    }
}
