<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortalContent extends Model
{
    protected $fillable = ['type', 'title', 'body', 'published', 'published_at'];

    protected function casts(): array
    {
        return ['published' => 'boolean', 'published_at' => 'datetime'];
    }
}
