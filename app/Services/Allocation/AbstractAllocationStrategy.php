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
     *
     * Applies the three placement constraints uniformly:
     *  - women_only / gender_lock: a room already seeded by one gender (or permanently
     *    women-only) only accepts that gender; a mixed family only accepts an unlocked room.
     *  - reserved_for_cluster_id: a room a family already claimed is invisible to everyone
     *    except that same cluster.
     *  - capacity + maintenance.
     *
     * Rooms are SELECT ... FOR UPDATE-locked (real DBs) so two concurrent allocations can
     * never both claim the last bed.
     *
     * @param  ?string  $placeGender  the single gender being placed into a shared room
     *                                ('male'|'female'|'other'); pass null for a mixed family,
     *                                which then requires a room with no gender_lock at all.
     * @param  bool  $allFemale  when false, permanently women_only rooms are excluded.
     * @param  ?GroupMember  $anchor  when set (shared pooling), prefer consolidating into a
     *                                partial room whose occupants share this member's locality.
     */
    protected function findRoom(
        int $headcount,
        bool $requirePrivate,
        ?string $placeGender,
        bool $allFemale,
        int $clusterId,
        bool $excludeDormitory = true,
        bool $preferElderlyFriendly = false,
        ?GroupMember $anchor = null,
    ): ?Room {
        return $this->pickRoom(
            $this->candidateRooms($headcount, $requirePrivate, $placeGender, $allFemale, $clusterId, $excludeDormitory, $preferElderlyFriendly),
            $anchor,
        );
    }

    protected function findRoomExcluding(
        int $headcount,
        bool $requirePrivate,
        ?string $placeGender,
        bool $allFemale,
        int $clusterId,
        array $excludeRoomIds,
        bool $excludeDormitory = true,
        bool $preferElderlyFriendly = false,
    ): ?Room {
        $rooms = $this->candidateRooms($headcount, $requirePrivate, $placeGender, $allFemale, $clusterId, $excludeDormitory, $preferElderlyFriendly)
            ->reject(fn (Room $room) => in_array($room->id, $excludeRoomIds, true))
            ->values();

        return $this->pickRoom($rooms, null);
    }

    /**
     * @return Collection<int, Room>
     */
    protected function candidateRooms(
        int $headcount,
        bool $requirePrivate,
        ?string $placeGender,
        bool $allFemale,
        int $clusterId,
        bool $excludeDormitory = true,
        bool $preferElderlyFriendly = false,
    ): Collection {
        return Room::query()
            ->where('room_status', '!=', 'maintenance')
            ->where('available_count', '>=', $headcount)
            ->when($requirePrivate, fn ($query) => $query->where('is_private', true))
            ->when($excludeDormitory, fn ($query) => $query->where('room_type', '!=', 'dormitory'))
            // A mixed family (placeGender === null) needs a room with no gender_lock; a single
            // gender needs the lock to be null or already its own gender.
            ->when($placeGender === null, fn ($query) => $query->whereNull('gender_lock'))
            ->when($placeGender !== null, fn ($query) => $query->where(fn ($q) => $q->whereNull('gender_lock')->orWhere('gender_lock', $placeGender)))
            // Permanent women-only stock is off-limits to anyone not travelling all-female.
            ->when(! $allFemale, fn ($query) => $query->where('women_only', false))
            // Rooms a different cluster reserved are invisible; this cluster's own reserved rooms stay visible.
            ->where(fn ($query) => $query->whereNull('reserved_for_cluster_id')->orWhere('reserved_for_cluster_id', $clusterId))
            ->when($preferElderlyFriendly, fn ($query) => $query->orderByDesc('elderly_friendly')->orderByDesc('lift_access'))
            ->when($allFemale, fn ($query) => $query->orderByDesc('women_only'))
            ->orderBy('available_count')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * Picks the winning room. With no anchor (family/private placement) this is simply the
     * first best-fit room. With an anchor (shared pooling) it prefers to consolidate the
     * pilgrim into a partly-filled room whose occupants share their locality, so same
     * city/area/language travellers end up together — never leaving a partial bed stranded.
     *
     * @param  Collection<int, Room>  $rooms
     */
    protected function pickRoom(Collection $rooms, ?GroupMember $anchor): ?Room
    {
        if ($rooms->isEmpty()) {
            return null;
        }

        if ($anchor === null) {
            return $rooms->first();
        }

        return $rooms
            ->sortByDesc(fn (Room $room) => $this->consolidationScore($room, $anchor))
            ->first();
    }

    /**
     * Ranks a candidate room for shared pooling: fill partial rooms before opening fresh ones,
     * and among partial rooms prefer the closest locality match (city 4, area 2, language 1).
     */
    protected function consolidationScore(Room $room, GroupMember $anchor): int
    {
        if ($room->occupied_count <= 0) {
            return 0; // A fresh room is the fallback, ranked below any occupied room.
        }

        $base = 1000; // Any partial room beats any empty room, so no bed is left stranded.

        return $base + $this->localityScore($room, $anchor);
    }

    protected function localityScore(Room $room, GroupMember $anchor): int
    {
        $user = $anchor->user;

        $occupants = Allocation::where('room_id', $room->id)
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter();

        $best = 0;

        foreach ($occupants as $occupant) {
            $score = 0;
            $score += ($user->city && $occupant->city === $user->city) ? 4 : 0;
            $score += ($user->area && $occupant->area === $user->area) ? 2 : 0;
            $score += ($user->language && $occupant->language === $user->language) ? 1 : 0;
            $best = max($best, $score);
        }

        return $best;
    }

    /**
     * Applies an occupancy delta to a room and re-derives its status. When a shared room takes
     * its first occupant it is stamped with that gender (gender_lock); when a family claims it,
     * it is reserved to that cluster. Both are only ever set, never overwritten, here.
     */
    protected function occupyRoom(Room $room, int $count, ?string $genderLock = null, ?int $reserveForCluster = null): void
    {
        $room->occupied_count += $count;
        $room->available_count -= $count;

        if ($genderLock !== null && $room->gender_lock === null) {
            $room->gender_lock = $genderLock;
        }

        if ($reserveForCluster !== null && $room->reserved_for_cluster_id === null) {
            $room->reserved_for_cluster_id = $reserveForCluster;
        }

        $room->room_status = $room->available_count <= 0 ? 'full' : 'partial';
        $room->save();
    }

    /**
     * Writes one pending allocation per member and updates the room's occupancy/locks.
     *
     * @param  Collection<int, GroupMember>  $members
     * @param  bool  $reserveForFamily  true for a family/couple room (Priority 1-3): the room
     *                                  is reserved to the cluster so no outsider takes the spare
     *                                  beds. false for shared pooling (Priority 4-5): the room
     *                                  is instead gender-locked to the (single-gender) bucket.
     */
    protected function allocateMembersToRoom(Collection $members, Room $room, FamilyCluster $cluster, Group $group, bool $reserveForFamily = false, ?string $genderLock = null): void
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

        [$lock, $reserveForCluster] = $reserveForFamily
            ? [null, $cluster->id]
            : [$genderLock ?? $this->bucketGender($members), null];

        $this->occupyRoom($room, $members->count(), $lock, $reserveForCluster);
    }

    /**
     * The single gender to lock a shared room to, or null if the bucket is somehow mixed
     * (which single-gender pooling buckets never are).
     *
     * @param  Collection<int, GroupMember>  $members
     */
    protected function bucketGender(Collection $members): ?string
    {
        $genders = $members->map(fn (GroupMember $member) => $member->user->gender)->unique()->values();

        return $genders->count() === 1 ? $genders->first() : null;
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
