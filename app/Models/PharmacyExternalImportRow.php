<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PharmacyExternalImportRow extends Model
{
    use HasUuids;
    use SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'import_id',
        'row_hash',
        'external_dispense_number',
        'dispensed_at',
        'customer_name_raw',
        'customer_name_normalized',
        'pharmacist_name_raw',
        'pharmacist_name_normalized',
        'pharmacist_user_id',
        'citizen_id',
        'central_pharmacy_request_id',
        'medication_name_raw',
        'quantity',
        'bypass_detected',
        'recurrence_interval_days',
        'recurrence_alert_level',
        'payload',
    ];

    protected $casts = [
        'dispensed_at' => 'datetime',
        'quantity' => 'integer',
        'bypass_detected' => 'boolean',
        'recurrence_interval_days' => 'integer',
        'payload' => 'array',
    ];

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(PharmacyExternalImport::class, 'import_id');
    }

    public function pharmacistUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pharmacist_user_id');
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function centralPharmacyRequest(): BelongsTo
    {
        return $this->belongsTo(CentralPharmacyRequest::class);
    }
}
