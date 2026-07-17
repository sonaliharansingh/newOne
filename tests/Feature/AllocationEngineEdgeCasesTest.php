<?php

namespace Tests\Feature;

use App\Models\AdminFlag;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Services\GroupSubmissionPipeline;
use Database\Seeders\HotelSeeder;
use Database\Seeders\RelationshipRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllocationEngineEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RelationshipRuleSeeder::class);
        $this->seed(HotelSeeder::class);
    }

    private function pipeline(): GroupSubmissionPipeline
    {
        return app(GroupSubmissionPipeline::class);
    }

    public function test_couple_is_kept_together_in_one_room_instead_of_being_gender_split(): void
    {
        $husband = User::factory()->male()->withAge(35)->create();
        $wife = User::factory()->female()->withAge(33)->create();

        $group = Group::factory()->create(['created_by' => $husband->id]);
        $husbandMember = GroupMember::factory()->leader()->create(['group_id' => $group->id, 'user_id' => $husband->id]);
        GroupMember::factory()->relatedTo($husbandMember, 'Spouse')->create(['user_id' => $wife->id]);

        $results = $this->pipeline()->preview($group);

        $this->assertCount(1, $results);
        $this->assertEquals('allocated', $results[0]['status']);
        $this->assertEquals(1, $results[0]['cluster']->allocations->pluck('room_id')->unique()->count());
        $this->assertEquals(1, $results[0]['cluster']->allocations->first()->priority_level);
    }

    public function test_family_too_large_for_one_room_falls_back_to_priority_3_gender_split(): void
    {
        $dad = User::factory()->male()->withAge(45)->create();
        $mom = User::factory()->female()->withAge(43)->create();

        $group = Group::factory()->create(['created_by' => $dad->id]);
        $dadMember = GroupMember::factory()->leader()->create(['group_id' => $group->id, 'user_id' => $dad->id]);
        GroupMember::factory()->relatedTo($dadMember, 'Spouse')->create(['user_id' => $mom->id]);

        // 3 children pushes the cluster past any private room's capacity (max 4).
        foreach ([12, 14, 9] as $age) {
            $child = User::factory()->male()->withAge($age)->create();
            GroupMember::factory()->relatedTo($dadMember, 'Child')->create(['user_id' => $child->id]);
        }

        $results = $this->pipeline()->preview($group);

        $this->assertCount(1, $results);
        $this->assertEquals('allocated', $results[0]['status']);
        $cluster = $results[0]['cluster'];
        $this->assertEquals(5, $cluster->cluster_size);

        // Gender split means at least 2 distinct rooms, and every allocation is priority 3.
        $this->assertGreaterThanOrEqual(2, $cluster->allocations->pluck('room_id')->unique()->count());
        $this->assertTrue($cluster->allocations->every(fn ($a) => $a->priority_level === 3));
    }

    public function test_minor_under_15_without_female_guardian_is_blocked(): void
    {
        $friend1 = User::factory()->male()->withAge(28)->create();
        $minor = User::factory()->male()->withAge(10)->create();

        $group = Group::factory()->create(['created_by' => $friend1->id]);
        $leaderMember = GroupMember::factory()->leader()->create(['group_id' => $group->id, 'user_id' => $friend1->id]);
        GroupMember::factory()->relatedTo($leaderMember, 'Nephew')->create(['user_id' => $minor->id]);

        $results = $this->pipeline()->preview($group);

        $this->assertCount(1, $results);
        $this->assertEquals('blocked', $results[0]['status']);
        $this->assertEquals(1, AdminFlag::where('flag_type', 'missing_female_guardian')->where('status', 'open')->count());
        $this->assertEmpty($results[0]['cluster']->allocations);
    }

    public function test_female_15_to_17_is_treated_as_adult_and_never_flagged(): void
    {
        $dad = User::factory()->male()->withAge(45)->create();
        $daughter = User::factory()->female()->withAge(16)->create();

        $group = Group::factory()->create(['created_by' => $dad->id]);
        $dadMember = GroupMember::factory()->leader()->create(['group_id' => $group->id, 'user_id' => $dad->id]);
        GroupMember::factory()->relatedTo($dadMember, 'Child')->create(['user_id' => $daughter->id]);

        $results = $this->pipeline()->preview($group);

        $this->assertEquals('allocated', $results[0]['status']);
        $this->assertEquals(0, AdminFlag::count());
    }

    public function test_multi_family_group_is_processed_as_independent_clusters(): void
    {
        $group = Group::factory()->create();

        $dad1 = User::factory()->male()->withAge(40)->create();
        $mom1 = User::factory()->female()->withAge(38)->create();
        $dad1Member = GroupMember::factory()->create([
            'group_id' => $group->id, 'user_id' => $dad1->id, 'relation_type' => 'Self', 'is_leader' => true,
        ]);
        GroupMember::factory()->relatedTo($dad1Member, 'Spouse')->create(['user_id' => $mom1->id]);

        $dad2 = User::factory()->male()->withAge(50)->create();
        $mom2 = User::factory()->female()->withAge(48)->create();
        $dad2Member = GroupMember::factory()->create([
            'group_id' => $group->id, 'user_id' => $dad2->id, 'relation_type' => 'Self',
        ]);
        GroupMember::factory()->relatedTo($dad2Member, 'Spouse')->create(['user_id' => $mom2->id]);

        $results = $this->pipeline()->preview($group);

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertEquals('allocated', $result['status']);
            $this->assertEquals(2, $result['cluster']->cluster_size);
        }

        // Independent clusters -> independent room assignments, not merged into one.
        $roomIds = collect($results)->flatMap(fn ($r) => $r['cluster']->allocations->pluck('room_id'))->unique();
        $this->assertCount(2, $roomIds);
    }

    public function test_solo_travellers_are_pooled_by_gender_and_never_mixed_across_gender(): void
    {
        $group = Group::factory()->create();

        foreach (['male', 'male', 'female'] as $i => $gender) {
            $solo = User::factory()->create(['gender' => $gender, 'date_of_birth' => now()->subYears(22)->format('Y-m-d')]);
            GroupMember::factory()->create([
                'group_id' => $group->id,
                'user_id' => $solo->id,
                'relation_type' => 'Self',
                'is_leader' => $i === 0,
            ]);
        }

        $results = $this->pipeline()->preview($group);

        $this->assertCount(3, $results);

        foreach ($results as $result) {
            $this->assertEquals('allocated', $result['status']);
            $allocation = $result['cluster']->allocations->first();
            $this->assertEquals(5, $allocation->priority_level);
            $this->assertFalse($allocation->room->women_only && $result['cluster']->members->first()->user->gender === 'male');
        }
    }
}
