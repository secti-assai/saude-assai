<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'profile',
        'module',
        'action',
        'entity_type',
        'entity_id',
        'ip_address',
        'user_agent',
        'metadata',
        'created_at',
    ];
}
