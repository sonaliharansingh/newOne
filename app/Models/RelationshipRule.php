<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RelationshipRule extends Model
{
    /** @use HasFactory<\Database\Factories\RelationshipRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'relation_type',
        'score',
        'must_stay_together',
        'guardian_allowed',
        'nearby_room_priority',
        'same_room_priority',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'must_stay_together' => 'boolean',
            'guardian_allowed' => 'boolean',
            'active' => 'boolean',
        ];
    }
}
