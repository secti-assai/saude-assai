<?php

namespace App\Http\Requests\WomensClinic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WomensClinicAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Aba 1: Identificação do Cidadão (CidadaoTransportThrift)
            'cns' => ['required_without:cpf', 'string', 'size:15'],
            'cpf' => ['required_without:cns', 'string', 'size:11'],
            'data_nascimento' => ['required', 'date', 'before_or_equal:today'],
            'sexo' => ['required', 'integer', Rule::in([0, 1])], // 0 = Masculino, 1 = Feminino
            'nome_completo' => ['required', 'string', 'max:255'],
            'telefone_celular' => ['nullable', 'string', 'max:15'],

            // Aba 2: Motivo da Consulta (FichaAtendimentoIndividualChildThrift)
            'turno' => ['required', 'integer', Rule::in([1, 2, 3])], // 1=Manhã, 2=Tarde, 3=Noite
            'tipo_atendimento' => ['required', 'integer', Rule::in([1, 2, 4, 5, 6])],
            'ciap_principal' => ['nullable', 'string', 'max:4'], // CIAP2
            'cid_principal' => ['nullable', 'string', 'max:4'], // CID10
            
            // Aba 3: Dados Antropométricos (Medicoes)
            'peso' => ['nullable', 'numeric', 'min:0.1', 'max:500'],
            'altura' => ['nullable', 'numeric', 'min:10', 'max:300'],
            'pressao_sistolica' => ['nullable', 'integer', 'min:0', 'max:400'],
            'pressao_diastolica' => ['nullable', 'integer', 'min:0', 'max:400'],
            
            // Especificidades da Clínica da Mulher
            'dum' => ['nullable', 'date', 'before_or_equal:today'], // Data da Última Menstruação
            'idade_gestacional' => ['nullable', 'integer', 'min:1', 'max:42'],
            'nu_gestas_previas' => ['nullable', 'integer', 'min:0', 'max:20'],
            'nu_partos' => ['nullable', 'integer', 'min:0', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'cpf.required_without' => 'O CPF é obrigatório quando o CNS não for informado (regra do Thrift).',
            'cns.required_without' => 'O CNS é obrigatório quando o CPF não for informado (regra do Thrift).',
            'sexo.in' => 'O sexo informado é inválido. Utilize 0 (Masculino) ou 1 (Feminino).',
        ];
    }
}
