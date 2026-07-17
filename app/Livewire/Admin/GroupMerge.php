<?php

namespace App\Livewire\Admin;

use App\Models\Group;
use App\Services\GroupMergeService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class GroupMerge extends Component
{
    public ?int $sourceGroupId = null;

    public ?int $targetGroupId = null;

    public function merge(): void
    {
        $this->validate([
            'sourceGroupId' => 'required|exists:groups,id|different:targetGroupId',
            'targetGroupId' => 'required|exists:groups,id',
        ]);

        try {
            app(GroupMergeService::class)->merge(
                Group::findOrFail($this->sourceGroupId),
                Group::findOrFail($this->targetGroupId)
            );
        } catch (ValidationException $e) {
            $this->addError('sourceGroupId', $e->getMessage());

            return;
        }

        session()->flash('status', 'Groups merged. Re-run allocation on the target group to reflect the change.');

        $this->sourceGroupId = null;
        $this->targetGroupId = null;
    }

    public function render()
    {
        $groups = Group::whereIn('status', ['open', 'closed'])
            ->withCount('members')
            ->orderBy('group_name')
            ->get();

        return view('livewire.admin.group-merge', ['groups' => $groups]);
    }
}
