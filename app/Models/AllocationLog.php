<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AllocationLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'allocation_id',
        'action',
        'old_room_id',
        'new_room_id',
        'changed_by',
        'reason',
    ];

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(Allocation::class);
    }

    public function oldRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'old_room_id');
    }

    public function newRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'new_room_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
