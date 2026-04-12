<?php

namespace App\Services;

use App\Models\GovAssaiEventoQueue;
use App\Jobs\SendEventToGovAssaiJob;
use Carbon\Carbon;

class GovAssaiIntegrationService
{
    /**
     * Dispatch an event to Gov.Assai. It queues the event in the database,
     * and dispatches a job to process it.
     */
    public function dispatchEvent(
        string $origem_evento_id,
        string $cpf,
        string $tipo_evento,
        string $status_evento,
        string $servico_utilizado,
        \DateTimeInterface $data_hora,
        ?string $estabelecimento = null,
        ?string $descricao = null,
        ?array $dados_adicionais = null
    ): GovAssaiEventoQueue {
        $cleanCpf = preg_replace('/[^0-9]/', '', $cpf);

        $payload = [
            'cpf' => $cleanCpf,
            'origem_evento_id' => $origem_evento_id,
            'tipo_evento' => $tipo_evento,
            'status_evento' => $status_evento,
            'servico_utilizado' => $servico_utilizado,
            'data_hora' => $data_hora->format('Y-m-d H:i:s'),
        ];

        if ($estabelecimento) {
            $payload['estabelecimento'] = $estabelecimento;
        }

        if ($descricao) {
            $payload['descricao'] = $descricao;
        }

        if (!empty($dados_adicionais)) {
            $payload['dados_adicionais'] = $dados_adicionais;
        }

        // Use updateOrCreate for idempotency in the local queue
        $queueItem = GovAssaiEventoQueue::updateOrCreate(
            ['origem_evento_id' => $origem_evento_id],
            [
                'cpf' => $cleanCpf,
                'tipo_evento' => $tipo_evento,
                'status_evento' => $status_evento,
                'servico_utilizado' => $servico_utilizado,
                'estabelecimento' => $estabelecimento,
                'data_hora' => $data_hora,
                'descricao' => $descricao,
                'dados_adicionais' => $dados_adicionais,
                'payload_json' => $payload,
                'status_envio' => 'pendente',
            ]
        );

        // Dispatch job to send
        SendEventToGovAssaiJob::dispatch($queueItem->id);

        return $queueItem;
    }
}