<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checkin extends Model
{
    protected $fillable = [
        'allocation_id',
        'checked_in_at',
        'checked_out_at',
        'luggage_count',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(Allocation::class);
    }
}
