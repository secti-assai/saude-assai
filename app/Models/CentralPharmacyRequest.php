<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CentralPharmacyRequest extends Model
{
    use HasUuids;
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'citizen_id',
        'reception_user_id',
        'attendant_user_id',
        'prescription_code',
        'medication_name',
        'quantity',
        'gov_assai_level',
        'residence_status',
        'status',
        'notes',
        'dispensed_at',
    ];

    protected $casts = [
        'dispensed_at' => 'datetime',
    ];

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reception_user_id');
    }

    public function attendant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attendant_user_id');
    }
}
