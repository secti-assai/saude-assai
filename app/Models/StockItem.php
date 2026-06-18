<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockItem extends Model
{
    protected $fillable = [
        'medication_id',
        'health_unit_id',
        'batch',
        'expiry_date',
        'quantity',
        'unit_cost',
        'total_cost',
        'entry_date',
        'supplier',
    ];

    public function medication()
    {
        return $this->belongsTo(Medication::class);
    }
}
