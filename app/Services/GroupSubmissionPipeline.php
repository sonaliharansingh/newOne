<?php

namespace App\Services;

use App\Jobs\DeliverPilgrimNotification;
use App\Models\Allocation;
use App\Models\AuditLog;
use App\Models\Group;
use App\Models\PilgrimNotification;
use App\Models\User;
use App\Services\Allocation\PriorityAllocationEngine;
use Illuminate\Support\Facades\DB;

/**
 * Orchestrates the submission flow: the graph itself is just the group_members rows
 * (related_user_id/relation_type) captured by the wizard, so this starts from cluster
 * detection. Steps: detect clusters -> validate guardians (flag + skip if invalid) ->
 * run the priority engine for valid clusters -> return a preview. finalize() is called
 * once an admin confirms the preview, flipping allocations to allocated and notifying.
 */
class GroupSubmissionPipeline
{
    public function __construct(
        private ClusterDetectionService $clusterDetection,
        private GuardianValidationService $guardianValidation,
        private PriorityAllocationEngine $allocationEngine,
    ) {}

    /**
     * @return array<int, array{cluster: \App\Models\FamilyCluster, status: string, reason: ?string}>
     */
    public function preview(Group $group): array
    {
        $clusters = $this->clusterDetection->detect($group);

        $results = [];

        foreach ($clusters as $cluster) {
            $this->allocationEngine->resetCluster($cluster);

            if (! $this->guardianValidation->validate($cluster)) {
                $results[] = [
                    'cluster' => $cluster->fresh(['members.user']),
                    'status' => 'blocked',
                    'reason' => 'Guardian requirement unmet — awaiting admin resolution.',
                ];

                continue;
            }

            $allocated = $this->allocationEngine->allocate($cluster, $group);

            $results[] = [
                'cluster' => $cluster->fresh(['members.user', 'allocations.user', 'allocations.room.hotel']),
                'status' => $allocated ? 'allocated' : 'unallocated',
                'reason' => $allocated ? null : 'No matching room capacity available.',
            ];
        }

        return $results;
    }

    public function finalize(Group $group, User $admin): void
    {
        DB::transaction(function () use ($group, $admin) {
            Allocation::where('group_id', $group->id)
                ->where('allocation_status', 'pending')
                ->update([
                    'allocation_status' => 'allocated',
                    'allocated_by' => $admin->id,
                ]);

            $group->update(['status' => 'allocated']);

            AuditLog::create([
                'user_id' => $admin->id,
                'module' => 'allocation',
                'action' => 'finalized',
                'reference_id' => $group->id,
                'description' => "Finalized allocation for group #{$group->id} ({$group->group_name}), booking {$group->bookingId()}.",
            ]);
        });

        $this->notifyMembers($group);
    }

    private function notifyMembers(Group $group): void
    {
        $message = "Your booking {$group->bookingId()} has been confirmed. Check your dashboard for room details.";

        foreach ($group->members()->with('user')->get() as $member) {
            // Dashboard notifications are just an in-app record, so there's nothing to
            // deliver — mark sent immediately rather than queuing a no-op job.
            PilgrimNotification::create([
                'user_id' => $member->user_id,
                'group_id' => $group->id,
                'title' => 'Room allocation confirmed',
                'message' => $message,
                'type' => 'dashboard',
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            foreach (['email', 'sms'] as $channel) {
                $notification = PilgrimNotification::create([
                    'user_id' => $member->user_id,
                    'group_id' => $group->id,
                    'title' => 'Room allocation confirmed',
                    'message' => $message,
                    'type' => $channel,
                    'status' => 'pending',
                ]);

                DeliverPilgrimNotification::dispatch($notification->id);
            }
        }
    }
}
