<?php

namespace App\Livewire\Admin;

use App\Models\Floor;
use App\Models\Hotel;
use App\Models\Room;
use Livewire\Component;

class RoomInventory extends Component
{
    public ?int $selectedHotelId = null;

    public ?int $selectedFloorId = null;

    // New hotel form
    public string $newHotelName = '';

    public string $newHotelCity = '';

    public string $newHotelState = '';

    // New floor form
    public int $newFloorNumber = 1;

    public bool $newFloorWomenOnly = false;

    public bool $newFloorElderlyFriendly = false;

    // Room form (create or edit)
    public ?int $editingRoomId = null;

    public string $roomNumber = '';

    public string $roomType = 'double';

    public int $capacity = 2;

    public bool $isPrivate = true;

    public bool $womenOnly = false;

    public bool $elderlyFriendly = false;

    public string $roomStatus = 'available';

    public function mount(): void
    {
        $this->selectedHotelId = Hotel::query()->value('id');
        $this->selectedFloorId = Floor::where('hotel_id', $this->selectedHotelId)->value('id');
    }

    public function selectHotel(int $hotelId): void
    {
        $this->selectedHotelId = $hotelId;
        $this->selectedFloorId = Floor::where('hotel_id', $hotelId)->value('id');
    }

    public function selectFloor(int $floorId): void
    {
        $this->selectedFloorId = $floorId;
        $this->resetRoomForm();
    }

    public function addHotel(): void
    {
        $this->validate([
            'newHotelName' => 'required|string|max:150',
            'newHotelCity' => 'nullable|string|max:100',
            'newHotelState' => 'nullable|string|max:100',
        ]);

        $hotel = Hotel::create([
            'hotel_name' => $this->newHotelName,
            'city' => $this->newHotelCity ?: null,
            'state' => $this->newHotelState ?: null,
            'has_lift' => true,
            'has_staircase' => true,
        ]);

        $this->newHotelName = '';
        $this->newHotelCity = '';
        $this->newHotelState = '';
        $this->selectHotel($hotel->id);
    }

    public function addFloor(): void
    {
        $this->validate([
            'newFloorNumber' => 'required|integer|min:0',
        ]);

        $floor = Floor::create([
            'hotel_id' => $this->selectedHotelId,
            'floor_number' => $this->newFloorNumber,
            'lift_access' => true,
            'staircase_access' => true,
            'women_only' => $this->newFloorWomenOnly,
            'elderly_friendly' => $this->newFloorElderlyFriendly,
        ]);

        Hotel::where('id', $this->selectedHotelId)->increment('total_floors');

        $this->newFloorNumber = 1;
        $this->newFloorWomenOnly = false;
        $this->newFloorElderlyFriendly = false;
        $this->selectFloor($floor->id);
    }

    public function editRoom(int $roomId): void
    {
        $room = Room::findOrFail($roomId);

        $this->editingRoomId = $room->id;
        $this->roomNumber = $room->room_number;
        $this->roomType = $room->room_type;
        $this->capacity = $room->capacity;
        $this->isPrivate = $room->is_private;
        $this->womenOnly = $room->women_only;
        $this->elderlyFriendly = $room->elderly_friendly;
        $this->roomStatus = $room->room_status;
    }

    public function resetRoomForm(): void
    {
        $this->editingRoomId = null;
        $this->roomNumber = '';
        $this->roomType = 'double';
        $this->capacity = 2;
        $this->isPrivate = true;
        $this->womenOnly = false;
        $this->elderlyFriendly = false;
        $this->roomStatus = 'available';
    }

    public function saveRoom(): void
    {
        $this->validate([
            'roomNumber' => 'required|string|max:50',
            'roomType' => 'required|in:single,double,triple,quad,dormitory',
            'capacity' => 'required|integer|min:1',
            'roomStatus' => 'required|in:available,partial,full,maintenance',
        ]);

        $floor = Floor::findOrFail($this->selectedFloorId);

        $occupied = $this->editingRoomId ? Room::findOrFail($this->editingRoomId)->occupied_count : 0;

        $attributes = [
            'hotel_id' => $floor->hotel_id,
            'floor_id' => $floor->id,
            'room_number' => $this->roomNumber,
            'room_type' => $this->roomType,
            'capacity' => $this->capacity,
            'occupied_count' => $occupied,
            'available_count' => max(0, $this->capacity - $occupied),
            'is_private' => $this->isPrivate,
            'lift_access' => $floor->lift_access,
            'staircase_access' => $floor->staircase_access,
            'women_only' => $this->womenOnly,
            'elderly_friendly' => $this->elderlyFriendly,
            'room_status' => $this->roomStatus,
        ];

        if ($this->editingRoomId) {
            Room::findOrFail($this->editingRoomId)->update($attributes);
        } else {
            Room::create($attributes);
        }

        $this->resetRoomForm();
        session()->flash('status', 'Room saved.');
    }

    public function deleteRoom(int $roomId): void
    {
        Room::findOrFail($roomId)->delete();
        session()->flash('status', 'Room deleted.');
    }

    public function render()
    {
        $hotels = Hotel::withCount('rooms')->orderBy('hotel_name')->get();
        $floors = $this->selectedHotelId
            ? Floor::where('hotel_id', $this->selectedHotelId)->orderBy('floor_number')->get()
            : collect();
        $rooms = $this->selectedFloorId
            ? Room::where('floor_id', $this->selectedFloorId)->orderBy('room_number')->get()
            : collect();

        return view('livewire.admin.room-inventory', [
            'hotels' => $hotels,
            'floors' => $floors,
            'rooms' => $rooms,
        ]);
    }
}
