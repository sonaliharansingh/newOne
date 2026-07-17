<?php

namespace Database\Factories;

use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hotel>
 */
class HotelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hotel_name' => fake()->company().' Hotel',
            'address' => fake()->address(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'total_floors' => 5,
            'has_lift' => true,
            'has_staircase' => true,
        ];
    }
}
