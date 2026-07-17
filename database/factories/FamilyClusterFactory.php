<?php

namespace Database\Factories;

use App\Models\FamilyCluster;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FamilyCluster>
 */
class FamilyClusterFactory extends Factory
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
            'cluster_name' => fake()->lastName().' Cluster',
            'cluster_size' => fake()->numberBetween(1, 6),
            'cluster_score' => fake()->numberBetween(0, 100),
            'allocation_status' => 'pending',
        ];
    }

    public function allocated(): static
    {
        return $this->state(fn (array $attributes) => ['allocation_status' => 'allocated']);
    }
}
