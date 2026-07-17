<?php

namespace App\Livewire;

use App\Models\Group;
use App\Models\GroupMember;
use App\Models\RelationshipRule;
use App\Models\User;
use App\Services\GroupSubmissionPipeline;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;

class GroupRegistrationWizard extends Component
{
    public int $step = 1;

    public ?int $groupId = null;

    // Step 1 — personal details for the authenticated pilgrim.
    public string $first_name = '';

    public string $last_name = '';

    public string $date_of_birth = '';

    public string $language = '';

    public string $passport_number = '';

    public string $father_name = '';

    public string $mother_name = '';

    public string $phone = '';

    public string $city = '';

    public string $state = '';

    public string $country = '';

    public string $address = '';

    public string $gender = '';

    public string $adhar_id = '';

    public int $luggage_count = 0;

    // Step 2 — trip details, solo vs group.
    public string $trip_start_date = '';

    public string $trip_end_date = '';

    public string $registrationType = 'solo';

    public string $groupName = '';

    public int $expected_member_count = 2;

    // Step 3 — repeatable group members. Each row:
    // first_name, last_name, date_of_birth, gender, relation_type, related_index,
    // user_id (set once persisted), group_member_id (set once persisted).
    public array $members = [];

    // Step 4 — preview results from the priority allocation engine.
    public array $previewResults = [];

    // Step 5 — confirmation.
    public ?string $bookingId = null;

    public function mount(): void
    {
        $user = auth()->user();

        $this->first_name = $user->first_name ?? '';
        $this->last_name = $user->last_name ?? '';
        $this->date_of_birth = $user->date_of_birth?->format('Y-m-d') ?? '';
        $this->language = $user->language ?? '';
        $this->passport_number = $user->passport_number ?? '';
        $this->father_name = $user->father_name ?? '';
        $this->mother_name = $user->mother_name ?? '';
        $this->phone = $user->phone ?? '';
        $this->city = $user->city ?? '';
        $this->state = $user->state ?? '';
        $this->country = $user->country ?? '';
        $this->address = $user->address ?? '';
        $this->gender = $user->gender ?? '';
        $this->adhar_id = $user->adhar_id ?? '';
        $this->luggage_count = $user->luggage_count ?? 0;
    }

    public function relationOptions(): array
    {
        return RelationshipRule::where('active', true)
            ->where('relation_type', '!=', 'Self')
            ->pluck('relation_type')
            ->all();
    }

    public function addMember(): void
    {
        $this->members[] = [
            'first_name' => '',
            'last_name' => '',
            'date_of_birth' => '',
            'gender' => '',
            'relation_type' => '',
            'related_index' => null,
            'user_id' => null,
            'group_member_id' => null,
        ];
    }

    public function removeMember(int $index): void
    {
        unset($this->members[$index]);
        $this->members = array_values($this->members);

        // Any row that referenced the removed index (or a shifted one) needs to be cleared
        // to avoid pointing at the wrong row after re-indexing.
        foreach ($this->members as $i => $member) {
            if (($this->members[$i]['related_index'] ?? null) === $index) {
                $this->members[$i]['related_index'] = null;
            }
        }
    }

    public function goToStep1(): void
    {
        $this->step = 1;
    }

    public function proceedToStep2(): void
    {
        $this->validate([
            'first_name' => 'required|string|max:150',
            'last_name' => 'required|string|max:150',
            'date_of_birth' => 'required|date|before:today',
            'language' => 'nullable|string|max:50',
            'passport_number' => 'nullable|string|max:50',
            'father_name' => 'nullable|string|max:150',
            'mother_name' => 'nullable|string|max:150',
            'phone' => 'required|string|max:20',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'gender' => 'required|in:male,female,other',
            'adhar_id' => 'nullable|digits:12',
            'luggage_count' => 'nullable|integer|min:0',
        ]);

        $this->step = 2;
    }

    public function proceedToStep3(): void
    {
        $this->validate([
            'trip_start_date' => 'required|date',
            'trip_end_date' => 'required|date|after_or_equal:trip_start_date',
            'registrationType' => 'required|in:solo,group',
            'groupName' => 'required_if:registrationType,group|string|max:150',
            'expected_member_count' => 'required_if:registrationType,group|integer|min:2|max:20',
        ]);

        if ($this->registrationType === 'solo') {
            $this->syncMembersToDatabase();
            $this->runPreview();

            return;
        }

        // The leader takes one seat of the expected headcount, so pre-seed the rest as
        // blank rows to match what was declared during trip planning.
        $memberRowsNeeded = $this->expected_member_count - 1;

        while (count($this->members) < $memberRowsNeeded) {
            $this->addMember();
        }

        $this->step = 3;
    }

    public function proceedToStep4(): void
    {
        $this->validate([
            'members.*.first_name' => 'required|string|max:150',
            'members.*.last_name' => 'required|string|max:150',
            'members.*.date_of_birth' => 'required|date|before:today',
            'members.*.gender' => 'required|in:male,female,other',
            'members.*.relation_type' => 'required|in:'.implode(',', $this->relationOptions()),
        ]);

        $this->syncMembersToDatabase();
        $this->runPreview();
    }

    public function backToStep3(): void
    {
        $this->step = 3;
    }

