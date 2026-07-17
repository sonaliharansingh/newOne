<?php

namespace App\Livewire\Admin;

use App\Models\AdminFlag;
use App\Models\Allocation;
use App\Models\AllocationLog;
use App\Models\AuditLog;
use App\Models\FamilyCluster;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Room;
use Illuminate\Support\Collection;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        return view('livewire.admin.dashboard', [
            'groupStats' => $this->groupStats(),
            'roomStats' => $this->roomStats(),
            'pilgrimCount' => GroupMember::count(),
            'openFlagCount' => AdminFlag::where('status', 'open')->count(),
            'unallocatedClusterCount' => $this->unallocatedClustersQuery()->count(),
            'freeStayCount' => Allocation::where('free_stay', true)->count(),
            'openFlags' => AdminFlag::with(['group', 'member.user', 'cluster'])
                ->where('status', 'open')
                ->latest()
                ->limit(6)
                ->get(),
            'unallocatedClusters' => $this->unallocatedClustersQuery()
                ->with('group')
                ->latest()
                ->limit(6)
                ->get(),
            'activity' => $this->recentActivity(),
        ]);
    }

    private function groupStats(): array
    {
        $counts = Group::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'open' => $counts->get('open', 0),
            'closed' => $counts->get('closed', 0),
            'allocated' => $counts->get('allocated', 0),
            'cancelled' => $counts->get('cancelled', 0),
            'total' => $counts->sum(),
        ];
    }

    private function roomStats(): array
    {
        $counts = Room::query()
            ->selectRaw('room_status, count(*) as total')
            ->groupBy('room_status')
            ->pluck('total', 'room_status');

        $capacity = (int) Room::sum('capacity');
        $available = (int) Room::where('room_status', '!=', 'maintenance')->sum('available_count');
        $booked = $capacity > 0 ? round((($capacity - $available) / $capacity) * 100) : 0;

        return [
            'available' => $counts->get('available', 0),
            'partial' => $counts->get('partial', 0),
            'full' => $counts->get('full', 0),
            'maintenance' => $counts->get('maintenance', 0),
            'bookedPercent' => $booked,
        ];
    }

    private function unallocatedClustersQuery()
    {
        return FamilyCluster::query()
            ->where('allocation_status', 'pending')
            ->whereHas('group', fn ($query) => $query->where('status', '!=', 'open'));
    }

    /**
     * @return Collection<int, array{type: string, description: string, created_at: \Illuminate\Support\Carbon}>
     */
    private function recentActivity(): Collection
    {
        $auditEntries = AuditLog::latest()->limit(10)->get()->map(fn (AuditLog $log) => [
            'type' => $log->module,
            'description' => $log->description,
            'created_at' => $log->created_at,
        ]);

        $overrideEntries = AllocationLog::with(['changedBy', 'oldRoom', 'newRoom'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (AllocationLog $log) => [
                'type' => 'room_override',
                'description' => sprintf(
                    '%s moved allocation #%d: room %s → %s%s',
                    $log->changedBy->name ?? 'System',
                    $log->allocation_id,
                    $log->oldRoom->room_number ?? '—',
                    $log->newRoom->room_number ?? '—',
                    $log->reason ? " ({$log->reason})" : ''
                ),
                'created_at' => $log->created_at,
            ]);

        return $auditEntries->concat($overrideEntries)
            ->sortByDesc('created_at')
            ->values()
            ->take(12);
    }
}
