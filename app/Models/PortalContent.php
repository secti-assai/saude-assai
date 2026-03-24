<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortalContent extends Model
{
    use HasFactory;

    protected $guarded = ["id"];
    
    protected $casts = [
        "published" => "boolean",
        "published_at" => "datetime",
        "metadata" => "json",
    ];
}

