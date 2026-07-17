<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hotel extends Model
{
    /** @use HasFactory<\Database\Factories\HotelFactory> */
    use HasFactory;

    protected $fillable = [
        'hotel_name',
        'address',
        'city',
        'state',
        'total_floors',
        'has_lift',
        'has_staircase',
    ];

    protected function casts(): array
    {
        return [
            'has_lift' => 'boolean',
            'has_staircase' => 'boolean',
        ];
    }

    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
