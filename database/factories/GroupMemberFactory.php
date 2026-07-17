<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GroupMember>
 */
class GroupMemberFactory extends Factory
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
            'user_id' => User::factory(),
            'is_leader' => false,
            'related_user_id' => null,
            'relation_type' => 'Self',
            'relation_score' => 0,
            'guardian_required' => false,
            'allocation_priority' => 0,
            'cluster_id' => null,
            'notes' => null,
        ];
    }

    public function leader(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_leader' => true,
            'relation_type' => 'Self',
        ]);
    }

    public function relatedTo(GroupMember $member, string $relationType): static
    {
        return $this->state(fn (array $attributes) => [
            'group_id' => $member->group_id,
            'related_user_id' => $member->id,
            'relation_type' => $relationType,
        ]);
    }
}
