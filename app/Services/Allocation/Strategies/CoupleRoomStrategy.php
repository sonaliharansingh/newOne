<?php

namespace App\Services\Allocation\Strategies;

use App\Models\FamilyCluster;
use App\Models\Group;
use App\Models\GroupMember;
use App\Services\Allocation\AbstractAllocationStrategy;
use Illuminate\Support\Collection;

/**
 * Priority 1: a bare Spouse/Partner pair (no children in the cluster) goes into one private
 * room. Partner (unmarried couples, e.g. girlfriend/boyfriend) is treated identically to Spouse.
 */
class CoupleRoomStrategy extends AbstractAllocationStrategy
{
    private const COUPLE_RELATIONS = ['Spouse', 'Partner'];

    protected function priorityLevel(): int
    {
        return 1;
    }

    protected function label(): string
    {
        return 'Priority 1: Couple room';
    }

    public function attempt(FamilyCluster $cluster, Collection $members, Group $group): bool
    {
        if ($members->count() !== 2) {
            return false;
        }

        if (! $members->contains(fn (GroupMember $member) => in_array($member->relation_type, self::COUPLE_RELATIONS, true))) {
            return false;
        }

        $room = $this->findRoom(
            2,
            requirePrivate: true,
            placeGender: null,
            allFemale: $this->isAllFemale($members),
            clusterId: $cluster->id,
            preferElderlyFriendly: $this->hasSenior($members),
        );

        if (! $room) {
            return false;
        }

        $this->allocateMembersToRoom($members, $room, $cluster, $group, reserveForFamily: true);

        return true;
    }
}
