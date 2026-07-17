<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    /** @use HasFactory<\Database\Factories\GroupFactory> */
    use HasFactory;

    protected $fillable = [
        'group_name',
        'trip_start_date',
        'trip_end_date',
        'created_by',
        'expected_members',
        'joined_members',
        'invite_code',
        'invite_expiry',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'trip_start_date' => 'date',
            'trip_end_date' => 'date',
            'invite_expiry' => 'datetime',
        ];
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function clusters(): HasMany
    {
        return $this->hasMany(FamilyCluster::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    public function invitationLinks(): HasMany
    {
        return $this->hasMany(InvitationLink::class);
    }

    public function adminFlags(): HasMany
    {
        return $this->hasMany(AdminFlag::class);
    }

    public function bookingId(): string
    {
        return 'SNC-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
