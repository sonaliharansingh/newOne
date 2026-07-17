<?php

namespace App\Livewire\Admin;

use App\Models\Group;
use Livewire\Component;
use Livewire\WithPagination;

class RegistrationsIndex extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public string $search = '';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $groups = Group::query()
            ->withCount('members')
            ->with('leader')
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->search, fn ($query) => $query->where('group_name', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.registrations-index', ['groups' => $groups]);
    }
}
