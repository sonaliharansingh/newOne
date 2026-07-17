<div>
    <div class="panel">
        <div class="panel-body">
            <div class="grid-2" style="margin-bottom: 1rem;">
                <div class="field">
                    <x-input-label value="Search by group name" />
                    <input type="text" wire:model.live.debounce.300ms="search">
                </div>

                <div class="field">
                    <x-input-label value="Status" />
                    <select wire:model.live="statusFilter">
                        <option value="">All</option>
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                        <option value="allocated">Allocated</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid var(--color-border);">
                            <th style="padding: 0.5rem;">Booking ID</th>
                            <th style="padding: 0.5rem;">Group Name</th>
                            <th style="padding: 0.5rem;">Leader</th>
                            <th style="padding: 0.5rem;">Members</th>
                            <th style="padding: 0.5rem;">Status</th>
                            <th style="padding: 0.5rem;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($groups as $group)
                            <tr style="border-bottom: 1px solid var(--color-border);">
                                <td style="padding: 0.5rem;">{{ $group->bookingId() }}</td>
                                <td style="padding: 0.5rem;">{{ $group->group_name }}</td>
                                <td style="padding: 0.5rem;">{{ $group->leader->name ?? '—' }}</td>
                                <td style="padding: 0.5rem;">{{ $group->members_count }}</td>
                                <td style="padding: 0.5rem;">
                                    <span class="badge {{ $group->status === 'allocated' ? 'badge-success' : 'badge-muted' }}">{{ $group->status }}</span>
                                </td>
                                <td style="padding: 0.5rem;">
                                    <a href="{{ route('admin.registrations.show', $group) }}" class="btn btn-secondary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 1rem; text-align: center;" class="text-muted">No registrations found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 1rem;">
                {{ $groups->links() }}
            </div>
        </div>
    </div>
</div>
