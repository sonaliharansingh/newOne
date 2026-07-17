<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Allocation extends Model
{
    /** @use HasFactory<\Database\Factories\AllocationFactory> */
    use HasFactory;

    protected $fillable = [
        'group_id',
        'cluster_id',
        'user_id',
        'room_id',
        'allocated_by',
        'allocation_type',
        'allocation_score',
        'priority_level',
        'allocation_status',
        'remarks',
        'free_stay',
    ];

    protected function casts(): array
    {
        return [
            'free_stay' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(FamilyCluster::class, 'cluster_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AllocationLog::class);
    }
}
