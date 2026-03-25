<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    ];

    public const ROLE_ADMIN = 'admin_secti';
    public const ROLE_GESTOR = 'gestor';
    public const ROLE_RECEPCAO = 'recepcionista';
    public const ROLE_ENFERMAGEM = 'enfermeiro';
    public const ROLE_MEDICO_UBS = 'medico_ubs';
    public const ROLE_MEDICO_HOSPITAL = 'medico_hospital';
    public const ROLE_FARMACIA = 'farmaceutico';
    public const ROLE_ENTREGADOR = 'entregador';
    public const ROLE_AUDITOR = 'auditor';

    /** @use HasFactory<UserFactory> */

    public function healthUnit(): BelongsTo
    {
        return $this->belongsTo(HealthUnit::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'doctor_user_id');
    }

    public function triages(): HasMany
    {
        return $this->hasMany(Triage::class, 'nurse_user_id');
    }
}
