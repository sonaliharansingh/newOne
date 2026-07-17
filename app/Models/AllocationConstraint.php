<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AllocationConstraint extends Model
{
    protected $fillable = [
        'constraint_name',
        'constraint_type',
        'weight',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}
