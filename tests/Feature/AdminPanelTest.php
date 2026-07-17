<?php

namespace Tests\Feature;

use App\Livewire\Admin\GroupMerge;
use App\Livewire\Admin\GuardianFlagQueue;
use App\Livewire\Admin\RegistrationShow;
use App\Livewire\Admin\RoomInventory;
use App\Models\AdminFlag;
use App\Models\FamilyCluster;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\HotelSeeder;
use Database\Seeders\RelationshipRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RelationshipRuleSeeder::class);
        $this->seed(HotelSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_non_admin_is_forbidden_from_admin_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/registrations')->assertForbidden();
    }

    public function test_admin_can_confirm_and_finalize_a_group(): void
    {
        $admin = $this->admin();
        $leader = User::factory()->male()->withAge(30)->create();
        $group = Group::factory()->create(['created_by' => $leader->id, 'status' => 'closed']);
        GroupMember::factory()->leader()->create(['group_id' => $group->id, 'user_id' => $leader->id]);

        app(\App\Services\GroupSubmissionPipeline::class)->preview($group);

        Livewire::actingAs($admin)
            ->test(RegistrationShow::class, ['groupId' => $group->id])
            ->call('confirmAndAllocate')
            ->assertSet('previewResults.0.status', 'allocated');

        $this->assertEquals('allocated', $group->fresh()->status);
        $this->assertEquals('allocated', $group->allocations()->first()->allocation_status);
    }

    public function test_admin_can_override_a_room_assignment(): void
    {
        $admin = $this->admin();
        $leader = User::factory()->male()->withAge(30)->create();
        $group = Group::factory()->create(['created_by' => $leader->id]);
        GroupMember::factory()->leader()->create(['group_id' => $group->id, 'user_id' => $leader->id]);

        app(\App\Services\GroupSubmissionPipeline::class)->preview($group);

        $allocation = $group->allocations()->first();
        $originalRoomId = $allocation->room_id;
        $newRoom = Room::where('id', '!=', $originalRoomId)->where('available_count', '>', 0)->first();

        Livewire::actingAs($admin)
            ->test(RegistrationShow::class, ['groupId' => $group->id])
            ->call('startOverride', $allocation->id)
            ->set('selectedRoomId', $newRoom->id)
            ->set('overrideReason', 'Family requested quieter floor')
            ->call('saveOverride');

        $allocation->refresh();
        $this->assertEquals($newRoom->id, $allocation->room_id);
        $this->assertEquals('manual', $allocation->allocation_type);
        $this->assertEquals(1, $allocation->logs()->count());
    }

    public function test_admin_can_resolve_a_guardian_flag(): void
    {
        $admin = $this->admin();
        $minorUser = User::factory()->male()->withAge(16)->create();
        $group = Group::factory()->create();
        $cluster = FamilyCluster::factory()->create(['group_id' => $group->id]);
        $member = GroupMember::factory()->create([
            'group_id' => $group->id,
            'user_id' => $minorUser->id,
            'cluster_id' => $cluster->id,
        ]);

        $flag = AdminFlag::create([
            'group_id' => $group->id,
            'cluster_id' => $cluster->id,
            'member_id' => $member->id,
            'flag_type' => 'missing_male_guardian',
            'status' => 'open',
        ]);

        Livewire::actingAs($admin)
            ->test(GuardianFlagQueue::class)
            ->call('startResolve', $flag->id)
            ->set('resolutionNotes', 'Chaperone confirmed by phone.')
            ->call('resolve');

        $this->assertEquals('resolved', $flag->fresh()->status);
        $this->assertEquals($admin->id, $flag->fresh()->resolved_by);
    }

    public function test_admin_can_add_a_room_through_inventory_screen(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(RoomInventory::class)
            ->set('roomNumber', '999')
            ->set('roomType', 'double')
            ->set('capacity', 2)
            ->call('saveRoom')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('rooms', ['room_number' => '999', 'capacity' => 2]);
    }

    public function test_admin_can_merge_two_groups(): void
    {
        $admin = $this->admin();
        $leader1 = User::factory()->create();
        $leader2 = User::factory()->create();

        $groupA = Group::factory()->create(['created_by' => $leader1->id]);
        $groupB = Group::factory()->create(['created_by' => $leader2->id]);

        GroupMember::factory()->leader()->create(['group_id' => $groupA->id, 'user_id' => $leader1->id]);
        GroupMember::factory()->leader()->create(['group_id' => $groupB->id, 'user_id' => $leader2->id]);

        Livewire::actingAs($admin)
            ->test(GroupMerge::class)
            ->set('sourceGroupId', $groupB->id)
            ->set('targetGroupId', $groupA->id)
            ->call('merge');

        $this->assertDatabaseMissing('groups', ['id' => $groupB->id]);
        $this->assertEquals(2, $groupA->members()->count());
    }
}
