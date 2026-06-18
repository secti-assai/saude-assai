<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medication extends Model
{
    protected $fillable = [
        'code',
        'name',
        'presentation',
        'concentration',
        'is_remume',
    ];

    public function stockItems()
    {
        return $this->hasMany(StockItem::class);
    }
}
