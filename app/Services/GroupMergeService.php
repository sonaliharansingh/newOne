<?php

namespace App\Services;

use App\Models\Allocation;
use App\Models\AuditLog;
use App\Models\Group;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Merges one group's members into another — the "spouse registered separately" edge case.
 * Releases any beds the source group's members were already holding, moves every
 * group_members row onto the target group, clears their stale cluster_id (clusters get
 * recomputed fresh next time the pipeline runs), and deletes the now-empty source group.
 * Declaring a relation between the newly merged members is a separate manual step in the
 * admin UI, since merging alone can't infer who is related to whom.
 */
class GroupMergeService
{
    public function merge(Group $source, Group $target): void
    {
        if ($source->id === $target->id) {
            throw ValidationException::withMessages(['group' => 'Cannot merge a group into itself.']);
        }

        DB::transaction(function () use ($source, $target) {
            $this->releaseAllocations($source);

            $source->members()->update([
                'group_id' => $target->id,
                'cluster_id' => null,
            ]);

            $target->update([
                'expected_members' => $target->members()->count(),
                'joined_members' => $target->members()->count(),
            ]);

            $source->refresh();
            $source->clusters()->delete();

            AuditLog::create([
                'user_id' => auth()->id(),
                'module' => 'group_merge',
                'action' => 'merged',
                'reference_id' => $target->id,
                'description' => "Merged group #{$source->id} ({$source->group_name}) into group #{$target->id} ({$target->group_name}).",
            ]);

            $source->delete();
        });
    }

    private function releaseAllocations(Group $group): void
    {
        $allocations = Allocation::where('group_id', $group->id)->with('room')->get();

        foreach ($allocations as $allocation) {
            if ($room = $allocation->room) {
                $room->occupied_count = max(0, $room->occupied_count - 1);
                $room->available_count = min($room->capacity, $room->available_count + 1);
                $room->room_status = $room->available_count <= 0 ? 'full' : ($room->occupied_count > 0 ? 'partial' : 'available');
                $room->save();
            }

            $allocation->delete();
        }
    }
}
