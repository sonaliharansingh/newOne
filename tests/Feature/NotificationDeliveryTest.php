<?php

namespace Tests\Feature;

use App\Mail\AllocationConfirmedMail;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\PilgrimNotification;
use App\Models\User;
use App\Services\GroupSubmissionPipeline;
use Database\Seeders\HotelSeeder;
use Database\Seeders\RelationshipRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_finalize_creates_and_delivers_notifications_on_every_channel(): void
    {
        Mail::fake();

        $this->seed(RelationshipRuleSeeder::class);
        $this->seed(HotelSeeder::class);

        $admin = User::factory()->admin()->create();
        $leader = User::factory()->create(['phone' => '9999999999']);
        $group = Group::factory()->create(['created_by' => $leader->id]);
        GroupMember::factory()->leader()->create(['group_id' => $group->id, 'user_id' => $leader->id]);

        app(GroupSubmissionPipeline::class)->preview($group);
        app(GroupSubmissionPipeline::class)->finalize($group, $admin);

        $notifications = PilgrimNotification::where('user_id', $leader->id)->get();

        $this->assertCount(3, $notifications);
        $this->assertEquals('sent', $notifications->firstWhere('type', 'dashboard')->status);
        $this->assertEquals('sent', $notifications->firstWhere('type', 'email')->status);
        $this->assertEquals('sent', $notifications->firstWhere('type', 'sms')->status);

        Mail::assertSent(AllocationConfirmedMail::class, function ($mail) use ($leader) {
            return $mail->hasTo($leader->email);
        });
    }
}
