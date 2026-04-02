<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WomensClinicAppointment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;
    
    // Regras de Ouro: Rastreação (skill saude-assai)
    // use LogsActivity; 

    protected $fillable = [
        'cns', 'cpf', 'data_nascimento', 'sexo', 'nome_completo',
        'telefone_celular', 'turno', 'tipo_atendimento',
        'ciap_principal', 'cid_principal', 'peso', 'altura',
        'dum', 'idade_gestacional', 'st_gravidez_planejada',
        'nu_gestas_previas', 'nu_partos', 'uuid_transporte'
    ];
}
