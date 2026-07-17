<div class="stack">
    {{-- Stat tiles --}}
    <div class="dashboard-stat-grid">
        <div class="dashboard-stat-tile">
            <span class="dashboard-stat-value">{{ $groupStats['total'] }}</span>
            <span class="dashboard-stat-label">Total Groups</span>
            <span class="text-muted">{{ $groupStats['open'] }} open &middot; {{ $groupStats['closed'] }} closed &middot; {{ $groupStats['allocated'] }} allocated</span>
        </div>
        <div class="dashboard-stat-tile">
            <span class="dashboard-stat-value">{{ $pilgrimCount }}</span>
            <span class="dashboard-stat-label">Pilgrims Registered</span>
        </div>
        <div class="dashboard-stat-tile">
            <span class="dashboard-stat-value">{{ $roomStats['bookedPercent'] }}%</span>
            <span class="dashboard-stat-label">Rooms Booked</span>
            <span class="text-muted">{{ $roomStats['available'] }} available &middot; {{ $roomStats['partial'] }} partial &middot; {{ $roomStats['full'] }} full &middot; {{ $roomStats['maintenance'] }} maintenance</span>
        </div>
        <div class="dashboard-stat-tile {{ $openFlagCount > 0 ? 'is-alert' : '' }}">
            <span class="dashboard-stat-value">{{ $openFlagCount }}</span>
            <span class="dashboard-stat-label">Open Guardian Flags</span>
        </div>
        <div class="dashboard-stat-tile {{ $unallocatedClusterCount > 0 ? 'is-alert' : '' }}">
            <span class="dashboard-stat-value">{{ $unallocatedClusterCount }}</span>
            <span class="dashboard-stat-label">Unallocated Clusters</span>
        </div>
        <div class="dashboard-stat-tile">
            <span class="dashboard-stat-value">{{ $freeStayCount }}</span>
            <span class="dashboard-stat-label">Free Stays (under 12)</span>
        </div>
    </div>

    <div class="grid-2">
        {{-- Needs attention --}}
        <div class="panel">
            <div class="panel-body">
                <h2 style="margin-bottom: 1rem;">Needs Attention</h2>

                @if ($openFlags->isEmpty() && $unallocatedClusters->isEmpty())
                    <p class="text-muted">Nothing needs review right now.</p>
                @else
                    <ul class="dashboard-attention-list">
                        @foreach ($openFlags as $flag)
                            <li>
                                <span class="badge badge-danger">Guardian</span>
                                <a href="{{ route('admin.registrations.show', $flag->group_id) }}" class="inline-link">
                                    {{ $flag->member?->user?->name ?? 'Member' }} — {{ str_replace('_', ' ', $flag->flag_type) }}
                                </a>
                            </li>
                        @endforeach

                        @foreach ($unallocatedClusters as $cluster)
                            <li>
                                <span class="badge badge-muted">Unplaced</span>
                                <a href="{{ route('admin.registrations.show', $cluster->group_id) }}" class="inline-link">
                                    {{ $cluster->cluster_name }} ({{ $cluster->group->group_name ?? 'Group' }})
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <a href="{{ route('admin.guardian-flags') }}" class="inline-link">View all guardian flags &rarr;</a>
                @endif
            </div>
        </div>

        {{-- Recent activity --}}
        <div class="panel">
            <div class="panel-body">
                <h2 style="margin-bottom: 1rem;">Recent Activity</h2>

                @if ($activity->isEmpty())
                    <p class="text-muted">No activity recorded yet.</p>
                @else
                    <ul class="dashboard-activity-list">
                        @foreach ($activity as $entry)
                            <li>
                                <span class="badge badge-muted">{{ str_replace('_', ' ', $entry['type']) }}</span>
                                <span>{{ $entry['description'] }}</span>
                                <span class="text-muted">{{ $entry['created_at']?->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
