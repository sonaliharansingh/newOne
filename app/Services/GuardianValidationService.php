<?php

namespace App\Services;

use App\Models\AdminFlag;
use App\Models\AuditLog;
use App\Models\FamilyCluster;
use App\Models\GroupMember;
use App\Models\RelationshipRule;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Enforces the minor-guardian hard constraint per cluster: any member under 15 needs an
 * adult female in the cluster, and any male aged 15-17 needs an adult male in the cluster.
 * Females aged 15-17 are treated as adults and never flagged. Missing guardians are written
 * to admin_flags and block the cluster's allocation until resolved.
 *
 * Exception: a female minor whose only eligible guardian in the cluster is male (e.g. she is
 * travelling with her father only, no adult female present) is not blocked — she's allowed
 * through so the allocator can place her privately with that guardian (see
 * PriorityAllocationEngine's hasSoloMaleGuardianException usage), and the exception is logged
 * to AuditLog for admin visibility instead of sitting in the guardian-flag queue.
 */
class GuardianValidationService
{
    public function validate(FamilyCluster $cluster): bool
    {
        $members = $cluster->members()->with('user')->get();
        $guardianAllowedByRelation = RelationshipRule::pluck('guardian_allowed', 'relation_type');

        $isValid = true;

        foreach ($members as $member) {
            if ($member->isMinorRequiringFemaleGuardian()) {
                $hasFemaleGuardian = $this->findEligibleGuardian($members, $member, 'female', $guardianAllowedByRelation) !== null;

                if (! $hasFemaleGuardian
                    && $member->user->gender === 'female'
                    && $this->findEligibleGuardian($members, $member, 'male', $guardianAllowedByRelation) !== null) {
                    $this->syncFlag($cluster, $member, 'missing_female_guardian', false);
                    $this->logSoloGuardianException($cluster, $member);
                } else {
                    $isValid = $this->syncFlag($cluster, $member, 'missing_female_guardian', ! $hasFemaleGuardian) && $isValid;
                }
            }

            if ($member->isMinorRequiringMaleGuardian()) {
                $hasGuardian = $this->findEligibleGuardian($members, $member, 'male', $guardianAllowedByRelation) !== null;
                $isValid = $this->syncFlag($cluster, $member, 'missing_male_guardian', ! $hasGuardian) && $isValid;
            }
        }

        return $isValid;
    }

    /**
     * True when the cluster contains a female minor whose only eligible guardian present is
     * male — the allocator must place them together in a private room (never pooled/split),
     * per the same rule validate() uses to allow this case through instead of blocking it.
     *
     * @param  Collection<int, GroupMember>  $members
     */
    public function hasSoloMaleGuardianException(Collection $members): bool
    {
        $guardianAllowedByRelation = RelationshipRule::pluck('guardian_allowed', 'relation_type');

        return $members->contains(function (GroupMember $member) use ($members, $guardianAllowedByRelation) {
            return $member->isMinorRequiringFemaleGuardian()
                && $member->user->gender === 'female'
                && $this->findEligibleGuardian($members, $member, 'female', $guardianAllowedByRelation) === null
                && $this->findEligibleGuardian($members, $member, 'male', $guardianAllowedByRelation) !== null;
        });
    }

    /**
     * Finds an adult member of the given gender, other than the minor, whose relation type is
     * marked guardian_allowed in relationship_rules.
     *
     * @param  Collection<int, GroupMember>  $members
     */
    public function findEligibleGuardian(Collection $members, GroupMember $minor, string $gender, ?Collection $guardianAllowedByRelation = null): ?GroupMember
    {
        $guardianAllowedByRelation ??= RelationshipRule::pluck('guardian_allowed', 'relation_type');

        return $members->first(function (GroupMember $candidate) use ($minor, $gender, $guardianAllowedByRelation) {
            return $candidate->id !== $minor->id
                && $candidate->user->gender === $gender
                && $guardianAllowedByRelation->get($candidate->relation_type, false)
                && $this->isAdult($candidate->user);
        });
    }

    private function logSoloGuardianException(FamilyCluster $cluster, GroupMember $member): void
    {
        AuditLog::create([
            'user_id' => null,
            'module' => 'guardian_validation',
            'action' => 'solo_male_guardian_exception',
            'reference_id' => $member->id,
            'description' => "{$member->user->name} has no adult female guardian in cluster #{$cluster->id} — auto-approved for a private room with her male guardian instead of being blocked.",
        ]);
    }

    private function isAdult(User $user): bool
    {
        if ($user->age === null) {
            return false;
        }

        // Female 15-17 is treated as adult; male reaches adult guardian eligibility at 18.
        return $user->gender === 'female' ? $user->age >= 15 : $user->age >= 18;
    }

    /**
     * Creates/keeps an open flag when a guardian is missing, and auto-resolves a flag once a
     * guardian becomes present. Returns whether the cluster should be treated as valid for
     * this member — a flag an admin has already resolved is a deliberate override, so it does
     * not re-block the cluster on a later re-run even though the underlying condition persists.
     */
    private function syncFlag(FamilyCluster $cluster, GroupMember $member, string $flagType, bool $shouldFlag): bool
    {
        $existing = AdminFlag::where('member_id', $member->id)
            ->where('flag_type', $flagType)
            ->first();

        if (! $shouldFlag) {
            if ($existing && $existing->status === 'open') {
                $existing->update([
                    'status' => 'resolved',
                    'resolution_notes' => 'Auto-resolved: eligible guardian present in cluster.',
                ]);
            }

            return true;
        }

        if ($existing && $existing->status === 'resolved') {
            return true;
        }

        if (! $existing) {
            AdminFlag::create([
                'group_id' => $cluster->group_id,
                'cluster_id' => $cluster->id,
                'member_id' => $member->id,
                'flag_type' => $flagType,
                'status' => 'open',
            ]);
        }

        return false;
    }
}
