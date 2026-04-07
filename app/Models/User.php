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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'first_login_at' => 'datetime',
        'last_logout_at' => 'datetime',
        'permissions' => 'array',
    ];

    public const ROLE_ADMIN = 'admin';
    public const ROLE_AGENDADOR = 'agendador';
    public const ROLE_RECEPCAO_CLINICA = 'recepcao_clinica';
    public const ROLE_MEDICO_CLINICA = 'medico_clinica';
    public const ROLE_FARMACIA = 'farmacia';

    /** @use HasFactory<UserFactory> */

    public const PERMISSION_WOMEN_CLINIC_SCHEDULE = 'women_clinic.schedule';
    public const PERMISSION_WOMEN_CLINIC_CHECKIN = 'women_clinic.checkin';
    public const PERMISSION_WOMEN_CLINIC_CHECKOUT = 'women_clinic.checkout';
    public const PERMISSION_WOMEN_CLINIC_REPORTS = 'women_clinic.reports';
    public const PERMISSION_CENTRAL_PHARMACY = 'central_pharmacy.unified';
    public const PERMISSION_CENTRAL_PHARMACY_REPORTS = 'central_pharmacy.reports';

    public static function allPermissionOptions(): array
    {
        return [
            self::PERMISSION_WOMEN_CLINIC_SCHEDULE,
            self::PERMISSION_WOMEN_CLINIC_CHECKIN,
            self::PERMISSION_WOMEN_CLINIC_CHECKOUT,
            self::PERMISSION_WOMEN_CLINIC_REPORTS,
            self::PERMISSION_CENTRAL_PHARMACY,
            self::PERMISSION_CENTRAL_PHARMACY_REPORTS,
        ];
    }

    public function roleDefaultPermissions(): array
    {
        return match ($this->role) {
            self::ROLE_ADMIN => self::allPermissionOptions(),
            self::ROLE_AGENDADOR => [self::PERMISSION_WOMEN_CLINIC_SCHEDULE, self::PERMISSION_WOMEN_CLINIC_REPORTS],
            self::ROLE_RECEPCAO_CLINICA => [self::PERMISSION_WOMEN_CLINIC_CHECKIN],
            self::ROLE_MEDICO_CLINICA => [self::PERMISSION_WOMEN_CLINIC_CHECKOUT],
            self::ROLE_FARMACIA => [self::PERMISSION_CENTRAL_PHARMACY],
            default => [],
        };
    }

    public function effectivePermissions(): array
    {
        if ($this->role === self::ROLE_ADMIN) {
            return self::allPermissionOptions();
        }

        if (is_array($this->permissions) && $this->permissions !== []) {
            $normalized = array_values(array_unique(array_filter(
                $this->permissions,
                fn ($permission): bool => is_string($permission) && $permission !== ''
            )));

            return array_values(array_intersect($normalized, self::allPermissionOptions()));
        }

        return $this->roleDefaultPermissions();
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->effectivePermissions(), true);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (is_string($permission) && $permission !== '' && $this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function healthUnit(): BelongsTo
    {
        return $this->belongsTo(HealthUnit::class);
    }
}
