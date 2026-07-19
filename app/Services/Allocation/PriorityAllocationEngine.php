<?php

namespace App\Services\Allocation;

use App\Models\Allocation;
use App\Models\FamilyCluster;
use App\Models\Group;
use App\Services\Allocation\Strategies\CoupleRoomStrategy;
use App\Services\Allocation\Strategies\ExtendedFamilyStrategy;
use App\Services\Allocation\Strategies\GenderAgeSplitStrategy;
use App\Services\Allocation\Strategies\ImmediateFamilyStrategy;
use App\Services\Allocation\Strategies\SoloPoolStrategy;
use Illuminate\Support\Facades\DB;

/**
 * Runs a family cluster through the five priority strategies in order, stopping at the
 * first one that fully places every member. Allocations are written with
 * allocation_status=pending; the admin confirm step later flips them to allocated.
 */
class PriorityAllocationEngine
{
    /** @var AllocationStrategyInterface[] */
    private array $strategies;

    public function __construct()
    {
        $this->strategies = [
            app(CoupleRoomStrategy::class),
            app(ImmediateFamilyStrategy::class),
            app(ExtendedFamilyStrategy::class),
            app(GenderAgeSplitStrategy::class),
            app(SoloPoolStrategy::class),
        ];
    }

    /**
     * @return bool true if the cluster was fully allocated, false if no strategy could place it
     */
    public function allocate(FamilyCluster $cluster, Group $group): bool
    {
        $members = $cluster->members()->with('user')->get();

        if ($members->isEmpty()) {
            return true;
        }

        return DB::transaction(function () use ($cluster, $members, $group) {
            foreach ($this->strategies as $strategy) {
                if ($strategy->attempt($cluster, $members, $group)) {
                    $cluster->update(['allocation_status' => 'allocated']);

                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Removes any previously written pending allocations for this cluster and frees the
     * rooms, so a cluster can be re-run through the engine (e.g. after a guardian flag
     * is resolved or the group's members change) without double-booking beds.
     */
    public function resetCluster(FamilyCluster $cluster): void
    {
        DB::transaction(function () use ($cluster) {
            $allocations = Allocation::where('cluster_id', $cluster->id)
                ->where('allocation_status', 'pending')
                ->with('room')
                ->get();

            foreach ($allocations as $allocation) {
                if ($room = $allocation->room) {
                    $room->occupied_count = max(0, $room->occupied_count - 1);
                    $room->available_count = min($room->capacity, $room->available_count + 1);
                    $room->room_status = $room->available_count <= 0 ? 'full' : ($room->occupied_count > 0 ? 'partial' : 'available');

                    // Once the room is empty again its runtime locks fall away: the gender lock
                    // and any cluster reservation are only meaningful while someone occupies it.
                    if ($room->occupied_count === 0) {
                        $room->gender_lock = null;
                        $room->reserved_for_cluster_id = null;
                    } elseif ($room->reserved_for_cluster_id === $cluster->id) {
                        // The reserving cluster is being torn down but beds remain (another
                        // cluster shares the room) — drop the stale reservation.
                        $room->reserved_for_cluster_id = null;
                    }

                    $room->save();
                }

                $allocation->delete();
            }

            $cluster->update(['allocation_status' => 'pending']);
        });
    }
}
