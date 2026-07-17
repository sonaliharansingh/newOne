<div>
    @if (session('status'))
        <div class="status-message">{{ session('status') }}</div>
    @endif

    <div class="panel">
        <div class="panel-body">
            <h2 style="margin-bottom: 0.5rem;">Merge Groups</h2>
            <p class="text-muted" style="margin-bottom: 1rem;">
                Use this when a spouse or family member registered as a separate group by mistake.
                The source group's members move into the target group; the source group is then removed.
                Re-run allocation afterward, and declare any new relations between the merged members if needed.
            </p>

            <div class="grid-2">
                <div class="field">
                    <x-input-label value="Source Group (will be merged away)" />
                    <select wire:model="sourceGroupId">
                        <option value="">Select a group</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->group_name }} ({{ $group->members_count }} members)</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('sourceGroupId')" />
                </div>

                <div class="field">
                    <x-input-label value="Target Group (keeps this identity)" />
                    <select wire:model="targetGroupId">
                        <option value="">Select a group</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->group_name }} ({{ $group->members_count }} members)</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('targetGroupId')" />
                </div>
            </div>

            <button type="button" class="btn btn-primary" wire:click="merge" wire:confirm="Merge these two groups? This cannot be undone.">Merge Groups</button>
        </div>
    </div>
</div>
