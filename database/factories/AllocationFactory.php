<?php

namespace Database\Factories;

use App\Models\Allocation;
use App\Models\Group;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Allocation>
 */
class AllocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'cluster_id' => null,
            'user_id' => User::factory(),
            'room_id' => Room::factory(),
            'allocated_by' => null,
            'allocation_type' => 'auto',
            'allocation_score' => fake()->numberBetween(0, 100),
            'priority_level' => fake()->numberBetween(1, 5),
            'allocation_status' => 'pending',
            'remarks' => null,
        ];
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'allocation_type' => 'manual',
            'allocated_by' => User::factory(),
        ]);
    }

    public function allocated(): static
    {
        return $this->state(fn (array $attributes) => ['allocation_status' => 'allocated']);
    }
}
