<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    protected $fillable = [
        'citizen_id',
        'health_unit_id',
        'reception_user_id',
        'care_type',
        'queue_password',
        'priority_color',
        'residence_status',
        'summary_reason',
        'work_accident',
        'status',
        'arrived_at',
    ];

    protected $casts = [
        'work_accident' => 'boolean',
        'arrived_at' => 'datetime',
    ];

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function triage(): HasOne
    {
        return $this->hasOne(Triage::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function hospitalRecord(): HasOne
    {
        return $this->hasOne(HospitalRecord::class);
    }

    public function calls()
    {
        return $this->hasMany(Call::class);
    }
}
