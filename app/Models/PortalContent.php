<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PortalContent extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $casts = [
        'published' => 'boolean',
        'published_at' => 'datetime',
        'metadata' => 'json',
    ];

    protected $guarded = ["id"];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logUnguarded();
    }

}

