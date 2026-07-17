<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PilgrimNotification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'group_id',
        'title',
        'message',
        'type',
        'status',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
