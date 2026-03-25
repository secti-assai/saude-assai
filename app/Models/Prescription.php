<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Prescription extends Model
{
    protected $fillable = [
        'attendance_id',
        'citizen_id',
        'doctor_user_id',
        'delivery_type',
        'status',
        'notes',
        'signed_at',
    ];

    protected function casts(): array
    {
        return ['signed_at' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function dispensation(): HasOne
    {
        return $this->hasOne(Dispensation::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }
}
