<?php

namespace App\Services\Allocation\Strategies;

use App\Models\FamilyCluster;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Room;
use App\Services\Allocation\AbstractAllocationStrategy;
use App\Services\GuardianValidationService;
use Illuminate\Support\Collection;

/**
 * Priority 3: extended or oversized family clusters (siblings, grandparents, cousins, or a
 * nuclear family too large for one room). Tries a single room for everyone first; otherwise
 * splits by gender into two rooms on the same floor when possible. Minors always stay with
 * whichever gender-group holds their guardian, since GuardianValidationService already
 * confirmed an eligible guardian exists in the cluster before this strategy runs.
 *
 * Exception: a cluster with a female minor whose only eligible guardian is male (see
 * GuardianValidationService::hasSoloMaleGuardianException) must never be pooled or gender-split
 * — it's private-room-only, all the way down. If no private room fits, the cluster stays
 * unplaced rather than compromising that guarantee.
 */
class ExtendedFamilyStrategy extends AbstractAllocationStrategy
{
    public function __construct(private GuardianValidationService $guardianValidation) {}

    protected function priorityLevel(): int
    {
        return 3;
    }

    protected function label(): string
    {
        return 'Priority 3: Extended family room';
    }

    public function attempt(FamilyCluster $cluster, Collection $members, Group $group): bool
    {
        if ($members->count() < 2) {
            return false;
        }

        $privacyOnly = $this->guardianValidation->hasSoloMaleGuardianException($members);
        $preferElderlyFriendly = $this->hasSenior($members);
        $womenOnly = $this->isAllFemale($members) ? null : false;

        $singleRoom = $privacyOnly
            ? $this->findRoom($members->count(), requirePrivate: true, womenOnly: false, preferElderlyFriendly: $preferElderlyFriendly)
            : $this->findRoom($members->count(), requirePrivate: false, womenOnly: $womenOnly, preferElderlyFriendly: $preferElderlyFriendly)
                ?? $this->findRoom($members->count(), requirePrivate: true, womenOnly: $womenOnly, preferElderlyFriendly: $preferElderlyFriendly);

        if ($singleRoom) {
            $this->allocateMembersToRoom($members, $singleRoom, $cluster, $group);

            return true;
        }

        if ($privacyOnly) {
            return false;
        }

        return $this->genderSplit($cluster, $members, $group);
    }

    private function genderSplit(FamilyCluster $cluster, Collection $members, Group $group): bool
    {
        [$femaleGroup, $maleGroup] = $members->partition(
            fn (GroupMember $member) => $member->user->gender === 'female' || $member->isMinorRequiringFemaleGuardian()
        );

        $femaleGroup = $femaleGroup->values();
        $maleGroup = $maleGroup->values();

        if ($femaleGroup->isEmpty() || $maleGroup->isEmpty()) {
            return false;
        }

        $femaleRoom = $this->findRoom($femaleGroup->count(), requirePrivate: false, womenOnly: true, preferElderlyFriendly: $this->hasSenior($femaleGroup))
            ?? $this->findRoom($femaleGroup->count(), requirePrivate: false, womenOnly: null, preferElderlyFriendly: $this->hasSenior($femaleGroup));

        if (! $femaleRoom) {
            return false;
        }

        $maleRoom = $this->findAdjacentRoom($maleGroup->count(), $femaleRoom, $this->hasSenior($maleGroup));

        if (! $maleRoom) {
            return false;
        }

        $this->allocateMembersToRoom($femaleGroup, $femaleRoom, $cluster, $group);
        $this->allocateMembersToRoom($maleGroup, $maleRoom, $cluster, $group);

        return true;
    }

    private function findAdjacentRoom(int $headcount, Room $anchor, bool $preferElderlyFriendly = false): ?Room
    {
        // "Adjacent" is satisfied at floor level (same floor as the anchor room); rooms are
        // ordered by available_count for best-fit rather than by parsing room_number, since
        // room_number is a free-form string and that parsing isn't portable across DB engines.
        $sameFloor = Room::query()
            ->where('room_status', '!=', 'maintenance')
            ->where('available_count', '>=', $headcount)
            ->where('women_only', false)
            ->where('room_type', '!=', 'dormitory')
            ->where('floor_id', $anchor->floor_id)
            ->where('id', '!=', $anchor->id)
            ->when($preferElderlyFriendly, fn ($query) => $query->orderByDesc('elderly_friendly')->orderByDesc('lift_access'))
            ->orderBy('available_count')
            ->first();

        return $sameFloor ?? $this->findRoomExcluding($headcount, requirePrivate: false, womenOnly: false, excludeRoomIds: [$anchor->id], preferElderlyFriendly: $preferElderlyFriendly);
    }
}
