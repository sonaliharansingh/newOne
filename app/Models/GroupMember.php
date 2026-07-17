<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupMember extends Model
{
    /** @use HasFactory<\Database\Factories\GroupMemberFactory> */
    use HasFactory;

    protected $fillable = [
        'group_id',
        'user_id',
        'is_leader',
        'related_user_id',
        'relation_type',
        'relation_score',
        'guardian_required',
        'allocation_priority',
        'cluster_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_leader' => 'boolean',
            'guardian_required' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function related(): BelongsTo
    {
        return $this->belongsTo(GroupMember::class, 'related_user_id');
    }

    public function dependents(): HasMany
    {
        return $this->hasMany(GroupMember::class, 'related_user_id');
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(FamilyCluster::class, 'cluster_id');
    }

    public function isMinorRequiringFemaleGuardian(): bool
    {
        return $this->user->age !== null && $this->user->age < 15;
    }

    public function isMinorRequiringMaleGuardian(): bool
    {
        return $this->user->gender === 'male' && $this->user->age !== null && $this->user->age >= 15 && $this->user->age <= 17;
    }
}
