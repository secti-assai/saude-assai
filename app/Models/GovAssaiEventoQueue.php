<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GovAssaiEventoQueue extends Model
{
    use HasFactory;

    protected $table = 'gov_assai_eventos_queue';
    
    protected $guarded = ['id'];

    protected $casts = [
        'dados_adicionais' => 'array',
        'payload_json' => 'array',
        'data_hora' => 'datetime',
        'ultima_tentativa_em' => 'datetime',
        'enviado_em' => 'datetime',
    ];
}
