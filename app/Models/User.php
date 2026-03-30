<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'permissions',
        'registration',
        'crm',
        'health_unit_id',
        'two_factor_enabled',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'two_factor_enabled' => 'boolean',
        'first_login_at' => 'datetime',
        'last_logout_at' => 'datetime',
        'permissions' => 'array',
    ];

    public const ROLE_ADMIN = 'admin';
    public const ROLE_AGENDADOR = 'agendador';
    public const ROLE_RECEPCAO_CLINICA = 'recepcao_clinica';
    public const ROLE_MEDICO_CLINICA = 'medico_clinica';
    public const ROLE_RECEPCAO_FARMACIA = 'recepcao_farmacia';
    public const ROLE_ATENDIMENTO_FARMACIA = 'atendimento_farmacia';

    /** @use HasFactory<UserFactory> */

    public const PERMISSION_WOMEN_CLINIC_SCHEDULE = 'women_clinic.schedule';
    public const PERMISSION_WOMEN_CLINIC_CHECKIN = 'women_clinic.checkin';
    public const PERMISSION_WOMEN_CLINIC_CHECKOUT = 'women_clinic.checkout';
    public const PERMISSION_CENTRAL_PHARMACY_RECEPTION = 'central_pharmacy.reception';
    public const PERMISSION_CENTRAL_PHARMACY_DISPENSE = 'central_pharmacy.dispense';

    public static function allPermissionOptions(): array
    {
        return [
            self::PERMISSION_WOMEN_CLINIC_SCHEDULE,
            self::PERMISSION_WOMEN_CLINIC_CHECKIN,
            self::PERMISSION_WOMEN_CLINIC_CHECKOUT,
            self::PERMISSION_CENTRAL_PHARMACY_RECEPTION,
            self::PERMISSION_CENTRAL_PHARMACY_DISPENSE,
        ];
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role === self::ROLE_ADMIN) {
            return true;
        }

        $permissions = $this->permissions ?? [];
        if (! is_array($permissions) || $permissions === []) {
            return false;
        }

        return in_array($permission, $permissions, true);
    }

    public function healthUnit(): BelongsTo
    {
        return $this->belongsTo(HealthUnit::class);
    }
}
