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
    /** Spouse/Partner relations whose two members must never be separated by the gender split. */
    private const COUPLE_RELATIONS = ['Spouse', 'Partner'];

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
        $allFemale = $this->isAllFemale($members);
        $count = $members->count();

        // A whole (possibly mixed-gender) family in one room needs a room with no gender_lock;
        // placeGender is null so only unlocked rooms qualify, and the room is then reserved to
        // the cluster so no outsider fills the spare beds.
        $singleRoom = $privacyOnly
            ? $this->findRoom($count, requirePrivate: true, placeGender: null, allFemale: false, clusterId: $cluster->id, preferElderlyFriendly: $preferElderlyFriendly)
            : $this->findRoom($count, requirePrivate: false, placeGender: null, allFemale: $allFemale, clusterId: $cluster->id, preferElderlyFriendly: $preferElderlyFriendly)
                ?? $this->findRoom($count, requirePrivate: true, placeGender: null, allFemale: $allFemale, clusterId: $cluster->id, preferElderlyFriendly: $preferElderlyFriendly);

        if ($singleRoom) {
            $this->allocateMembersToRoom($members, $singleRoom, $cluster, $group, reserveForFamily: true);

            return true;
        }

        if ($privacyOnly) {
            return false;
        }

        return $this->genderSplit($cluster, $members, $group);
    }

    private function genderSplit(FamilyCluster $cluster, Collection $members, Group $group): bool
    {
        // A Spouse/Partner couple straddles the gender line, so a plain male/female split would
        // separate them. For an all-adult cluster we keep couples whole (both partners on the
        // same side). When a minor requiring a guardian is present the older behaviour wins —
        // under-15s ride with the female guardian — since a two-adult couple kept together
        // could otherwise strand the minors in a room with no adult.
        [$groupA, $groupB] = $this->hasMinorRequiringGuardian($members)
            ? $this->genderPartition($members)
            : $this->coupleAwarePartition($members);

        if ($groupA->isEmpty() || $groupB->isEmpty()) {
            // The couple-aware split put everyone on one side (e.g. no non-couple male exists);
            // fall back to a plain gender partition before giving up.
            [$groupA, $groupB] = $this->genderPartition($members);
        }

        if ($groupA->isEmpty() || $groupB->isEmpty()) {
            return false;
        }

        // Each split room stays the family's own (reserved to the cluster) so no stranger takes
        // the spare beds; placeGender only steers away from rooms a stranger already gender-locked.
        // A mixed side (a couple) needs an unlocked room (bucketGender null), so no stranger's
        // gender-locked room is reused for it.
        $roomA = $this->findRoom(
            $groupA->count(),
            requirePrivate: false,
            placeGender: $this->bucketGender($groupA),
            allFemale: $this->isAllFemale($groupA),
            clusterId: $cluster->id,
            preferElderlyFriendly: $this->hasSenior($groupA),
        );

        if (! $roomA) {
            return false;
        }

        $roomB = $this->findAdjacentRoom($groupB, $roomA, $cluster->id, $this->hasSenior($groupB));

        if (! $roomB) {
            return false;
        }

        $this->allocateMembersToRoom($groupA, $roomA, $cluster, $group, reserveForFamily: true);
        $this->allocateMembersToRoom($groupB, $roomB, $cluster, $group, reserveForFamily: true);

        return true;
    }

    /**
     * The plain split: females (and every under-15 minor, so they ride with a female guardian)
     * in one room, the remaining males in the other.
     *
     * @param  Collection<int, GroupMember>  $members
     * @return array{0: Collection<int, GroupMember>, 1: Collection<int, GroupMember>}
     */
    private function genderPartition(Collection $members): array
    {
        [$femaleGroup, $maleGroup] = $members->partition(
            fn (GroupMember $member) => $member->user->gender === 'female' || $member->isMinorRequiringFemaleGuardian()
        );

        return [$femaleGroup->values(), $maleGroup->values()];
    }

    /**
     * Keeps each Spouse/Partner couple whole by pinning both partners to the same side: couples
     * plus every other female share one room, the remaining (non-couple) males the other.
     *
     * @param  Collection<int, GroupMember>  $members
     * @return array{0: Collection<int, GroupMember>, 1: Collection<int, GroupMember>}
     */
    private function coupleAwarePartition(Collection $members): array
    {
        $coupleIds = $this->coupleMemberIds($members);

        [$groupA, $groupB] = $members->partition(
            fn (GroupMember $member) => in_array($member->id, $coupleIds, true) || $member->user->gender === 'female'
        );

        return [$groupA->values(), $groupB->values()];
    }

    /**
     * The group_member ids belonging to a Spouse/Partner couple: each such member and the member
     * it is related to (related_user_id references a group_members row, despite its name).
     *
     * @param  Collection<int, GroupMember>  $members
     * @return array<int, int>
     */
    private function coupleMemberIds(Collection $members): array
    {
        $byId = $members->keyBy('id');
        $ids = [];

        foreach ($members as $member) {
            if (! in_array($member->relation_type, self::COUPLE_RELATIONS, true)) {
                continue;
            }

            $ids[$member->id] = $member->id;

            if ($member->related_user_id !== null && $byId->has($member->related_user_id)) {
                $ids[$member->related_user_id] = $member->related_user_id;
            }
        }

        return array_values($ids);
    }

    /**
     * @param  Collection<int, GroupMember>  $members
     */
    private function hasMinorRequiringGuardian(Collection $members): bool
    {
        return $members->contains(
            fn (GroupMember $member) => $member->isMinorRequiringFemaleGuardian() || $member->isMinorRequiringMaleGuardian()
        );
    }

    /**
     * @param  Collection<int, GroupMember>  $group
     */
    private function findAdjacentRoom(Collection $group, Room $anchor, int $clusterId, bool $preferElderlyFriendly = false): ?Room
    {
        // "Adjacent" is satisfied at floor level (same floor as the anchor room). The candidate
        // list is already best-fit ordered (available_count) and FOR UPDATE-locked, so we take
        // the first same-floor room, else the first anywhere.
        $candidates = $this->candidateRooms(
            $group->count(),
            requirePrivate: false,
            placeGender: $this->bucketGender($group),
            allFemale: $this->isAllFemale($group),
            clusterId: $clusterId,
            preferElderlyFriendly: $preferElderlyFriendly,
        )->reject(fn (Room $room) => $room->id === $anchor->id)->values();

        return $candidates->firstWhere('floor_id', $anchor->floor_id) ?? $candidates->first();
    }
}
