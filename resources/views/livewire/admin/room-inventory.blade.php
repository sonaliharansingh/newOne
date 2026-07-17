<div>
    @if (session('status'))
        <div class="status-message">{{ session('status') }}</div>
    @endif

    <div class="grid-2">
        {{-- Hotels --}}
        <div class="panel">
            <div class="panel-body">
                <h2 style="margin-bottom: 1rem;">Hotels</h2>

                <ul class="member-list" style="margin-bottom: 1rem;">
                    @foreach ($hotels as $hotel)
                        <li>
                            <button type="button" wire:click="selectHotel({{ $hotel->id }})"
                                class="btn {{ $selectedHotelId === $hotel->id ? 'btn-primary' : 'btn-secondary' }}"
                                style="width: 100%; text-transform: none; justify-content: flex-start;">
                                {{ $hotel->hotel_name }} ({{ $hotel->rooms_count }} rooms)
                            </button>
                        </li>
                    @endforeach
                </ul>

                <div class="field">
                    <x-input-label value="New Hotel Name" />
                    <input type="text" wire:model="newHotelName">
                    <x-input-error :messages="$errors->get('newHotelName')" />
                </div>
                <div class="grid-2">
                    <div class="field">
                        <x-input-label value="City" />
                        <input type="text" wire:model="newHotelCity">
                    </div>
                    <div class="field">
                        <x-input-label value="State" />
                        <input type="text" wire:model="newHotelState">
                    </div>
                </div>
                <button type="button" class="btn btn-secondary" wire:click="addHotel">+ Add Hotel</button>
            </div>
        </div>

        {{-- Floors --}}
        <div class="panel">
            <div class="panel-body">
                <h2 style="margin-bottom: 1rem;">Floors</h2>

                <ul class="member-list" style="margin-bottom: 1rem;">
                    @foreach ($floors as $floor)
                        <li>
                            <button type="button" wire:click="selectFloor({{ $floor->id }})"
                                class="btn {{ $selectedFloorId === $floor->id ? 'btn-primary' : 'btn-secondary' }}"
                                style="width: 100%; text-transform: none; justify-content: flex-start;">
                                Floor {{ $floor->floor_number }}
                                @if ($floor->women_only) <span class="badge badge-muted">Women only</span> @endif
                                @if ($floor->elderly_friendly) <span class="badge badge-muted">Elderly friendly</span> @endif
                            </button>
                        </li>
                    @endforeach
                </ul>

                @if ($selectedHotelId)
                    <div class="field">
                        <x-input-label value="Floor Number" />
                        <input type="number" wire:model="newFloorNumber" min="0">
                        <x-input-error :messages="$errors->get('newFloorNumber')" />
                    </div>
                    <div class="field">
                        <label class="checkbox-label">
                            <input type="checkbox" wire:model="newFloorWomenOnly">
                            <span>Women only</span>
                        </label>
                    </div>
                    <div class="field">
                        <label class="checkbox-label">
                            <input type="checkbox" wire:model="newFloorElderlyFriendly">
                            <span>Elderly friendly</span>
                        </label>
                    </div>
                    <button type="button" class="btn btn-secondary" wire:click="addFloor">+ Add Floor</button>
                @endif
            </div>
        </div>
    </div>

    {{-- Rooms --}}
    @if ($selectedFloorId)
        <div class="panel" style="margin-top: 1.5rem;">
            <div class="panel-body">
                <h2 style="margin-bottom: 1rem;">Rooms on this floor</h2>

                <div style="overflow-x: auto; margin-bottom: 1.5rem;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 1px solid var(--color-border);">
                                <th style="padding: 0.5rem;">Number</th>
                                <th style="padding: 0.5rem;">Type</th>
                                <th style="padding: 0.5rem;">Capacity</th>
                                <th style="padding: 0.5rem;">Available</th>
                                <th style="padding: 0.5rem;">Private</th>
                                <th style="padding: 0.5rem;">Status</th>
                                <th style="padding: 0.5rem;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rooms as $room)
                                <tr style="border-bottom: 1px solid var(--color-border);" wire:key="room-{{ $room->id }}">
                                    <td style="padding: 0.5rem;">{{ $room->room_number }}</td>
                                    <td style="padding: 0.5rem;">{{ $room->room_type }}</td>
                                    <td style="padding: 0.5rem;">{{ $room->capacity }}</td>
                                    <td style="padding: 0.5rem;">{{ $room->available_count }}</td>
                                    <td style="padding: 0.5rem;">{{ $room->is_private ? 'Yes' : 'No' }}</td>
                                    <td style="padding: 0.5rem;">
                                        <span class="badge {{ $room->room_status === 'available' ? 'badge-success' : 'badge-muted' }}">{{ $room->room_status }}</span>
                                    </td>
                                    <td style="padding: 0.5rem;">
                                        <button type="button" class="btn btn-secondary" wire:click="editRoom({{ $room->id }})">Edit</button>
                                        <button type="button" class="btn btn-danger" wire:click="deleteRoom({{ $room->id }})" wire:confirm="Delete this room?">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-muted" style="padding: 1rem; text-align: center;">No rooms on this floor yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <h3 style="margin-bottom: 1rem;">{{ $editingRoomId ? 'Edit Room' : 'Add Room' }}</h3>

                <div class="grid-2">
                    <div class="field">
                        <x-input-label value="Room Number" />
                        <input type="text" wire:model="roomNumber">
                        <x-input-error :messages="$errors->get('roomNumber')" />
                    </div>
                    <div class="field">
                        <x-input-label value="Room Type" />
                        <select wire:model="roomType">
                            <option value="single">Single</option>
                            <option value="double">Double</option>
                            <option value="triple">Triple</option>
                            <option value="quad">Quad</option>
                            <option value="dormitory">Dormitory</option>
                        </select>
                    </div>
                    <div class="field">
                        <x-input-label value="Capacity" />
                        <input type="number" wire:model="capacity" min="1">
                        <x-input-error :messages="$errors->get('capacity')" />
                    </div>
                    <div class="field">
                        <x-input-label value="Status" />
                        <select wire:model="roomStatus">
                            <option value="available">Available</option>
                            <option value="partial">Partial</option>
                            <option value="full">Full</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label class="checkbox-label">
                        <input type="checkbox" wire:model="isPrivate">
                        <span>Private (reserved for one family, not pooled with strangers)</span>
                    </label>
                </div>
                <div class="field">
                    <label class="checkbox-label">
                        <input type="checkbox" wire:model="womenOnly">
                        <span>Women only</span>
                    </label>
                </div>
                <div class="field">
                    <label class="checkbox-label">
                        <input type="checkbox" wire:model="elderlyFriendly">
                        <span>Elderly friendly</span>
                    </label>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-primary" wire:click="saveRoom">{{ $editingRoomId ? 'Save Changes' : 'Add Room' }}</button>
                    @if ($editingRoomId)
                        <button type="button" class="btn btn-secondary" wire:click="resetRoomForm">Cancel</button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
