<?php

namespace Database\Factories;

use App\Models\Floor;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $floor = Floor::factory()->create();
        $capacity = fake()->randomElement([1, 2, 3, 4]);

        return [
            'hotel_id' => $floor->hotel_id,
            'floor_id' => $floor->id,
            'room_number' => $floor->floor_number.fake()->unique()->numberBetween(1, 999),
            'room_type' => match ($capacity) {
                1 => 'single',
                2 => 'double',
                3 => 'triple',
                4 => 'quad',
            },
            'capacity' => $capacity,
            'occupied_count' => 0,
            'available_count' => $capacity,
            'is_private' => $capacity <= 2,
            'lift_access' => $floor->lift_access,
            'staircase_access' => $floor->staircase_access,
            'women_only' => $floor->women_only,
            'elderly_friendly' => $floor->elderly_friendly,
            'room_status' => 'available',
        ];
    }

    public function dormitory(int $capacity = 8): static
    {
        return $this->state(fn (array $attributes) => [
            'room_type' => 'dormitory',
            'capacity' => $capacity,
            'occupied_count' => 0,
            'available_count' => $capacity,
            'is_private' => false,
        ]);
    }

    public function withCapacity(int $capacity): static
    {
        return $this->state(fn (array $attributes) => [
            'room_type' => match (true) {
                $capacity <= 1 => 'single',
                $capacity === 2 => 'double',
                $capacity === 3 => 'triple',
                $capacity === 4 => 'quad',
                default => 'dormitory',
            },
            'capacity' => $capacity,
            'occupied_count' => 0,
            'available_count' => $capacity,
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => ['is_private' => true]);
    }

    public function shared(): static
    {
        return $this->state(fn (array $attributes) => ['is_private' => false]);
    }

    public function full(): static
    {
        return $this->state(fn (array $attributes) => [
            'occupied_count' => $attributes['capacity'],
            'available_count' => 0,
            'room_status' => 'full',
        ]);
    }
}
