<?php

namespace App\Observers;

use App\Models\WomenClinicAppointment;
use App\Services\GovAssaiIntegrationService;

class WomenClinicAppointmentObserver
{
    public function __construct(private readonly GovAssaiIntegrationService $govAssaiService)
    {
    }

    /**
     * Handle the WomenClinicAppointment "created" event.
     */
    public function created(WomenClinicAppointment $appointment): void
    {
        $this->syncWithGovAssai($appointment);
    }

    /**
     * Handle the WomenClinicAppointment "updated" event.
     */
    public function updated(WomenClinicAppointment $appointment): void
    {
        if ($appointment->isDirty('status') || $appointment->isDirty('scheduled_for')) {
            $this->syncWithGovAssai($appointment);
        }
    }

    private function syncWithGovAssai(WomenClinicAppointment $appointment): void
    {
        $appointment->loadMissing('citizen');
        
        $cpf = $appointment->citizen?->cpf;
        if (!$cpf) {
            return; // Precisa do CPF para enviar
        }

        $origem_evento_id = 'SAUDE-CM-' . $appointment->id;

        $estabelecimento = WomenClinicAppointment::clinicLabel($appointment->clinic_type);
        $servico_utilizado = 'Consulta na ' . $estabelecimento;
        
        $dados_adicionais = [];
        if ($appointment->specialty) {
            $dados_adicionais['especialidade'] = mb_strtolower(WomenClinicAppointment::specialtyLabel($appointment->specialty));
        }

        $data_hora = $appointment->scheduled_for ?? $appointment->created_at;

        // Mapeamento de status e evento
        $tipo_evento = null;
        $status_evento = null;
        $descricao = null;

        switch ($appointment->status) {
            case 'AGENDADO':
            case 'CHECKIN':
                $tipo_evento = 'agendamento_consulta';
                $status_evento = 'agendado';
                $descricao = 'Consulta agendada.';
                break;
            case 'CANCELADO':
                $tipo_evento = 'cancelamento_consulta';
                $status_evento = 'cancelado';
                $descricao = 'Consulta cancelada.';
                $data_hora = $appointment->cancelled_at ?? $data_hora;
                break;
            case 'FINALIZADO':
                $tipo_evento = 'consulta_realizada';
                $status_evento = 'finalizado';
                $descricao = 'Atendimento concluído.';
                $data_hora = $appointment->checked_out_at ?? $data_hora;
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
