<div>
    @if (session('status'))
        <div class="status-message">{{ session('status') }}</div>
    @endif

    <div class="panel">
        <div class="panel-body">
            <h2 style="margin-bottom: 1rem;">Guardian Flag Queue</h2>

            @forelse ($flags as $flag)
                <div class="cluster-card is-blocked" wire:key="flag-{{ $flag->id }}">
                    <div class="member-row-header">
                        <span class="member-row-title">
                            {{ $flag->member->user->name }}
                            <span class="badge badge-danger">{{ str_replace('_', ' ', $flag->flag_type) }}</span>
                        </span>
                        <a href="{{ route('admin.registrations.show', $flag->group_id) }}" class="inline-link">View group</a>
                    </div>

                    <p class="text-muted">
                        Group: {{ $flag->group->group_name }}
                        @if ($flag->cluster) — Cluster: {{ $flag->cluster->cluster_name }} @endif
                    </p>

                    @if ($resolvingFlagId === $flag->id)
                        <div class="field">
                            <x-input-label value="Resolution Notes" />
                            <textarea wire:model="resolutionNotes" rows="2" placeholder="Explain how this was resolved (e.g. guardian confirmed in person, chaperone arranged)"></textarea>
                            <x-input-error :messages="$errors->get('resolutionNotes')" />
                        </div>
                        <div class="form-actions">
                            <button type="button" class="btn btn-primary" wire:click="resolve">Confirm Resolution</button>
                            <button type="button" class="btn btn-secondary" wire:click="cancelResolve">Cancel</button>
                        </div>
                    @else
                        <button type="button" class="btn btn-primary" wire:click="startResolve({{ $flag->id }})">Resolve</button>
                    @endif
                </div>
            @empty
                <p class="text-muted">No open guardian flags.</p>
            @endforelse

            <div style="margin-top: 1rem;">
                {{ $flags->links() }}
            </div>
        </div>
    </div>
</div>
