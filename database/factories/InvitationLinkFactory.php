<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\InvitationLink;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InvitationLink>
 */
class InvitationLinkFactory extends Factory
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
            'invite_code' => Str::upper(Str::random(10)),
            'max_joins' => fake()->numberBetween(1, 6),
            'joined_count' => 0,
            'expires_at' => now()->addDays(7),
            'active' => true,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => ['expires_at' => now()->subDay(), 'active' => false]);
    }
}
