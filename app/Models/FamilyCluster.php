<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamilyCluster extends Model
{
    /** @use HasFactory<\Database\Factories\FamilyClusterFactory> */
    use HasFactory;

    protected $fillable = [
        'group_id',
        'cluster_name',
        'cluster_size',
        'cluster_score',
        'allocation_status',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class, 'cluster_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class, 'cluster_id');
    }

    public function adminFlags(): HasMany
    {
        return $this->hasMany(AdminFlag::class, 'cluster_id');
    }
}
