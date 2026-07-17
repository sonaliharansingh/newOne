<?php

namespace Database\Seeders;

use App\Models\RelationshipRule;
use Illuminate\Database\Seeder;

class RelationshipRuleSeeder extends Seeder
{
    /**
     * Canonical lookup data driving the priority allocation engine.
     * must_stay_together drives Priority 1/2/3 same-room grouping;
     * guardian_allowed marks relations that can satisfy the minor-guardian hard constraint.
     */
    public function run(): void
    {
        $rules = [
            ['relation_type' => 'Self', 'score' => 100, 'must_stay_together' => true, 'guardian_allowed' => true, 'nearby_room_priority' => 0, 'same_room_priority' => 100],
            ['relation_type' => 'Spouse', 'score' => 100, 'must_stay_together' => true, 'guardian_allowed' => true, 'nearby_room_priority' => 0, 'same_room_priority' => 100],
            ['relation_type' => 'Partner', 'score' => 100, 'must_stay_together' => true, 'guardian_allowed' => true, 'nearby_room_priority' => 0, 'same_room_priority' => 100],
            ['relation_type' => 'Child', 'score' => 95, 'must_stay_together' => true, 'guardian_allowed' => false, 'nearby_room_priority' => 0, 'same_room_priority' => 95],
            ['relation_type' => 'Parent', 'score' => 95, 'must_stay_together' => true, 'guardian_allowed' => true, 'nearby_room_priority' => 0, 'same_room_priority' => 95],
            ['relation_type' => 'Grandparent', 'score' => 85, 'must_stay_together' => true, 'guardian_allowed' => true, 'nearby_room_priority' => 60, 'same_room_priority' => 75],
            ['relation_type' => 'Grandchild', 'score' => 85, 'must_stay_together' => true, 'guardian_allowed' => false, 'nearby_room_priority' => 60, 'same_room_priority' => 75],
            ['relation_type' => 'Sibling', 'score' => 80, 'must_stay_together' => true, 'guardian_allowed' => true, 'nearby_room_priority' => 80, 'same_room_priority' => 70],
            ['relation_type' => 'In-law', 'score' => 55, 'must_stay_together' => false, 'guardian_allowed' => false, 'nearby_room_priority' => 55, 'same_room_priority' => 0],
            ['relation_type' => 'Uncle', 'score' => 60, 'must_stay_together' => false, 'guardian_allowed' => true, 'nearby_room_priority' => 60, 'same_room_priority' => 0],
            ['relation_type' => 'Aunt', 'score' => 60, 'must_stay_together' => false, 'guardian_allowed' => true, 'nearby_room_priority' => 60, 'same_room_priority' => 0],
            ['relation_type' => 'Cousin', 'score' => 50, 'must_stay_together' => false, 'guardian_allowed' => false, 'nearby_room_priority' => 50, 'same_room_priority' => 0],
            ['relation_type' => 'Nephew', 'score' => 50, 'must_stay_together' => false, 'guardian_allowed' => false, 'nearby_room_priority' => 50, 'same_room_priority' => 0],
            ['relation_type' => 'Niece', 'score' => 50, 'must_stay_together' => false, 'guardian_allowed' => false, 'nearby_room_priority' => 50, 'same_room_priority' => 0],
            ['relation_type' => 'Friend', 'score' => 20, 'must_stay_together' => false, 'guardian_allowed' => false, 'nearby_room_priority' => 20, 'same_room_priority' => 0],
        ];

        foreach ($rules as $rule) {
            RelationshipRule::updateOrCreate(
                ['relation_type' => $rule['relation_type']],
                $rule + ['active' => true]
            );
        }
    }
}
