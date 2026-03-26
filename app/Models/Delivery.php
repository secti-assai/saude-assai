<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    protected $fillable = [
        'prescription_id',
        'delivery_user_id',
        'status',
        'address',
        'gps_lat',
        'gps_lng',
        'failure_reason',
        'signature_data',
        'confirmed_at',
    ];

    protected $casts = ['confirmed_at' => 'datetime'];

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }
}
