<?php

namespace App\Services\Allocation;

use App\Models\Allocation;
use App\Models\FamilyCluster;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Room;
use Illuminate\Support\Collection;

abstract class AbstractAllocationStrategy implements AllocationStrategyInterface
{
    abstract protected function priorityLevel(): int;

    abstract protected function label(): string;

    /**
     * Locks and returns the best-fit available room for the given headcount, or null.
     * Prefers private rooms whose capacity is closest to the headcount (least wasted beds).
     *
     * @param  bool  $excludeDormitory  dormitories are shared bunk-style stock reserved for
     *                                  Priority 4/5 pooling — family strategies (1-3) must
     *                                  never claim one exclusively for a single family.
     * @param  bool  $preferElderlyFriendly  soft preference (never excludes a room) for
     *                                       elderly_friendly/lift_access stock when the cluster
     *                                       includes a senior (60+) member.
     */
    protected function findRoom(int $headcount, bool $requirePrivate, ?bool $womenOnly = null, bool $excludeDormitory = true, bool $preferElderlyFriendly = false): ?Room
    {
        return $this->findRoomQuery($headcount, $requirePrivate, $womenOnly, $excludeDormitory, $preferElderlyFriendly)->first();
    }

    protected function findRoomExcluding(int $headcount, bool $requirePrivate, ?bool $womenOnly, array $excludeRoomIds, bool $excludeDormitory = true, bool $preferElderlyFriendly = false): ?Room
    {
        return $this->findRoomQuery($headcount, $requirePrivate, $womenOnly, $excludeDormitory, $preferElderlyFriendly)
            ->when(! empty($excludeRoomIds), fn ($query) => $query->whereNotIn('id', $excludeRoomIds))
            ->first();
    }

    private function findRoomQuery(int $headcount, bool $requirePrivate, ?bool $womenOnly, bool $excludeDormitory = true, bool $preferElderlyFriendly = false)
    {
        return Room::query()
            ->where('room_status', '!=', 'maintenance')
            ->where('available_count', '>=', $headcount)
            ->when($requirePrivate, fn ($query) => $query->where('is_private', true))
            ->when($excludeDormitory, fn ($query) => $query->where('room_type', '!=', 'dormitory'))
            ->when($womenOnly !== null, fn ($query) => $query->where('women_only', $womenOnly))
            ->when($preferElderlyFriendly, fn ($query) => $query->orderByDesc('elderly_friendly')->orderByDesc('lift_access'))
            ->orderBy('available_count')
            ->orderBy('id');
    }

    protected function occupyRoom(Room $room, int $count): void
    {
        $room->occupied_count += $count;
        $room->available_count -= $count;
        $room->room_status = $room->available_count <= 0 ? 'full' : 'partial';
        $room->save();
    }

    /**
     * @param  Collection<int, GroupMember>  $members
     */
    protected function allocateMembersToRoom(Collection $members, Room $room, FamilyCluster $cluster, Group $group): void
    {
        foreach ($members as $member) {
            Allocation::create([
                'group_id' => $group->id,
                'cluster_id' => $cluster->id,
                'user_id' => $member->user_id,
                'room_id' => $room->id,
                'allocated_by' => null,
                'allocation_type' => 'auto',
                'allocation_score' => $member->relation_score,
                'priority_level' => $this->priorityLevel(),
                'allocation_status' => 'pending',
                'remarks' => $this->label(),
                'free_stay' => $member->user->age !== null && $member->user->age < 12,
            ]);

            $member->update(['allocation_priority' => $this->priorityLevel()]);
        }

        $this->occupyRoom($room, $members->count());
    }

    /**
     * @param  Collection<int, GroupMember>  $members
     */
    protected function isAllFemale(Collection $members): bool
    {
        return $members->every(fn (GroupMember $member) => $member->user->gender === 'female');
    }

    /**
     * @param  Collection<int, GroupMember>  $members
     */
    protected function hasSenior(Collection $members): bool
    {
        return $members->contains(fn (GroupMember $member) => $member->user->age !== null && $member->user->age >= 60);
    }
}
