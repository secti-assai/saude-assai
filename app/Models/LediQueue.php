<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LediQueue extends Model
{
    protected $fillable = [
        'resource_type',
        'resource_id',
        'ledger_type',
        'payload',
        'status',
        'attempts',
        'last_error',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
