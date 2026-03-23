<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispensationItem extends Model
{
    protected $fillable = ['dispensation_id', 'medication_id', 'batch', 'expiry_date', 'quantity'];

    protected function casts(): array
    {
        return ['expiry_date' => 'date'];
    }

    public function dispensation(): BelongsTo
    {
        return $this->belongsTo(Dispensation::class);
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }
}
