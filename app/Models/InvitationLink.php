<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationLink extends Model
{
    /** @use HasFactory<\Database\Factories\InvitationLinkFactory> */
    use HasFactory;

    protected $fillable = [
        'group_id',
        'invite_code',
        'max_joins',
        'joined_count',
        'expires_at',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function isUsable(): bool
    {
        return $this->active
            && $this->joined_count < $this->max_joins
            && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
