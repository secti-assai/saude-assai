<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Citizen extends Model
{
    use SoftDeletes;

    protected $casts = [
        'cpf' => 'encrypted',
        'cns' => 'encrypted',
        'email' => 'encrypted',
        'birth_date' => 'date',
        'is_resident_assai' => 'boolean',
        'pharmacy_lock_flag' => 'boolean',
        'residence_validated_at' => 'datetime',
    ];

    protected $fillable = [
        'cpf',
        'cpf_hash',
        'cns',
        'full_name',
        'social_name',
        'birth_date',
        'sexo',
        'genero',
        'raca_cor',
        'gov_assai_id',
        'address',
        'is_resident_assai',
        'pharmacy_lock_flag',
        'residence_validated_at',
        'phone',
        'email',
    ];

}
