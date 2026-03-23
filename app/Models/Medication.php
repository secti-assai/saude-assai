<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    protected $fillable = ['code', 'name', 'presentation', 'concentration', 'is_remume'];

    protected function casts(): array
    {
        return ['is_remume' => 'boolean'];
    }
}
