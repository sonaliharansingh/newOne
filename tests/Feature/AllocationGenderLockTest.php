<?php

namespace Tests\Feature;

use App\Models\Allocation;
use App\Models\FamilyCluster;
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
 * Covers the runtime gender_lock, locality-aware consolidation, family cluster-lock and
 * occupancy-accounting guarantees added on top of the priority engine.
 */
class AllocationGenderLockTest extends TestCase
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
     * Builds a shared (non-private) room with a fresh floor/hotel, with explicit locks cleared.
     */
    private function sharedRoom(int $capacity, array $overrides = []): Room
    {
        return Room::factory()->withCapacity($capacity)->shared()->create(array_merge([
            'women_only' => false,
            'gender_lock' => null,
            'reserved_for_cluster_id' => null,
        ], $overrides));
    }

    /**
     * Registers one solo traveller as their own group and returns the resulting allocation.
     */
    private function registerSolo(array $userAttributes): ?Allocation
    {
        $user = User::factory()->create($userAttributes);
        $group = Group::factory()->create(['created_by' => $user->id]);
        GroupMember::factory()->leader()->create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'relation_type' => 'Self',
        ]);

        $results = $this->pipeline()->preview($group);

        return $results[0]['cluster']->allocations->first();
    }

    public function test_second_same_gender_solo_consolidates_into_the_partial_room_and_it_is_gender_locked(): void
    {
        $room = $this->sharedRoom(2);

        $first = $this->registerSolo(['gender' => 'male', 'city' => 'Patna', 'date_of_birth' => now()->subYears(30)->format('Y-m-d')]);
        $second = $this->registerSolo(['gender' => 'male', 'city' => 'Patna', 'date_of_birth' => now()->subYears(28)->format('Y-m-d')]);

        $this->assertEquals($room->id, $first->room_id);
        $this->assertEquals($room->id, $second->room_id, 'Second male should consolidate into the same partial room.');

        $room->refresh();
        $this->assertEquals('male', $room->gender_lock);
        $this->assertEquals(0, $room->available_count);
        $this->assertEquals('full', $room->room_status);
    }

    public function test_female_is_excluded_from_a_male_locked_room_and_seeds_her_own(): void
    {
        $roomA = $this->sharedRoom(2);
        $roomB = $this->sharedRoom(2);

        $male = $this->registerSolo(['gender' => 'male', 'city' => 'Patna', 'date_of_birth' => now()->subYears(30)->format('Y-m-d')]);
        $female = $this->registerSolo(['gender' => 'female', 'city' => 'Patna', 'date_of_birth' => now()->subYears(30)->format('Y-m-d')]);

        $this->assertNotEquals($male->room_id, $female->room_id, 'A female must never be placed into a male-locked room.');

        $this->assertEquals('male', Room::find($male->room_id)->gender_lock);
        $this->assertEquals('female', Room::find($female->room_id)->gender_lock);
    }

    public function test_other_gender_locks_the_room_to_other_and_excludes_male(): void
    {
        $roomA = $this->sharedRoom(2);
        $roomB = $this->sharedRoom(2);

        $other = $this->registerSolo(['gender' => 'other', 'city' => 'Patna', 'date_of_birth' => now()->subYears(30)->format('Y-m-d')]);
        $male = $this->registerSolo(['gender' => 'male', 'city' => 'Patna', 'date_of_birth' => now()->subYears(30)->format('Y-m-d')]);

        $this->assertEquals('other', Room::find($other->room_id)->gender_lock);
        $this->assertNotEquals($other->room_id, $male->room_id, 'A male must never join an other-locked room.');
    }

    public function test_family_room_is_reserved_and_a_stranger_cannot_take_the_spare_bed(): void
    {
        // Only a single private quad exists: a family of three takes it, leaving one bed that
        // must stay with the family (case 4) rather than going to an unrelated solo traveller.
        $quad = Room::factory()->withCapacity(4)->private()->create([
            'women_only' => false, 'gender_lock' => null, 'reserved_for_cluster_id' => null,
        ]);

        $dad = User::factory()->male()->withAge(40)->create();
        $mom = User::factory()->female()->withAge(38)->create();
        $child = User::factory()->male()->withAge(8)->create();

        $group = Group::factory()->create(['created_by' => $dad->id]);
        $dadMember = GroupMember::factory()->leader()->create(['group_id' => $group->id, 'user_id' => $dad->id]);
        GroupMember::factory()->relatedTo($dadMember, 'Spouse')->create(['user_id' => $mom->id]);
        GroupMember::factory()->relatedTo($dadMember, 'Child')->create(['user_id' => $child->id]);

        $familyResults = $this->pipeline()->preview($group);
        $this->assertEquals('allocated', $familyResults[0]['status']);

        $quad->refresh();
        $this->assertEquals(3, $quad->occupied_count);
        $this->assertEquals($familyResults[0]['cluster']->id, $quad->reserved_for_cluster_id);

        // A stranger now registers; the quad's spare bed is off-limits, so with no other stock
        // they go unallocated rather than being packed in with the family.
        $stranger = $this->registerSolo(['gender' => 'male', 'date_of_birth' => now()->subYears(25)->format('Y-m-d')]);
        $this->assertNull($stranger, 'Stranger should not be allocated into the reserved family room.');

        $quad->refresh();
        $this->assertEquals(3, $quad->occupied_count, 'Reserved family room occupancy must be untouched by the stranger.');
    }

    public function test_solo_prefers_a_partial_room_matching_locality_over_a_different_locality_room(): void
    {
        // Two partial male rooms already exist: one in the newcomer's city, one elsewhere.
        $sameCity = $this->seedOccupiedMaleRoom('Patna', 'Kankarbagh', 'Hindi');
        $otherCity = $this->seedOccupiedMaleRoom('Delhi', 'Saket', 'Punjabi');

        $newcomer = $this->registerSolo([
            'gender' => 'male', 'city' => 'Patna', 'area' => 'Kankarbagh', 'language' => 'Hindi',
            'date_of_birth' => now()->subYears(27)->format('Y-m-d'),
        ]);

        $this->assertEquals($sameCity->id, $newcomer->room_id, 'Newcomer should consolidate with same-locality travellers.');
        $this->assertNotEquals($otherCity->id, $newcomer->room_id);
    }

    public function test_occupancy_accounting_never_oversells_a_room(): void
    {
        // Scarce stock: 3 beds total, 6 solo males registering.
        $this->sharedRoom(2);
        $this->sharedRoom(1);

        for ($i = 0; $i < 6; $i++) {
            $this->registerSolo(['gender' => 'male', 'city' => 'Patna', 'date_of_birth' => now()->subYears(25 + $i)->format('Y-m-d')]);
        }

        foreach (Room::all() as $room) {
            $this->assertLessThanOrEqual($room->capacity, $room->occupied_count, "Room {$room->id} was oversold.");
            $this->assertEquals($room->capacity - $room->occupied_count, $room->available_count);
            $this->assertGreaterThanOrEqual(0, $room->available_count);
        }

        // No two allocations may share a bed: total allocations <= total capacity.
        $this->assertLessThanOrEqual(Room::sum('capacity'), Allocation::count());
    }

    /**
     * Directly seeds a partial, male-locked double already holding one occupant of the given
     * locality, so locality-preference ranking can be exercised without relying on the
     * consolidation loop (which would otherwise pack everyone into the first room).
     */
    private function seedOccupiedMaleRoom(string $city, string $area, string $language): Room
    {
        $room = $this->sharedRoom(2, ['gender_lock' => 'male', 'occupied_count' => 1, 'available_count' => 1, 'room_status' => 'partial']);

        $occupant = User::factory()->create([
            'gender' => 'male', 'city' => $city, 'area' => $area, 'language' => $language,
            'date_of_birth' => now()->subYears(35)->format('Y-m-d'),
        ]);
        $group = Group::factory()->create(['created_by' => $occupant->id]);
        $cluster = FamilyCluster::factory()->create(['group_id' => $group->id]);
        GroupMember::factory()->leader()->create([
            'group_id' => $group->id, 'user_id' => $occupant->id, 'relation_type' => 'Self', 'cluster_id' => $cluster->id,
        ]);
        Allocation::factory()->create([
            'group_id' => $group->id, 'cluster_id' => $cluster->id, 'user_id' => $occupant->id, 'room_id' => $room->id,
        ]);

        return $room;
    }
}
