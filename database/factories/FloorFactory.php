<?php

namespace Database\Factories;

use App\Models\Floor;
use App\Models\Hotel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Floor>
 */
class FloorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hotel_id' => Hotel::factory(),
            'floor_number' => fake()->unique()->numberBetween(1, 20),
            'lift_access' => true,
            'staircase_access' => true,
            'women_only' => false,
            'elderly_friendly' => false,
        ];
    }

    public function womenOnly(): static
    {
        return $this->state(fn (array $attributes) => ['women_only' => true]);
    }

    public function elderlyFriendly(): static
    {
        return $this->state(fn (array $attributes) => ['elderly_friendly' => true, 'lift_access' => true]);
    }
}
