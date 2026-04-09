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

    public const CLINIC_WOMEN = 'CLINICA_MULHER';
    public const CLINIC_POLICLINICA = 'POLICLINICA';

    public const SPECIALTY_CARDIOLOGIA = 'CARDIOLOGIA';
    public const SPECIALTY_ORTOPEDIA = 'ORTOPEDIA';
    public const SPECIALTY_PSIQUIATRIA = 'PSIQUIATRIA';
    public const SPECIALTY_ODONTOLOGIA = 'ODONTOLOGIA';
    public const SPECIALTY_FISIOTERAPIA = 'FISIOTERAPIA';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'citizen_id',
        'scheduler_user_id',
        'reception_user_id',
        'doctor_user_id',
        'scheduled_for',
        'clinic_type',
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

    /**
     * @return array<string,string>
     */
    public static function clinicOptions(): array
    {
        return [
            self::CLINIC_WOMEN => 'Clinica da Mulher',
            self::CLINIC_POLICLINICA => 'Policlinica',
        ];
    }

    /**
     * @return array<int,string>
     */
    public static function clinicValues(): array
    {
        return array_keys(self::clinicOptions());
    }

    /**
     * @return array<string,array<string,string>>
     */
    public static function specialtyOptionsByClinic(): array
    {
        return [
            self::CLINIC_WOMEN => [
                self::SPECIALTY_CARDIOLOGIA => 'Cardiologia',
                self::SPECIALTY_ORTOPEDIA => 'Ortopedia',
                self::SPECIALTY_PSIQUIATRIA => 'Psiquiatria',
            ],
            self::CLINIC_POLICLINICA => [
                self::SPECIALTY_ODONTOLOGIA => 'Odontologia',
                self::SPECIALTY_FISIOTERAPIA => 'Fisioterapia',
            ],
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function specialtyOptions(): array
    {
        return array_merge(...array_values(self::specialtyOptionsByClinic()));
    }

    /**
     * @return array<string,string>
     */
    public static function specialtyOptionsForClinic(?string $clinicType): array
    {
        $normalizedClinic = self::resolveClinicType($clinicType);

        return self::specialtyOptionsByClinic()[$normalizedClinic] ?? [];
    }

    /**
     * @return array<int,string>
     */
    public static function specialtyValues(): array
    {
        return array_keys(self::specialtyOptions());
    }

    /**
     * @return array<int,string>
     */
    public static function specialtyValuesForClinic(?string $clinicType): array
    {
        return array_keys(self::specialtyOptionsForClinic($clinicType));
    }

    public static function normalizeClinicType(?string $clinicType): ?string
    {
        if (! is_string($clinicType)) {
            return null;
        }

        $normalized = strtoupper(trim($clinicType));

        return in_array($normalized, self::clinicValues(), true)
            ? $normalized
            : null;
    }

    public static function resolveClinicType(?string $clinicType): string
    {
        return self::normalizeClinicType($clinicType) ?? self::CLINIC_WOMEN;
    }

    public static function clinicLabel(?string $clinicType): string
    {
        $normalized = self::resolveClinicType($clinicType);

        return self::clinicOptions()[$normalized] ?? 'Clinica nao informada';
    }

    public static function normalizeSpecialty(?string $specialty): ?string
    {
        if (! is_string($specialty)) {
            return null;
        }

        $normalized = strtoupper(trim($specialty));

        return in_array($normalized, self::specialtyValues(), true)
            ? $normalized
            : null;
    }

    public static function specialtyLabel(?string $specialty): string
    {
        $normalized = self::normalizeSpecialty($specialty);

        if ($normalized === null) {
            return 'Nao informado';
        }

        return self::specialtyOptions()[$normalized] ?? 'Nao informado';
    }

    public static function isSpecialtyAllowedForClinic(?string $clinicType, ?string $specialty): bool
    {
        $normalizedSpecialty = self::normalizeSpecialty($specialty);

        if ($normalizedSpecialty === null) {
            return false;
        }

        return in_array($normalizedSpecialty, self::specialtyValuesForClinic($clinicType), true);
    }

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
