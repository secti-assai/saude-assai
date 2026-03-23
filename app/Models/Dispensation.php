<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispensation extends Model
{
    protected $fillable = [
        'prescription_id',
        'citizen_id',
        'pharmacist_user_id',
        'residence_status',
        'blocked',
        'justification',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'blocked' => 'boolean',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(DispensationItem::class);
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }
}