    public function confirmSubmission(): void
    {
        $group = Group::findOrFail($this->groupId);
        $group->update(['status' => 'closed']);

        $this->bookingId = $group->bookingId();
        $this->step = 5;
    }

    private function runPreview(): void
    {
        $group = Group::findOrFail($this->groupId);

        $this->previewResults = app(GroupSubmissionPipeline::class)->preview($group);
        $this->step = 4;
    }

    private function syncMembersToDatabase(): void
    {
        DB::transaction(function () {
            $user = auth()->user();

            $user->update([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'name' => trim("{$this->first_name} {$this->last_name}"),
                'date_of_birth' => $this->date_of_birth,
                'language' => $this->language ?: null,
                'passport_number' => $this->passport_number ?: null,
                'father_name' => $this->father_name ?: null,
                'mother_name' => $this->mother_name ?: null,
                'phone' => $this->phone,
                'city' => $this->city ?: null,
                'state' => $this->state ?: null,
                'country' => $this->country ?: null,
                'address' => $this->address ?: null,
                'gender' => $this->gender,
                'adhar_id' => $this->adhar_id ?: null,
                'luggage_count' => $this->luggage_count,
                'type' => $this->registrationType,
            ]);

            $memberCount = $this->registrationType === 'group' ? count($this->members) + 1 : 1;

            $groupName = $this->registrationType === 'group'
                ? $this->groupName
                : "{$user->name} (Solo)";

            if ($this->groupId) {
                $group = Group::findOrFail($this->groupId);
                $group->update([
                    'group_name' => $groupName,
                    'trip_start_date' => $this->trip_start_date,
                    'trip_end_date' => $this->trip_end_date,
                    'expected_members' => $memberCount,
                    'joined_members' => $memberCount,
                ]);
            } else {
                $group = Group::create([
                    'group_name' => $groupName,
                    'trip_start_date' => $this->trip_start_date,
                    'trip_end_date' => $this->trip_end_date,
                    'created_by' => $user->id,
                    'expected_members' => $memberCount,
                    'joined_members' => $memberCount,
                    'status' => 'open',
                ]);
                $this->groupId = $group->id;
            }

            $leaderMember = GroupMember::updateOrCreate(
                ['group_id' => $group->id, 'user_id' => $user->id],
                ['is_leader' => true, 'relation_type' => 'Self', 'related_user_id' => null]
            );

            if ($this->registrationType !== 'group') {
                return;
            }

            $survivingGroupMemberIds = collect($this->members)->pluck('group_member_id')->filter()->all();

            GroupMember::where('group_id', $group->id)
                ->where('id', '!=', $leaderMember->id)
                ->when(
                    ! empty($survivingGroupMemberIds),
                    fn ($query) => $query->whereNotIn('id', $survivingGroupMemberIds),
                    fn ($query) => $query
                )
                ->delete();

            $indexToGroupMemberId = [-1 => $leaderMember->id];

            foreach ($this->members as $index => $row) {
                if (! empty($row['user_id'])) {
                    $member = User::findOrFail($row['user_id']);
                    $member->update([
                        'first_name' => $row['first_name'],
                        'last_name' => $row['last_name'],
                        'name' => trim("{$row['first_name']} {$row['last_name']}"),
                        'date_of_birth' => $row['date_of_birth'],
                        'gender' => $row['gender'],
                    ]);
                } else {
                    $member = User::create([
                        'name' => trim("{$row['first_name']} {$row['last_name']}"),
                        'first_name' => $row['first_name'],
                        'last_name' => $row['last_name'],
                        'email' => 'member-'.Str::uuid().'@pending.local',
                        'password' => Hash::make(Str::random(32)),
                        'date_of_birth' => $row['date_of_birth'],
                        'gender' => $row['gender'],
                        'type' => 'group',
                        'role' => 'User',
                    ]);
                    $this->members[$index]['user_id'] = $member->id;
                }

                $relatedGroupMemberId = $row['related_index'] === null || $row['related_index'] === ''
                    ? null
                    : ($indexToGroupMemberId[(int) $row['related_index']] ?? null);

                $groupMember = GroupMember::updateOrCreate(
                    ['group_id' => $group->id, 'user_id' => $member->id],
                    [
                        'is_leader' => false,
                        'relation_type' => $row['relation_type'],
                        'related_user_id' => $relatedGroupMemberId,
                    ]
                );

                $this->members[$index]['group_member_id'] = $groupMember->id;
                $indexToGroupMemberId[$index] = $groupMember->id;
            }
        });
    }

    /**
     * Flattens the per-cluster preview results into one card per room, each holding
     * the members allocated to it — the room is the unit a pilgrim actually cares about.
     *
     * @return \Illuminate\Support\Collection<int, array{room: \App\Models\Room, members: \Illuminate\Support\Collection}>
     */
    public function roomAssignments(): \Illuminate\Support\Collection
    {
        return collect($this->previewResults)
            ->pluck('cluster.allocations')
            ->flatten(1)
            ->filter(fn ($allocation) => $allocation->room)
            ->groupBy('room_id')
            ->map(fn ($allocations) => [
                'room' => $allocations->first()->room,
                'members' => $allocations->pluck('user'),
            ])
            ->sortBy(fn ($assignment) => $assignment['room']->room_number)
            ->values();
    }

    public function render()
    {
        return view('livewire.group-registration-wizard');
    }
}
