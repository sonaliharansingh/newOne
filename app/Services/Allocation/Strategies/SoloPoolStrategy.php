<?php

namespace App\Services\Allocation\Strategies;

use App\Models\FamilyCluster;
use App\Models\Group;
use App\Services\Allocation\AbstractAllocationStrategy;
use Illuminate\Support\Collection;

/**
 * Priority 5: a lone solo traveller (cluster of exactly one member, no declared relations).
 * Fills whatever bed is left, preferring shared rooms over private ones so private stock
 * stays reserved for families higher up the priority chain.
 */
class SoloPoolStrategy extends AbstractAllocationStrategy
{
    protected function priorityLevel(): int
    {
        return 5;
    }

    protected function label(): string
    {
        return 'Priority 5: Solo pool fill';
    }

    public function attempt(FamilyCluster $cluster, Collection $members, Group $group): bool
    {
        if ($members->count() !== 1) {
            return false;
        }

        $member = $members->first();
        $womenOnly = $member->user->gender === 'female' ? null : false;
        $preferElderlyFriendly = $this->hasSenior($members);

        $room = $this->findRoom(1, requirePrivate: false, womenOnly: $womenOnly, excludeDormitory: false, preferElderlyFriendly: $preferElderlyFriendly)
            ?? $this->findRoom(1, requirePrivate: true, womenOnly: $womenOnly, preferElderlyFriendly: $preferElderlyFriendly);

        if (! $room) {
            return false;
        }

        $this->allocateMembersToRoom($members, $room, $cluster, $group);

        return true;
    }
}
