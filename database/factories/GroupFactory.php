<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Group>
 */
class GroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_name' => fake()->lastName().' Family Group',
            'created_by' => User::factory(),
            'expected_members' => fake()->numberBetween(1, 6),
            'joined_members' => 0,
            'invite_code' => Str::upper(Str::random(8)),
            'invite_expiry' => now()->addDays(7),
            'status' => 'open',
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'closed']);
    }

    public function allocated(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'allocated']);
    }
}
