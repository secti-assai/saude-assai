<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockItem extends Model
{
    protected $fillable = ['medication_id', 'health_unit_id', 'batch', 'expiry_date', 'quantity'];

    protected function casts(): array
    {
        return ['expiry_date' => 'date'];
    }
}
