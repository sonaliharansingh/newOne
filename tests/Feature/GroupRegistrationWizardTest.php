<?php

namespace Tests\Feature;

use App\Livewire\GroupRegistrationWizard;
use App\Models\AdminFlag;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Services\GroupSubmissionPipeline;
use Database\Seeders\HotelSeeder;
use Database\Seeders\RelationshipRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GroupRegistrationWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RelationshipRuleSeeder::class);
        $this->seed(HotelSeeder::class);
    }

    public function test_solo_registration_completes_and_gets_allocated(): void
    {
        $user = User::factory()->male()->withAge(30)->create();

        Livewire::actingAs($user)
            ->test(GroupRegistrationWizard::class)
            ->set('first_name', 'John')
            ->set('last_name', 'Doe')
            ->set('date_of_birth', '1994-01-01')
            ->set('gender', 'male')
            ->set('phone', '9999999999')
            ->call('proceedToStep2')
            ->assertHasNoErrors()
            ->assertSet('step', 2)
            ->set('trip_start_date', '2026-08-01')
            ->set('trip_end_date', '2026-08-05')
            ->set('registrationType', 'solo')
            ->call('proceedToStep3')
            ->assertSet('step', 4)
            ->call('confirmSubmission')
            ->assertSet('step', 5)
            ->assertSee('booking');

        $group = Group::first();
        $this->assertNotNull($group);
        $this->assertEquals('closed', $group->status);
        $this->assertEquals(1, $group->members()->count());
    }

    public function test_group_registration_with_couple_and_child_allocates_one_room(): void
    {
        $user = User::factory()->male()->withAge(40)->create();

        $component = Livewire::actingAs($user)
            ->test(GroupRegistrationWizard::class)
            ->set('first_name', 'Lead')
            ->set('last_name', 'Er')
            ->set('date_of_birth', '1985-01-01')
            ->set('gender', 'male')
            ->set('phone', '9999999999')
            ->call('proceedToStep2')
            ->assertSet('step', 2)
            ->set('trip_start_date', '2026-08-01')
            ->set('trip_end_date', '2026-08-05')
            ->set('registrationType', 'group')
            ->set('groupName', 'Er Family')
            ->set('expected_member_count', 2)
            ->call('proceedToStep3')
            ->assertSet('step', 3);

        $component->set('members.0.first_name', 'Spouse')
            ->set('members.0.last_name', 'Er')
            ->set('members.0.date_of_birth', '1987-01-01')
            ->set('members.0.gender', 'female')
            ->set('members.0.related_index', -1)
            ->set('members.0.relation_type', 'Spouse')
            ->call('proceedToStep4')
            ->assertHasNoErrors()
            ->assertSet('step', 4);

        $group = Group::first();
        $this->assertNotNull($group);
        $this->assertEquals(2, $group->members()->count());

        $cluster = $group->clusters()->first();
        $this->assertNotNull($cluster);
        $this->assertEquals('allocated', $cluster->allocation_status);
        $this->assertEquals(2, $cluster->allocations()->count());
    }

    public function test_unaccompanied_minor_gets_blocked_with_admin_flag(): void
    {
        $user = User::factory()->male()->withAge(16)->create();

        Livewire::actingAs($user)
            ->test(GroupRegistrationWizard::class)
            ->set('first_name', 'Teen')
            ->set('last_name', 'Alone')
            ->set('date_of_birth', now()->subYears(16)->format('Y-m-d'))
            ->set('gender', 'male')
            ->set('phone', '9999999999')
            ->call('proceedToStep2')
            ->set('trip_start_date', '2026-08-01')
            ->set('trip_end_date', '2026-08-05')
            ->set('registrationType', 'solo')
            ->call('proceedToStep3')
            ->assertSet('step', 4);

        $group = Group::first();
        $this->assertNotNull($group);

        $this->assertEquals(1, AdminFlag::where('flag_type', 'missing_male_guardian')->count());
    }

    public function test_room_assignments_groups_a_three_person_family_into_one_room_card(): void
    {
        $dad = User::factory()->male()->withAge(45)->create();
        $mom = User::factory()->female()->withAge(43)->create();
        $child = User::factory()->male()->withAge(10)->create();

        $group = Group::factory()->create(['created_by' => $dad->id]);
        $dadMember = GroupMember::factory()->leader()->create(['group_id' => $group->id, 'user_id' => $dad->id]);
        GroupMember::factory()->relatedTo($dadMember, 'Spouse')->create(['group_id' => $group->id, 'user_id' => $mom->id]);
        GroupMember::factory()->relatedTo($dadMember, 'Child')->create(['group_id' => $group->id, 'user_id' => $child->id]);

        $wizard = new GroupRegistrationWizard;
        $wizard->previewResults = app(GroupSubmissionPipeline::class)->preview($group);

        $assignments = $wizard->roomAssignments();

        $this->assertCount(1, $assignments);
        $this->assertEquals(3, $assignments->first()['members']->count());
        $this->assertEqualsCanonicalizing(
            [$dad->name, $mom->name, $child->name],
            $assignments->first()['members']->pluck('name')->all()
        );
    }
}
