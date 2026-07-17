<?php

namespace App\Livewire\Admin;

use App\Models\AdminFlag;
use App\Models\AuditLog;
use Livewire\Component;
use Livewire\WithPagination;

class GuardianFlagQueue extends Component
{
    use WithPagination;

    public ?int $resolvingFlagId = null;

    public string $resolutionNotes = '';

    public function startResolve(int $flagId): void
    {
        $this->resolvingFlagId = $flagId;
        $this->resolutionNotes = '';
    }

    public function cancelResolve(): void
    {
        $this->resolvingFlagId = null;
    }

    public function resolve(): void
    {
        $this->validate([
            'resolutionNotes' => 'required|string|max:500',
        ]);

        $flag = AdminFlag::findOrFail($this->resolvingFlagId);
        $flag->update([
            'status' => 'resolved',
            'resolved_by' => auth()->id(),
            'resolution_notes' => $this->resolutionNotes,
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'module' => 'guardian_flag',
            'action' => 'flag_resolved',
            'reference_id' => $flag->id,
            'description' => "Resolved {$flag->flag_type} flag for member #{$flag->member_id} — {$this->resolutionNotes}",
        ]);

        $this->resolvingFlagId = null;
        session()->flash('status', 'Guardian flag resolved.');
    }

    public function render()
    {
        $flags = AdminFlag::with(['group', 'member.user', 'cluster'])
            ->where('status', 'open')
            ->latest()
            ->paginate(15);

        return view('livewire.admin.guardian-flag-queue', ['flags' => $flags]);
    }
}
