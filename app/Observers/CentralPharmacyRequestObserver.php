<?php

namespace App\Observers;

use App\Models\CentralPharmacyRequest;
use App\Services\GovAssaiIntegrationService;

class CentralPharmacyRequestObserver
{
    public function __construct(private readonly GovAssaiIntegrationService $govAssaiService)
    {
    }

    /**
     * Handle the CentralPharmacyRequest "created" event.
     */
    public function created(CentralPharmacyRequest $request): void
    {
        $this->syncWithGovAssai($request);
    }

    /**
     * Handle the CentralPharmacyRequest "updated" event.
     */
    public function updated(CentralPharmacyRequest $request): void
    {
        if ($request->isDirty('status') || $request->isDirty('dispensed_at')) {
            $this->syncWithGovAssai($request);
        }
    }

    private function syncWithGovAssai(CentralPharmacyRequest $request): void
    {
        $request->loadMissing('citizen');
        
        $cpf = $request->citizen?->cpf;
        if (!$cpf) {
            return; // Precisa do CPF para enviar
        }

        $origem_evento_id = 'SAUDE-FARM-' . $request->id;

        $estabelecimento = 'Farmácia Central';
        $servico_utilizado = 'Retirada de medicamento';
        
        $dados_adicionais = [
            'medicamento_receitado' => $request->medication_name,
        ];
        
        if ($request->equivalent_medication_name) {
            $dados_adicionais['medicamento_entregue'] = $request->equivalent_medication_name;
        }

        $data_hora = $request->dispensed_at ?? $request->created_at;

        $tipo_evento = null;
        $status_evento = null;
        $descricao = null;

        // "recepcao validada" does not map to events mentioned by user, but let's just observe what user said: 
        // "Retirada de medicamento" -> "retirada_medicamento", status "retirado".
        switch ($request->status) {
            case 'DISPENSADO':
            case 'DISPENSADO_EQUIVALENTE':
                $tipo_evento = 'retirada_medicamento';
                $status_evento = 'retirado';
                $descricao = 'Medicamento entregue ao paciente.';
                $data_hora = $request->dispensed_at ?? now();
                break;
        }

        if ($tipo_evento && $status_evento) {
            $this->govAssaiService->dispatchEvent(
                origem_evento_id: $origem_evento_id,
                cpf: $cpf,
                tipo_evento: $tipo_evento,
                status_evento: $status_evento,
                servico_utilizado: $servico_utilizado,
                data_hora: $data_hora,
                estabelecimento: $estabelecimento,
                descricao: $descricao,
                dados_adicionais: $dados_adicionais
            );
        }
    }
}
