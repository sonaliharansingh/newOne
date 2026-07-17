<?php

namespace Database\Factories;

use App\Models\RelationshipRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RelationshipRule>
 */
class RelationshipRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'relation_type' => fake()->unique()->randomElement([
                'Self', 'Spouse', 'Child', 'Parent', 'Sibling', 'Grandparent', 'Grandchild',
                'Uncle', 'Aunt', 'Cousin', 'Nephew', 'Niece', 'In-law', 'Friend',
            ]),
            'score' => fake()->numberBetween(10, 100),
            'must_stay_together' => false,
            'guardian_allowed' => false,
            'nearby_room_priority' => fake()->numberBetween(0, 100),
            'same_room_priority' => fake()->numberBetween(0, 100),
            'active' => true,
        ];
    }
}
