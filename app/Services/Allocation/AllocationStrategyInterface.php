<?php

namespace App\Services\Allocation;

use App\Models\FamilyCluster;
use App\Models\Group;
use Illuminate\Support\Collection;

interface AllocationStrategyInterface
{
    /**
     * Attempt to allocate every member of the cluster. Returns true when the cluster is
     * fully handled (allocations written); false means "fall through" to the next priority.
     *
     * @param  Collection<int, \App\Models\GroupMember>  $members
     */
    public function attempt(FamilyCluster $cluster, Collection $members, Group $group): bool;
}
