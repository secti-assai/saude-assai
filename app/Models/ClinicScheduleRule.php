<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ClinicScheduleRule extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'clinic_type',
        'specialty',
        'day_of_week',
        'weeks_of_month',
        'time',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'weeks_of_month' => 'array',
        'is_active' => 'boolean',
    ];
}
