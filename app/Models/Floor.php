<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Floor extends Model
{
    /** @use HasFactory<\Database\Factories\FloorFactory> */
    use HasFactory;

    protected $fillable = [
        'hotel_id',
        'floor_number',
        'lift_access',
        'staircase_access',
        'women_only',
        'elderly_friendly',
    ];

    protected function casts(): array
    {
        return [
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

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
