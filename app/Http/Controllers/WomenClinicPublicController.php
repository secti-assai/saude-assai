<?php

namespace App\Http\Controllers;

use App\Models\Citizen;
use App\Models\WomenClinicAppointment;
use App\Services\AuditService;
use App\Services\GovAssaiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WomenClinicPublicController extends Controller
{
    public function __construct(
        private readonly GovAssaiService $govAssai,
        private readonly AuditService $audit,
    ) {
    }

    public function cancel(Request $request, WomenClinicAppointment $womenClinicAppointment): View|RedirectResponse
    {
        $womenClinicAppointment->loadMissing('citizen');

        if ($request->isMethod('get')) {
            return view('women-clinic.public-cancel', [
                'appointment' => $womenClinicAppointment,
            ]);
        }

        $data = $request->validate([
            'cpf' => ['required', 'string'],
            'birth_date' => ['required', 'date'],
        ]);

        if ($womenClinicAppointment->status !== 'AGENDADO') {
            return back()->withErrors(['status' => 'Esta consulta nao esta mais disponivel para cancelamento.'])->withInput();
        }

        if ($womenClinicAppointment->scheduled_for !== null && $womenClinicAppointment->scheduled_for->lte(now())) {
            return back()->withErrors(['status' => 'O prazo para cancelamento desta consulta ja expirou.'])->withInput();
        }

        $citizen = $womenClinicAppointment->citizen;
        if (! $citizen || ! $this->matchesCitizenIdentity($citizen, (string) $data['cpf'], (string) $data['birth_date'])) {
            return back()->withErrors(['cpf' => 'Nao foi possivel validar CPF e data de nascimento para este agendamento.'])->withInput();
        }

        $womenClinicAppointment->update([
            'status' => 'CANCELADO',
            'cancelled_at' => now(),
        ]);

        $this->audit->log($request, 'MULHER', 'CANCELAMENTO_LINK_CIDADAO', WomenClinicAppointment::class, null, [
            'appointment_id' => $womenClinicAppointment->id,
            'citizen_id' => $womenClinicAppointment->citizen_id,
        ]);

        return back()->with('status', 'Consulta cancelada com sucesso.');
    }

    public function feedback(Request $request, WomenClinicAppointment $womenClinicAppointment): View|RedirectResponse
    {
        $womenClinicAppointment->loadMissing('citizen');

        if ($request->isMethod('get')) {
            return view('women-clinic.public-feedback', [
                'appointment' => $womenClinicAppointment,
            ]);
        }

        $data = $request->validate([
            'cpf' => ['required', 'string'],
            'birth_date' => ['required', 'date'],
            'feedback_score' => ['required', 'integer', 'min:1', 'max:5'],
            'feedback_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($womenClinicAppointment->status !== 'FINALIZADO') {
            return back()->withErrors(['status' => 'A avaliacao so pode ser enviada apos o check-out da consulta.'])->withInput();
        }

        if ($womenClinicAppointment->feedback_submitted_at !== null) {
            return back()->withErrors(['status' => 'Avaliacao ja registrada para esta consulta.']);
        }

        $citizen = $womenClinicAppointment->citizen;
        if (! $citizen || ! $this->matchesCitizenIdentity($citizen, (string) $data['cpf'], (string) $data['birth_date'])) {
            return back()->withErrors(['cpf' => 'Nao foi possivel validar CPF e data de nascimento para esta avaliacao.'])->withInput();
        }

        $womenClinicAppointment->update([
            'feedback_score' => (int) $data['feedback_score'],
            'feedback_comment' => trim((string) ($data['feedback_comment'] ?? '')) ?: null,
            'feedback_submitted_at' => now(),
        ]);

        $this->audit->log($request, 'MULHER', 'AVALIACAO_LINK_CIDADAO', WomenClinicAppointment::class, null, [
            'appointment_id' => $womenClinicAppointment->id,
            'citizen_id' => $womenClinicAppointment->citizen_id,
            'feedback_score' => (int) $data['feedback_score'],
        ]);

        return back()->with('status', 'Avaliacao registrada com sucesso. Obrigado pelo retorno.');
    }

    private function matchesCitizenIdentity(Citizen $citizen, string $cpf, string $birthDate): bool
    {
        $inputCpf = $this->govAssai->normalizeCpf($cpf);
        $citizenCpf = $this->govAssai->normalizeCpf((string) $citizen->cpf);
        $citizenBirth = $citizen->birth_date?->format('Y-m-d');

        return $inputCpf !== ''
            && $citizenCpf !== ''
            && hash_equals($citizenCpf, $inputCpf)
            && $citizenBirth === $birthDate;
    }
}
