<?php

namespace App\Livewire\Admin;

use App\Models\Allocation;
use App\Models\Group;
use App\Models\Room;
use App\Services\GroupSubmissionPipeline;
use App\Services\RoomOverrideService;
use Livewire\Component;

class RegistrationShow extends Component
{
    public int $groupId;

    public array $previewResults = [];

    public ?int $overridingAllocationId = null;

    public ?int $selectedRoomId = null;

    public string $overrideReason = '';

    public function mount(int $groupId): void
    {
        $this->groupId = $groupId;
        $this->loadPreview();
    }

    public function runAllocation(): void
    {
        $this->loadPreview(rerun: true);
        session()->flash('status', 'Allocation engine re-run for this group.');
    }

    public function confirmAndAllocate(): void
    {
        app(GroupSubmissionPipeline::class)->finalize($this->group(), auth()->user());
        $this->loadPreview();
        session()->flash('status', 'Registration confirmed and allocations finalized.');
    }

    public function startOverride(int $allocationId): void
    {
        $this->overridingAllocationId = $allocationId;
        $this->selectedRoomId = null;
        $this->overrideReason = '';
    }

    public function cancelOverride(): void
    {
        $this->overridingAllocationId = null;
    }

    public function saveOverride(): void
    {
        $this->validate([
            'selectedRoomId' => 'required|exists:rooms,id',
            'overrideReason' => 'nullable|string|max:500',
        ]);

        $allocation = Allocation::findOrFail($this->overridingAllocationId);
        $room = Room::findOrFail($this->selectedRoomId);

        app(RoomOverrideService::class)->override($allocation, $room, auth()->user(), $this->overrideReason ?: null);

        $this->overridingAllocationId = null;
        $this->loadPreview();

        session()->flash('status', 'Room override saved.');
    }

    public function availableRooms()
    {
        return Room::where('room_status', '!=', 'maintenance')
            ->where('available_count', '>', 0)
            ->orderBy('hotel_id')
            ->orderBy('room_number')
            ->get();
    }

    private function group(): Group
    {
        return Group::findOrFail($this->groupId);
    }

    private function loadPreview(bool $rerun = false): void
    {
        $group = $this->group();

        if ($rerun) {
            $this->previewResults = app(GroupSubmissionPipeline::class)->preview($group);

            return;
        }

        $group->load(['clusters.members.user', 'clusters.allocations.room', 'clusters.adminFlags']);

        $this->previewResults = $group->clusters->map(function ($cluster) {
            $hasOpenFlag = $cluster->adminFlags->contains('status', 'open');

            return [
                'cluster' => $cluster,
                'status' => $hasOpenFlag ? 'blocked' : ($cluster->allocations->isNotEmpty() ? 'allocated' : 'unallocated'),
                'reason' => $hasOpenFlag ? 'Guardian requirement unmet — awaiting admin resolution.' : null,
            ];
        })->all();
    }

    public function render()
    {
        $group = Group::with('leader')->findOrFail($this->groupId);

        return view('livewire.admin.registration-show', ['group' => $group]);
    }
}
