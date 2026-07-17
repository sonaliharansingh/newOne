<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    /** @use HasFactory<\Database\Factories\RoomFactory> */
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'floor_id',
        'room_number',
        'room_type',
        'capacity',
        'occupied_count',
        'available_count',
        'is_private',
        'lift_access',
        'staircase_access',
        'women_only',
        'elderly_friendly',
        'room_status',
    ];

    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
            'lift_access' => 'boolean',
            'staircase_access' => 'boolean',
            'women_only' => 'boolean',
            'elderly_friendly' => 'boolean',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    public function hasCapacityFor(int $count): bool
    {
        return $this->room_status !== 'maintenance' && $this->available_count >= $count;
    }
}
