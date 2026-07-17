<?php

namespace App\Services;

use App\Models\Allocation;
use App\Models\AllocationLog;
use App\Models\AuditLog;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Manual room reassignment for a single allocation, used by the admin override screen.
 * Frees a bed on the old room, occupies one on the new room, and records the change in
 * allocation_logs for audit purposes.
 */
class RoomOverrideService
{
    public function override(Allocation $allocation, Room $newRoom, User $admin, ?string $reason = null): void
    {
        if ($newRoom->id === $allocation->room_id) {
            throw ValidationException::withMessages(['room' => 'The member is already in that room.']);
        }

        if (! $newRoom->hasCapacityFor(1)) {
            throw ValidationException::withMessages(['room' => 'That room has no available capacity.']);
        }

        DB::transaction(function () use ($allocation, $newRoom, $admin, $reason) {
            $oldRoom = $allocation->room;

            if ($oldRoom) {
                $oldRoom->occupied_count = max(0, $oldRoom->occupied_count - 1);
                $oldRoom->available_count = min($oldRoom->capacity, $oldRoom->available_count + 1);
                $oldRoom->room_status = $oldRoom->available_count <= 0 ? 'full' : ($oldRoom->occupied_count > 0 ? 'partial' : 'available');
                $oldRoom->save();
            }

            $newRoom->occupied_count += 1;
            $newRoom->available_count -= 1;
            $newRoom->room_status = $newRoom->available_count <= 0 ? 'full' : 'partial';
            $newRoom->save();

            AllocationLog::create([
                'allocation_id' => $allocation->id,
                'action' => 'manual_override',
                'old_room_id' => $oldRoom?->id,
                'new_room_id' => $newRoom->id,
                'changed_by' => $admin->id,
                'reason' => $reason,
            ]);

            $allocation->update([
                'room_id' => $newRoom->id,
                'allocation_type' => 'manual',
                'allocated_by' => $admin->id,
            ]);

            AuditLog::create([
                'user_id' => $admin->id,
                'module' => 'room_override',
                'action' => 'manual_override',
                'reference_id' => $allocation->id,
                'description' => "Moved allocation #{$allocation->id} from room #{$oldRoom?->id} to room #{$newRoom->id}".($reason ? " — {$reason}" : ''),
            ]);
        });
    }
}
