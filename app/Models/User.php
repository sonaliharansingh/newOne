<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'date_of_birth',
        'language',
        'passport_number',
        'father_name',
        'mother_name',
        'phone',
        'city',
        'area',
        'state',
        'country',
        'address',
        'gender',
        'adhar_id',
        'luggage_count',
        'photo_url',
        'type',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Age is derived from date_of_birth when present, falling back to the stored column.
     */
    public function getAgeAttribute(): ?int
    {
        if ($this->date_of_birth) {
            return $this->date_of_birth->age;
        }

        return $this->attributes['age'] ?? null;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isInventoryMember(): bool
    {
        return $this->role === 'Inventorymember';
    }

    public function groupMemberships(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function createdGroups(): HasMany
    {
        return $this->hasMany(Group::class, 'created_by');
    }
}
