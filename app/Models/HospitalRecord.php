<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalRecord extends Model
{
    protected $fillable = [
        'attendance_id',
        'doctor_user_id',
        'soap_objective',
        'soap_assessment',
        'diagnosis',
        'cid_10',
        'secondary_cids',
        'procedures',
        'exams',
        'guidance',
        'outcome',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'secondary_cids' => 'array',
            'signed_at' => 'datetime',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }
}
