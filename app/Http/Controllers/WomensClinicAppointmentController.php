<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WomensClinicAppointmentController extends Controller
{
    private $esusService;

    public function __construct(\App\Services\WomensClinicEsusService $esusService)
    {
        $this->esusService = $esusService;
    }

    /**
     * Endpoint para salvar o formulário agendado da Clínica da Mulher
     */
    public function store(\App\Http\Requests\WomensClinic\WomensClinicAppointmentRequest $request): \Illuminate\Http\JsonResponse
    {
        // 1. Validação (Request Validation refletido do Thrift já aconteceu no WomensClinicAppointmentRequest)
        $validatedData = $request->validated();

        // 2. Banco do Sistema Local (Criação do Agendamento) 
        $appointment = \App\Models\WomensClinicAppointment::create($validatedData);

        // 3. O "Pulo do Gato": Integração com o Thrift (PEC)
        try {
             $cnsProfissional = auth()->user()->cns ?? '123456789012345';
             $cboMedico = '225124'; // Med. Saúde da Familia / Gineco
             $cnesAssai = '1234567';

            $this->esusService->syncAppointmentToEsus($appointment, $cnsProfissional, $cboMedico, $cnesAssai);

            return response()->json([
                'message' => 'Consulta Agendada com Sucesso e Serializada no PEC!',
                'uuid_transporte' => $appointment->uuid_transporte
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro na Injeção e-SUS: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint do Autocomplete (UX Estilo PEC)
     */
    public function autocompleteCitizen($documento): \Illuminate\Http\JsonResponse
    {
        $dbCidadao = \App\Models\Citizen::where('cpf', $documento)
                         ->orWhere('cns', $documento)
                         ->first();

        if (!$dbCidadao) {
            return response()->json(['message' => 'Cidadão não encontrado na base de Assaí'], 404);
        }

        return response()->json([
            'cns' => $dbCidadao->cns,
            'cpf' => $dbCidadao->cpf,
            'data_nascimento' => $dbCidadao->data_nascimento->format('Y-m-d'),
            'sexo' => $dbCidadao->sexo,
            'nome_completo' => $dbCidadao->nome,
            'telefone_celular' => $dbCidadao->telefone
        ]);
    }
}
