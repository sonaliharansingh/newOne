<?php

namespace App\Services\Allocation\Strategies;

use App\Models\FamilyCluster;
use App\Models\Group;
use App\Services\Allocation\AbstractAllocationStrategy;
use Illuminate\Support\Collection;

/**
 * Priority 2: a nuclear family (self/spouse/partner + children, no extended relations) of two
 * or more members. Tries one private room sized to the whole cluster; falls through if none
 * fits. Two-member nuclear clusters (e.g. a father + one child tagged Self/Child, not a couple)
 * reach here when CoupleRoomStrategy didn't claim them.
 */
class ImmediateFamilyStrategy extends AbstractAllocationStrategy
{
    private const NUCLEAR_RELATIONS = ['Self', 'Spouse', 'Partner', 'Child'];

    protected function priorityLevel(): int
    {
        return 2;
    }

    protected function label(): string
    {
        return 'Priority 2: Immediate family room';
    }

    public function attempt(FamilyCluster $cluster, Collection $members, Group $group): bool
    {
        if ($members->count() < 2) {
            return false;
        }

        $relationTypes = $members->pluck('relation_type')->unique();

        if ($relationTypes->diff(self::NUCLEAR_RELATIONS)->isNotEmpty()) {
            return false;
        }

        $womenOnly = $this->isAllFemale($members) ? null : false;

        $room = $this->findRoom($members->count(), requirePrivate: true, womenOnly: $womenOnly, preferElderlyFriendly: $this->hasSenior($members));

        if (! $room) {
            return false;
        }

        $this->allocateMembersToRoom($members, $room, $cluster, $group);

        return true;
    }
}
