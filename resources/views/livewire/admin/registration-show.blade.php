<div>
    @if (session('status'))
        <div class="status-message">{{ session('status') }}</div>
    @endif

    <div class="panel" style="margin-bottom: 1.5rem;">
        <div class="panel-body">
            <div class="member-row-header">
                <div>
                    <span class="member-row-title">{{ $group->group_name }}</span>
                    <span class="badge {{ $group->status === 'allocated' ? 'badge-success' : 'badge-muted' }}">{{ $group->status }}</span>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" wire:click="runAllocation" wire:loading.attr="disabled">Re-run Allocation</button>
                    @if ($group->status !== 'allocated')
                        <button type="button" class="btn btn-primary" wire:click="confirmAndAllocate" wire:loading.attr="disabled">Confirm &amp; Finalize</button>
                    @endif
                </div>
            </div>
            <p class="text-muted">Booking ID: {{ $group->bookingId() }} — Leader: {{ $group->leader->name ?? '—' }}</p>
        </div>
    </div>

    @foreach ($previewResults as $result)
        @php $cluster = $result['cluster']; @endphp
        <div class="cluster-card {{ $result['status'] === 'blocked' ? 'is-blocked' : ($result['status'] === 'allocated' ? 'is-allocated' : '') }}">
            <div class="member-row-header">
                <span class="member-row-title">{{ $cluster->cluster_name }}</span>
                @if ($result['status'] === 'allocated')
                    <span class="badge badge-success">Allocated</span>
                @elseif ($result['status'] === 'blocked')
                    <span class="badge badge-danger">Needs Admin Review</span>
                @else
                    <span class="badge badge-muted">Awaiting Room</span>
                @endif
            </div>

            @if ($result['reason'])
                <p class="text-muted">
                    {{ $result['reason'] }}
                    <a href="{{ route('admin.guardian-flags') }}" class="inline-link">Resolve in guardian queue</a>
                </p>
            @endif

            <ul class="member-list">
                @foreach ($cluster->members as $member)
                    @php $allocation = $cluster->allocations->firstWhere('user_id', $member->user_id); @endphp
                    <li style="display: flex; align-items: center; justify-content: space-between;">
                        <span>
                            {{ $member->user->name }} ({{ $member->relation_type }})
                            @if ($allocation)
                                — Room {{ $allocation->room->room_number }}
                                @if ($allocation->free_stay)
                                    <span class="badge badge-muted">Free stay (under 12)</span>
                                @endif
                            @endif
                        </span>

                        @if ($allocation)
                            <button type="button" class="btn btn-secondary" wire:click="startOverride({{ $allocation->id }})">Override Room</button>
                        @endif
                    </li>

                    @if ($allocation && $overridingAllocationId === $allocation->id)
                        <li>
                            <div class="grid-2" style="margin-top: 0.5rem;">
                                <div class="field">
                                    <x-input-label value="New Room" />
                                    <select wire:model="selectedRoomId">
                                        <option value="">Select a room</option>
                                        @foreach ($this->availableRooms() as $room)
                                            <option value="{{ $room->id }}">
                                                {{ $room->room_number }} ({{ $room->room_type }}, {{ $room->available_count }} beds free)
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('selectedRoomId')" />
                                </div>
                                <div class="field">
                                    <x-input-label value="Reason" />
                                    <input type="text" wire:model="overrideReason" placeholder="Optional reason">
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-primary" wire:click="saveOverride">Save</button>
                                <button type="button" class="btn btn-secondary" wire:click="cancelOverride">Cancel</button>
                            </div>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    @endforeach

    <a href="{{ route('admin.registrations.index') }}" class="inline-link">&larr; Back to registrations</a>
</div>
