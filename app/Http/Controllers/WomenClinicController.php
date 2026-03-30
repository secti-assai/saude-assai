<?php

namespace App\Http\Controllers;

use App\Models\WomenClinicAppointment;
use App\Services\AuditService;
use App\Services\CitizenEligibilityService;
use App\Services\CitizenIdentityChallengeService;
use App\Services\GovAssaiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WomenClinicController extends Controller
{
    public function __construct(
        private readonly CitizenEligibilityService $eligibility,
        private readonly GovAssaiService $govAssai,
        private readonly CitizenIdentityChallengeService $identityChallenge,
        private readonly AuditService $audit,
    ) {
    }

    public function agendadorArea(): View
    {
        $appointments = WomenClinicAppointment::with(['citizen', 'scheduler', 'reception', 'doctor'])
            ->orderByDesc('scheduled_for')
            ->limit(50)
            ->get();

        return view('women-clinic.agendador', [
            'appointments' => $appointments,
            'flow' => session()->get('women_clinic.schedule_flow'),
        ]);
    }

    public function startScheduleFlow(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cpf' => ['required', 'string'],
        ]);

        $cpf = $this->govAssai->normalizeCpf($data['cpf']);
        $gov = $this->govAssai->fetchCitizenByCpf($cpf);

        if (! $gov['success'] || ! is_array($gov['data'])) {
            return back()->withErrors(['cpf' => $gov['message']]);
        }

        $challenge = $this->identityChallenge->createChallenge($gov['data'], 'women_clinic_schedule');

        session()->put('women_clinic.schedule_flow', [
            'cpf' => $cpf,
            'citizen_name' => (string) data_get($gov['data'], 'cidadao.nome', ''),
            'identity_verified' => false,
            'challenge' => $challenge,
        ]);

        return back()->with('status', 'CPF validado. Confirme a identidade no passo seguinte.');
    }

    public function verifyScheduleIdentity(Request $request): RedirectResponse
    {
        $flow = session()->get('women_clinic.schedule_flow');

        if (! is_array($flow) || ! isset($flow['cpf'], $flow['challenge']['token'])) {
            return back()->withErrors(['cpf' => 'Inicie novamente o fluxo de CPF.']);
        }

        $data = $request->validate([
            'answer' => ['required', 'string', 'max:50'],
        ]);

        $ok = $this->identityChallenge->verify('women_clinic_schedule', (string) $flow['challenge']['token'], $data['answer']);

        if (! $ok) {
            return back()->withErrors(['answer' => 'Dado de confirmacao invalido.']);
        }

        $flow['identity_verified'] = true;
        session()->put('women_clinic.schedule_flow', $flow);
        $this->identityChallenge->markVerified('women_clinic_schedule', (string) $flow['cpf']);

        return back()->with('status', 'Identidade confirmada. Complete o agendamento no passo final.');
    }

    public function recepcaoArea(): View
    {
        $appointments = WomenClinicAppointment::with(['citizen', 'scheduler'])
            ->whereIn('status', ['AGENDADO', 'CHECKIN'])
            ->orderBy('scheduled_for')
            ->limit(50)
            ->get();

        return view('women-clinic.recepcao', [
            'appointments' => $appointments,
        ]);
    }

    public function medicoArea(): View
    {
        $appointments = WomenClinicAppointment::with(['citizen', 'reception'])
            ->where('status', 'CHECKIN')
            ->orderBy('checked_in_at')
            ->limit(50)
            ->get();

        return view('women-clinic.medico', [
            'appointments' => $appointments,
        ]);
    }

    public function schedule(Request $request): RedirectResponse
    {
        $flow = session()->get('women_clinic.schedule_flow');

        if (! is_array($flow) || ! isset($flow['cpf'])) {
            return back()->withErrors(['cpf' => 'Fluxo de CPF nao iniciado.']);
        }

        if (! $this->identityChallenge->isVerified('women_clinic_schedule', (string) $flow['cpf'])) {
            return back()->withErrors(['cpf' => 'Confirme a identidade do cidadao antes de finalizar.']);
        }

        $data = $request->validate([
            'scheduled_for' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validation = $this->eligibility->validateAndSync((string) $flow['cpf']);

        if (! $validation['eligible'] || ! $validation['citizen']) {
            $this->audit->log($request, 'MULHER', 'AGENDAMENTO_BLOQUEADO', WomenClinicAppointment::class, null, [
                'cpf' => $validation['cpf'],
                'error_code' => $validation['error_code'],
                'residence_status' => $validation['residence_status'],
                'gov_assai_level' => $validation['gov_assai_level'],
            ]);

            return back()->withErrors(['cpf' => $validation['message']])->withInput();
        }

        $appointment = WomenClinicAppointment::create([
            'citizen_id' => $validation['citizen']->id,
            'scheduler_user_id' => (int) $request->user()->id,
            'scheduled_for' => $data['scheduled_for'],
            'gov_assai_level' => $validation['gov_assai_level'],
            'residence_status' => $validation['residence_status'],
            'status' => 'AGENDADO',
            'notes' => $data['notes'] ?? null,
        ]);

        $this->audit->log($request, 'MULHER', 'AGENDAR_CONSULTA', WomenClinicAppointment::class, null, [
            'appointment_id' => $appointment->id,
            'citizen_id' => $appointment->citizen_id,
            'scheduled_for' => $appointment->scheduled_for?->toIso8601String(),
        ]);

        $this->identityChallenge->clear('women_clinic_schedule');
        $this->identityChallenge->consumeVerified('women_clinic_schedule');
        session()->forget('women_clinic.schedule_flow');

        return back()->with('status', 'Consulta da Clinica da Mulher agendada com sucesso.');
    }

    public function checkIn(Request $request, WomenClinicAppointment $womenClinicAppointment): RedirectResponse
    {
        if ($womenClinicAppointment->status !== 'AGENDADO') {
            return back()->withErrors(['status' => 'Somente consultas agendadas podem receber check-in.']);
        }

        $validation = $this->eligibility->validateAndSync((string) $womenClinicAppointment->citizen->cpf);

        if (! $validation['eligible']) {
            $this->audit->log($request, 'MULHER', 'CHECKIN_BLOQUEADO', WomenClinicAppointment::class, null, [
                'appointment_id' => $womenClinicAppointment->id,
                'cpf' => $validation['cpf'],
                'error_code' => $validation['error_code'],
                'residence_status' => $validation['residence_status'],
                'gov_assai_level' => $validation['gov_assai_level'],
            ]);

            return back()->withErrors(['status' => $validation['message']]);
        }

        $womenClinicAppointment->update([
            'reception_user_id' => (int) $request->user()->id,
            'status' => 'CHECKIN',
            'residence_status' => $validation['residence_status'],
            'gov_assai_level' => $validation['gov_assai_level'],
            'checked_in_at' => now(),
        ]);

        $this->audit->log($request, 'MULHER', 'CHECKIN_CONSULTA', WomenClinicAppointment::class, null, [
            'appointment_id' => $womenClinicAppointment->id,
        ]);

        return back()->with('status', 'Check-in realizado. Cidadao liberado para aguardar consulta.');
    }

    public function checkOut(Request $request, WomenClinicAppointment $womenClinicAppointment): RedirectResponse
    {
        if ($womenClinicAppointment->status !== 'CHECKIN') {
            return back()->withErrors(['status' => 'Somente consultas em check-in podem ser finalizadas.']);
        }

        $womenClinicAppointment->update([
            'doctor_user_id' => (int) $request->user()->id,
            'status' => 'FINALIZADO',
            'checked_out_at' => now(),
        ]);

        $this->audit->log($request, 'MULHER', 'CHECKOUT_CONSULTA', WomenClinicAppointment::class, null, [
            'appointment_id' => $womenClinicAppointment->id,
        ]);

        return back()->with('status', 'Consulta finalizada com check-out medico.');
    }
}
