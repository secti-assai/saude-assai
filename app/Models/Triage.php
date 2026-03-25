<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Triage extends Model
{
    protected $fillable = [
        'attendance_id',
        'nurse_user_id',
        'nursing_history',
        'comorbidities',
        'consciousness_level',
        'systolic_pressure',
        'diastolic_pressure',
        'temperature',
        'heart_rate',
        'spo2',
        'hgt',
        'weight',
        'risk_color',
        'risk_classification',
    ];

    protected $casts = ['comorbidities' => 'array'];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(AlertVitalSign::class);
    }
}
