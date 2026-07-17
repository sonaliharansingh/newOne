<?php

namespace Database\Seeders;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Database\Seeder;

class HotelSeeder extends Seeder
{
    public function run(): void
    {
        $hotels = [
            ['hotel_name' => 'Ganga Niwas', 'city' => 'Haridwar', 'floors' => 4],
            ['hotel_name' => 'Yamuna Bhavan', 'city' => 'Rishikesh', 'floors' => 3],
        ];

        foreach ($hotels as $hotelData) {
            $hotel = Hotel::create([
                'hotel_name' => $hotelData['hotel_name'],
                'address' => $hotelData['hotel_name'].' Main Road',
                'city' => $hotelData['city'],
                'state' => 'Uttarakhand',
                'total_floors' => $hotelData['floors'],
                'has_lift' => true,
                'has_staircase' => true,
            ]);

            for ($floorNumber = 1; $floorNumber <= $hotelData['floors']; $floorNumber++) {
                $floor = $hotel->floors()->create([
                    'floor_number' => $floorNumber,
                    'lift_access' => true,
                    'staircase_access' => true,
                    'women_only' => $floorNumber === 2,
                    'elderly_friendly' => $floorNumber === 1,
                ]);

                $this->seedRoomsForFloor($hotel, $floor, $floorNumber);
            }
        }
    }

    private function seedRoomsForFloor(Hotel $hotel, $floor, int $floorNumber): void
    {
        // is_private marks a room reserved exclusively for one family/cluster (Priority 1/2/3
        // strategies), independent of capacity — a quad can be a private family room or a
        // shared room used to pool unrelated members (Priority 4/5), so roughly half of the
        // triple/quad stock is flagged private and half shared.
        $roomPlan = [
            ['room_type' => 'single', 'capacity' => 1, 'count' => 2, 'private_count' => 2],
            ['room_type' => 'double', 'capacity' => 2, 'count' => 4, 'private_count' => 4],
            ['room_type' => 'triple', 'capacity' => 3, 'count' => 4, 'private_count' => 2],
            ['room_type' => 'quad', 'capacity' => 4, 'count' => 4, 'private_count' => 2],
            ['room_type' => 'dormitory', 'capacity' => 8, 'count' => 1, 'private_count' => 0],
        ];

        $roomSequence = 1;

        foreach ($roomPlan as $plan) {
            for ($i = 0; $i < $plan['count']; $i++) {
                Room::create([
                    'hotel_id' => $hotel->id,
                    'floor_id' => $floor->id,
                    'room_number' => sprintf('%d%02d', $floorNumber, $roomSequence++),
                    'room_type' => $plan['room_type'],
                    'capacity' => $plan['capacity'],
                    'occupied_count' => 0,
                    'available_count' => $plan['capacity'],
                    'is_private' => $i < $plan['private_count'],
                    'lift_access' => $floor->lift_access,
                    'staircase_access' => $floor->staircase_access,
                    'women_only' => $floor->women_only,
                    'elderly_friendly' => $floor->elderly_friendly,
                    'room_status' => 'available',
                ]);
            }
        }
    }
}
