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
        $gender = $member->user->gender;
        $allFemale = $gender === 'female';
        $preferElderlyFriendly = $this->hasSenior($members);

        // Shared stock first (dormitories included), consolidating this pilgrim into a partial
        // room that is gender-compatible and closest on locality (city -> area -> language),
        // so no partial bed is left stranded; a private room is the last resort.
        $room = $this->findRoom(1, requirePrivate: false, placeGender: $gender, allFemale: $allFemale, clusterId: $cluster->id, excludeDormitory: false, preferElderlyFriendly: $preferElderlyFriendly, anchor: $member)
            ?? $this->findRoom(1, requirePrivate: true, placeGender: $gender, allFemale: $allFemale, clusterId: $cluster->id, preferElderlyFriendly: $preferElderlyFriendly, anchor: $member);

        if (! $room) {
            return false;
        }

        $this->allocateMembersToRoom($members, $room, $cluster, $group);

        return true;
    }
}
