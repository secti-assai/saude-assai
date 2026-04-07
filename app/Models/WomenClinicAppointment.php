<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WomenClinicAppointment extends Model
{
    use HasUuids;
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'citizen_id',
        'scheduler_user_id',
        'reception_user_id',
        'doctor_user_id',
        'scheduled_for',
        'specialty',
        'gov_assai_level',
        'residence_status',
        'status',
        'notes',
        'checked_in_at',
        'checked_out_at',
        'cancelled_at',
        'reminder_24h_sent_at',
        'feedback_score',
        'feedback_comment',
        'feedback_submitted_at',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'reminder_24h_sent_at' => 'datetime',
        'feedback_submitted_at' => 'datetime',
    ];

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduler_user_id');
    }

    public function reception(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reception_user_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_user_id');
    }
}
