<?php

namespace Tests\Feature;

use App\Models\Allocation;
use App\Models\Floor;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Room;
use App\Models\User;
use App\Services\GroupSubmissionPipeline;
use Database\Seeders\RelationshipRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The owner's worked example: a 4-person group of "me (male) + my wife (female) +
 * my wife's friend (male) + my cousin (male)".
 *
 * Because every member is chained to another through group_members.related_user_id
 * (wife -> me, friend -> wife, cousin -> me), union-find folds all four into ONE
 * cluster. That cluster is mixed-relation (Self/Spouse/Friend/Cousin) and mixed-gender
 * (3 male, 1 female), so it skips Priority 1 (couple) and Priority 2 (nuclear family)
 * and is resolved by Priority 3 (ExtendedFamilyStrategy):
 *
 *   1. it first tries to put all four in a SINGLE room (a quad) reserved to the cluster;
 *   2. if no single room fits four, it splits into two adjacent rooms on the same floor,
 *      keeping the husband+wife couple together in one and the two other males in the other;
 *   3. if it can't even do that, the waterfall falls through to Priority 4 pooling and,
 *      failing everything, the cluster is returned unallocated.
 */
class MixedFriendCousinGroupAllocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RelationshipRuleSeeder::class);
    }

    private function pipeline(): GroupSubmissionPipeline
    {
        return app(GroupSubmissionPipeline::class);
    }

    /**
     * Builds the exact 4-person group and returns [group, [me, wife, friend, cousin]].
     *
     * @return array{0: Group, 1: array<string, User>}
     */
    private function makeGroup(): array
    {
        $me = User::factory()->male()->withAge(32)->create(['city' => 'Patna']);
        $wife = User::factory()->female()->withAge(30)->create(['city' => 'Patna']);
        $friend = User::factory()->male()->withAge(31)->create(['city' => 'Patna']);
        $cousin = User::factory()->male()->withAge(28)->create(['city' => 'Patna']);

        $group = Group::factory()->create(['created_by' => $me->id]);

        // Me: the leader / Self.
        $meMember = GroupMember::factory()->leader()->create([
            'group_id' => $group->id,
            'user_id' => $me->id,
            'relation_type' => 'Self',
        ]);

        // My wife is related to me (Spouse).
        $wifeMember = GroupMember::factory()->relatedTo($meMember, 'Spouse')->create([
            'user_id' => $wife->id,
        ]);

        // My wife's friend is related to my wife (Friend).
        GroupMember::factory()->relatedTo($wifeMember, 'Friend')->create([
            'user_id' => $friend->id,
        ]);

        // My cousin is related to me (Cousin).
        GroupMember::factory()->relatedTo($meMember, 'Cousin')->create([
            'user_id' => $cousin->id,
        ]);

        return [$group, compact('me', 'wife', 'friend', 'cousin')];
    }

    public function test_a_quad_room_holds_all_four_in_a_single_room_reserved_to_the_cluster(): void
    {
        // Plenty of stock, including a quad that fits everyone.
        $quad = Room::factory()->withCapacity(4)->shared()->create([
            'women_only' => false, 'gender_lock' => null, 'reserved_for_cluster_id' => null,
        ]);

        [$group, $users] = $this->makeGroup();

        $results = $this->pipeline()->preview($group);

        // One cluster, fully allocated.
        $this->assertCount(1, $results);
        $this->assertEquals('allocated', $results[0]['status']);

        // Every one of the four is in the ONE quad room.
        $allocations = Allocation::where('group_id', $group->id)->get();
        $this->assertCount(4, $allocations);
        $this->assertEquals([$quad->id], $allocations->pluck('room_id')->unique()->values()->all());

        // The room is now full and reserved to this cluster, so no stranger can take a bed.
        $quad->refresh();
        $this->assertEquals(4, $quad->occupied_count);
        $this->assertEquals(0, $quad->available_count);
        $this->assertEquals('full', $quad->room_status);
        $this->assertEquals($results[0]['cluster']->id, $quad->reserved_for_cluster_id);

        // These are Priority 3 (extended-family single room) placements.
        $this->assertEquals([3], $allocations->pluck('priority_level')->unique()->values()->all());
    }

    public function test_when_no_quad_is_available_the_couple_stays_together_and_the_two_other_males_share_the_adjacent_room(): void
    {
        // No room can hold four. On one shared floor we offer a triple and a double; nothing has
        // 4 free beds. The husband+wife couple must be kept together (they fit the double), and
        // the wife's friend + the cousin share the adjacent triple.
        $floor = Floor::factory()->create(['women_only' => false]);
        $triple = Room::factory()->withCapacity(3)->shared()->create([
            'hotel_id' => $floor->hotel_id, 'floor_id' => $floor->id,
            'women_only' => false, 'gender_lock' => null, 'reserved_for_cluster_id' => null,
        ]);
        $double = Room::factory()->withCapacity(2)->shared()->create([
            'hotel_id' => $floor->hotel_id, 'floor_id' => $floor->id,
            'women_only' => false, 'gender_lock' => null, 'reserved_for_cluster_id' => null,
        ]);

        [$group, $users] = $this->makeGroup();

        $results = $this->pipeline()->preview($group);

        $this->assertEquals('allocated', $results[0]['status']);

        $roomByUser = Allocation::where('group_id', $group->id)
            ->get()
            ->keyBy('user_id')
            ->map(fn (Allocation $a) => $a->room_id);

        // The couple stays together in the double; the friend + cousin share the triple.
        $this->assertEquals(
            $roomByUser[$users['me']->id],
            $roomByUser[$users['wife']->id],
            'The husband and wife must stay in the same room.'
        );
        $this->assertEquals($double->id, $roomByUser[$users['wife']->id], 'The couple should take the double.');

        $this->assertEquals(
            $roomByUser[$users['friend']->id],
            $roomByUser[$users['cousin']->id],
            'The friend and cousin should share the other room.'
        );
        $this->assertEquals($triple->id, $roomByUser[$users['friend']->id]);

        // The couple must not share with the two other males.
        $this->assertNotEquals($roomByUser[$users['wife']->id], $roomByUser[$users['friend']->id]);

        // "Adjacent" is satisfied at floor level.
        $this->assertEquals(
            Room::find($double->id)->floor_id,
            Room::find($triple->id)->floor_id,
            'The split rooms should be on the same floor.'
        );
    }

    public function test_five_person_mixed_cluster_keeps_the_couple_together_across_a_quad_and_a_double(): void
    {
        // The owner's second worked example: me (male) + wife (female, Spouse) +
        // wife's friend (male) + my cousin (female) + the cousin's sibling (female).
        // All five fold into ONE cluster. No single room fits five, so Priority 3 splits into
        // the quad + the double — and the husband+wife couple must land in the same room.
        $quad = Room::factory()->withCapacity(4)->shared()->create([
            'women_only' => false, 'gender_lock' => null, 'reserved_for_cluster_id' => null,
        ]);
        $double = Room::factory()->withCapacity(2)->shared()->create([
            'hotel_id' => $quad->hotel_id, 'floor_id' => $quad->floor_id,
            'women_only' => false, 'gender_lock' => null, 'reserved_for_cluster_id' => null,
        ]);

        $me = User::factory()->male()->withAge(32)->create();
        $wife = User::factory()->female()->withAge(30)->create();
        $friend = User::factory()->male()->withAge(31)->create();
        $cousin = User::factory()->female()->withAge(29)->create();
        $sibling = User::factory()->female()->withAge(27)->create();

        $group = Group::factory()->create(['created_by' => $me->id]);
        $meMember = GroupMember::factory()->leader()->create([
            'group_id' => $group->id, 'user_id' => $me->id, 'relation_type' => 'Self',
        ]);
        $wifeMember = GroupMember::factory()->relatedTo($meMember, 'Spouse')->create(['user_id' => $wife->id]);
        GroupMember::factory()->relatedTo($wifeMember, 'Friend')->create(['user_id' => $friend->id]);
        $cousinMember = GroupMember::factory()->relatedTo($meMember, 'Cousin')->create(['user_id' => $cousin->id]);
        GroupMember::factory()->relatedTo($cousinMember, 'Sibling')->create(['user_id' => $sibling->id]);

        $results = $this->pipeline()->preview($group);

        $this->assertCount(1, $results);
        $this->assertEquals('allocated', $results[0]['status']);

        $roomByUser = Allocation::where('group_id', $group->id)
            ->get()->keyBy('user_id')->map(fn (Allocation $a) => $a->room_id);

        // The couple stays together.
        $this->assertEquals(
            $roomByUser[$me->id],
            $roomByUser[$wife->id],
            'The husband and wife must stay in the same room.'
        );

        // All five are placed, across exactly the two rooms, at priority 3.
        $allocations = Allocation::where('group_id', $group->id)->get();
        $this->assertCount(5, $allocations);
        $this->assertEqualsCanonicalizing([$quad->id, $double->id], $allocations->pluck('room_id')->unique()->all());
        $this->assertEquals([3], $allocations->pluck('priority_level')->unique()->values()->all());
    }

    public function test_when_no_rooms_fit_the_group_is_returned_unallocated(): void
    {
        // Only a single lonely bed exists — far too little for a 4-person cluster.
        Room::factory()->withCapacity(1)->shared()->create([
            'women_only' => false, 'gender_lock' => null, 'reserved_for_cluster_id' => null,
        ]);

        [$group, $users] = $this->makeGroup();

        $results = $this->pipeline()->preview($group);

        $this->assertEquals('unallocated', $results[0]['status']);
        $this->assertEquals('No matching room capacity available.', $results[0]['reason']);
        $this->assertEquals(0, Allocation::where('group_id', $group->id)->count());
    }
}
