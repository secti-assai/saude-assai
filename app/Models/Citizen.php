<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Citizen extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cpf',
        'cns',
        'full_name',
        'social_name',
        'birth_date',
        'gender',
        'address',
        'is_resident_assai',
        'residence_validated_at',
        'phone',
        'email',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_resident_assai' => 'boolean',
            'residence_validated_at' => 'datetime',
        ];
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
