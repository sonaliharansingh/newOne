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
 * Priority 4: the universal last-resort fallback for any cluster that couldn't fit a single
 * room or a two-way gender split (Priority 3) — not just declared Friend groups. Members are
 * first bundled into "guardian units" (a minor stays bundled with whichever eligible guardian
 * the earlier validation already confirmed for them, so a unit is never split across rooms),
 * then units are bucketed by gender:age-band:city ("same locality and same age/gender") and
 * packed into whatever shared rooms — including dormitories — are available, spilling across
 * multiple rooms if a bucket is larger than any single room's capacity.
 *
 * The one case this strategy refuses is GuardianValidationService::hasSoloMaleGuardianException
 * (a female minor whose only guardian is male) — that case is private-room-only all the way
 * down the waterfall and must never be pooled into a shared/dormitory bucket.
 */
class GenderAgeSplitStrategy extends AbstractAllocationStrategy
{
    public function __construct(private GuardianValidationService $guardianValidation) {}

    protected function priorityLevel(): int
    {
        return 4;
    }

    protected function label(): string
    {
        return 'Priority 4: Gender/age/locality split';
    }

    public function attempt(FamilyCluster $cluster, Collection $members, Group $group): bool
    {
        if ($members->count() < 2) {
            return false;
        }

        if ($this->guardianValidation->hasSoloMaleGuardianException($members)) {
            return false;
        }

        $units = $this->buildUnits($members);

        $buckets = $units->groupBy(function (array $unit) {
            $representative = $unit['representative'];

            return $representative->user->gender.':'.$this->ageBand($representative->user->age).':'.($representative->user->city ?: 'unspecified');
        });

        $plan = $this->buildPlan($buckets);

        if ($plan === null) {
            return false;
        }

        foreach ($plan as [$bucketMembers, $room]) {
            $this->allocateMembersToRoom($bucketMembers, $room, $cluster, $group);
        }

        return true;
    }

    protected function ageBand(?int $age): string
    {
        return match (true) {
            $age === null => 'adult',
            $age >= 60 => 'senior',
            $age >= 25 => 'adult',
            default => 'youth',
        };
    }

    /**
     * Bundles each minor requiring a guardian with that eligible guardian (found the same way
     * GuardianValidationService already validated), so the pair/group is always packed into a
     * room together. Every other member becomes their own singleton unit.
     *
     * @param  Collection<int, GroupMember>  $members
     * @return Collection<int, array{representative: GroupMember, members: Collection<int, GroupMember>}>
     */
    private function buildUnits(Collection $members): Collection
    {
        $claimedBy = [];

        foreach ($members as $member) {
            $requiredGender = match (true) {
                $member->isMinorRequiringFemaleGuardian() => 'female',
                $member->isMinorRequiringMaleGuardian() => 'male',
                default => null,
            };

            if ($requiredGender === null) {
                continue;
            }

            $guardian = $this->guardianValidation->findEligibleGuardian($members, $member, $requiredGender);

            if ($guardian) {
                $claimedBy[$member->id] = $guardian->id;
            }
        }

        return $members
            ->groupBy(fn (GroupMember $member) => $claimedBy[$member->id] ?? $member->id)
            ->map(fn (Collection $unitMembers, $repId) => [
                'representative' => $members->firstWhere('id', $repId),
                'members' => $unitMembers->values(),
            ])
            ->values();
    }

    /**
     * Packs whole units (never split across the room-capacity boundary) into rooms per bucket.
     *
     * @return array<int, array{0: Collection<int, GroupMember>, 1: Room}>|null
     */
    private function buildPlan(Collection $buckets): ?array
    {
        $reserved = [];
        $plan = [];

        foreach ($buckets as $key => $unitsInBucket) {
            $remainingUnits = $unitsInBucket->values();
            $womenOnly = str_starts_with($key, 'female') ? null : false;
            $preferElderly = str_contains($key, ':senior:');

            while ($remainingUnits->isNotEmpty()) {
                $room = $this->findAvailableRoomForPlan($womenOnly, $reserved, $preferElderly);

                if (! $room) {
                    return null;
                }

                $capacityLeft = $room->available_count - ($reserved[$room->id] ?? 0);
                $roomMembers = collect();
                $keepIndices = [];

                foreach ($remainingUnits as $idx => $unit) {
                    $unitMembers = $unit['members'];

                    if ($unitMembers->count() <= $capacityLeft - $roomMembers->count()) {
                        $roomMembers = $roomMembers->merge($unitMembers);
                    } else {
                        $keepIndices[] = $idx;
                    }
                }

                if ($roomMembers->isEmpty()) {
                    // The smallest remaining unit doesn't fit even this room — nothing more to try.
                    return null;
                }

                $reserved[$room->id] = ($reserved[$room->id] ?? 0) + $roomMembers->count();
                $plan[] = [$roomMembers, $room];
                $remainingUnits = $remainingUnits->only($keepIndices)->values();
            }
        }

        return $plan;
    }

    private function findAvailableRoomForPlan(?bool $womenOnly, array $reserved, bool $preferElderly = false): ?Room
    {
        $rooms = Room::query()
            ->where('room_status', '!=', 'maintenance')
            ->when($womenOnly !== null, fn ($query) => $query->where('women_only', $womenOnly))
            ->when($preferElderly, fn ($query) => $query->orderByDesc('elderly_friendly')->orderByDesc('lift_access'))
            ->orderBy('is_private')
            ->orderByDesc('available_count')
            ->get();

        foreach ($rooms as $room) {
            $effectiveAvailable = $room->available_count - ($reserved[$room->id] ?? 0);

            if ($effectiveAvailable > 0) {
                return $room;
            }
        }

        return null;
    }
}
