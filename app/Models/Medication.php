<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    protected $fillable = ['code', 'name', 'presentation', 'concentration', 'is_remume'];

    protected $casts = ['is_remume' => 'boolean'];
}
