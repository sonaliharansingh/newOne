<?php

namespace App\Services;

use App\Models\FamilyCluster;
use App\Models\Group;
use App\Models\RelationshipRule;
use Illuminate\Support\Collection;

/**
 * Detects connected clusters (couples, families, friend groups) within a group by
 * running union-find over the group_members.related_user_id forest edges. A member
 * with no related_user_id (the leader, or a "Self" declaration) is its own cluster root.
 */
class ClusterDetectionService
{
    private array $parent = [];

    /**
     * @return Collection<int, FamilyCluster>
     */
    public function detect(Group $group): Collection
    {
        $members = $group->members()->with('user')->get();

        $this->stampRelationScores($members);

        $this->parent = [];
        foreach ($members as $member) {
            $this->parent[$member->id] = $member->id;
        }

        foreach ($members as $member) {
            if ($member->related_user_id !== null && isset($this->parent[$member->related_user_id])) {
                $this->union($member->id, $member->related_user_id);
            }
        }

        $componentsByRoot = $members->groupBy(fn ($member) => $this->find($member->id));

        return $componentsByRoot->map(function (Collection $componentMembers) use ($group) {
            return $this->syncCluster($group, $componentMembers);
        })->values();
    }

    private function stampRelationScores(Collection $members): void
    {
        $rules = RelationshipRule::pluck('score', 'relation_type');

        foreach ($members as $member) {
            $score = $rules->get($member->relation_type, 0);

            if ($member->relation_score !== $score) {
                $member->update(['relation_score' => $score]);
            }
        }
    }

    private function syncCluster(Group $group, Collection $componentMembers): FamilyCluster
    {
        $existingClusterId = $componentMembers->pluck('cluster_id')->filter()->first();

        $attributes = [
            'group_id' => $group->id,
            'cluster_name' => $this->deriveClusterName($componentMembers),
            'cluster_size' => $componentMembers->count(),
            'cluster_score' => (int) $componentMembers->sum('relation_score'),
            'allocation_status' => 'pending',
        ];

        if ($existingClusterId) {
            $cluster = FamilyCluster::findOrFail($existingClusterId);
            $cluster->update($attributes);
        } else {
            $cluster = FamilyCluster::create($attributes);
        }

        foreach ($componentMembers as $member) {
            if ($member->cluster_id !== $cluster->id) {
                $member->update(['cluster_id' => $cluster->id]);
            }
        }

        return $cluster;
    }

    private function deriveClusterName(Collection $componentMembers): string
    {
        if ($componentMembers->count() === 1) {
            return $componentMembers->first()->user->name.' (Individual)';
        }

        $leader = $componentMembers->firstWhere('is_leader', true) ?? $componentMembers->first();

        return ($leader->user->last_name ?? $leader->user->name).' Family';
    }

    private function find(int $id): int
    {
        while ($this->parent[$id] !== $id) {
            $this->parent[$id] = $this->parent[$this->parent[$id]];
            $id = $this->parent[$id];
        }

        return $id;
    }

    private function union(int $a, int $b): void
    {
        $rootA = $this->find($a);
        $rootB = $this->find($b);

        if ($rootA !== $rootB) {
            $this->parent[$rootA] = $rootB;
        }
    }
}
