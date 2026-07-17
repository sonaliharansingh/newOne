<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminFlag extends Model
{
    protected $fillable = [
        'group_id',
        'cluster_id',
        'member_id',
        'flag_type',
        'status',
        'resolved_by',
        'resolution_notes',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(FamilyCluster::class, 'cluster_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(GroupMember::class, 'member_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
