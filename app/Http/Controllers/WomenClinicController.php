<?php

namespace App\Http\Controllers;

use App\Jobs\SendWomenClinicLifecycleNotificationJob;
use App\Models\User;
use App\Models\WomenClinicAppointment;
use App\Services\AuditService;
use App\Services\CitizenEligibilityService;
use App\Services\CitizenIdentityChallengeService;
use App\Services\GovAssaiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function agendadorArea(Request $request): View
    {
        $today = now()->toDateString();

        $dateStart = trim((string) $request->query('date_start', $today));
        $dateEnd = trim((string) $request->query('date_end', $today));

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStart)) {
            $dateStart = $today;
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateEnd)) {
            $dateEnd = $today;
        }

        if ($dateStart > $dateEnd) {
            [$dateStart, $dateEnd] = [$dateEnd, $dateStart];
        }

        $statusOptions = [
            'AGENDADO' => 'Agendado',
            'CHECKIN' => 'Check-in',
            'FINALIZADO' => 'Finalizado',
        ];

        $dynamicStatuses = WomenClinicAppointment::query()
            ->select('status')
            ->distinct()
            ->pluck('status')
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => strtoupper(trim($value)))
            ->reject(fn (string $value): bool => isset($statusOptions[$value]))
            ->values();

        foreach ($dynamicStatuses as $dynamicStatus) {
            $statusOptions[$dynamicStatus] = ucwords(strtolower(str_replace('_', ' ', $dynamicStatus)));
        }

        $statusOptions['TODOS'] = 'Todos os status';

        $statusFilter = strtoupper(trim((string) $request->query('status', 'AGENDADO')));

        if (! isset($statusOptions[$statusFilter])) {
            $statusFilter = 'AGENDADO';
        }

        $clinicOptions = WomenClinicAppointment::clinicOptions();
        $clinicFilterOptions = ['TODOS' => 'Todas as clinicas'] + $clinicOptions;
        $clinicFilterInput = trim((string) $request->query('clinic_type', 'TODOS'));
        $clinicFilter = $clinicFilterInput === 'TODOS'
            ? 'TODOS'
            : (WomenClinicAppointment::normalizeClinicType($clinicFilterInput) ?? 'TODOS');

        $specialtyOptions = WomenClinicAppointment::specialtyOptions();
        $specialtyFilterOptions = ['TODOS' => 'Todas as especialidades'] + $specialtyOptions;
        $specialtyFilter = strtoupper(trim((string) $request->query('specialty', 'TODOS')));

        if (! isset($specialtyFilterOptions[$specialtyFilter])) {
            $specialtyFilter = 'TODOS';
        }

        $appointmentsQuery = WomenClinicAppointment::with(['citizen', 'scheduler', 'reception', 'doctor'])
            ->whereDate('scheduled_for', '>=', $dateStart)
            ->whereDate('scheduled_for', '<=', $dateEnd);

        if ($clinicFilter === WomenClinicAppointment::CLINIC_WOMEN) {
            $appointmentsQuery->where(function ($query): void {
                $query
                    ->where('clinic_type', WomenClinicAppointment::CLINIC_WOMEN)
                    ->orWhereNull('clinic_type');
            });
        }

        if ($clinicFilter === WomenClinicAppointment::CLINIC_POLICLINICA) {
            $appointmentsQuery->where('clinic_type', WomenClinicAppointment::CLINIC_POLICLINICA);
        }

        if ($statusFilter !== 'TODOS') {
            $appointmentsQuery->where('status', $statusFilter);
        }

        if ($specialtyFilter !== 'TODOS') {
            $appointmentsQuery->where('specialty', $specialtyFilter);
        }

        $appointments = $appointmentsQuery
            ->orderBy('scheduled_for')
            ->limit(100)
            ->get();

        return view('women-clinic.agendador', [
            'appointments' => $appointments,
            'flow' => session()->get('women_clinic.schedule_flow'),
            'filters' => [
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'status' => $statusFilter,
                'clinic_type' => $clinicFilter,
                'specialty' => $specialtyFilter,
            ],
            'clinicOptions' => $clinicOptions,
            'clinicFilterOptions' => $clinicFilterOptions,
            'statusOptions' => $statusOptions,
            'clinicSpecialtyOptions' => $specialtyOptions,
            'specialtiesByClinic' => WomenClinicAppointment::specialtyOptionsByClinic(),
            'specialtyFilterOptions' => $specialtyFilterOptions,
        ]);
    }

    public function startScheduleFlow(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cpf' => ['required', 'string'],
        ]);

        $cpf = $this->govAssai->normalizeCpf($data['cpf']);
        $validation = $this->eligibility->validateAndSync($cpf);

        if (! $validation['eligible']) {
            return back()->withErrors(['cpf' => $validation['message']])->withInput();
        }

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

    public function cancelScheduleFlow(): RedirectResponse
    {
        $this->identityChallenge->clear('women_clinic_schedule');
        session()->forget('women_clinic.schedule_flow');

        return redirect()->route('clinic-scheduler.index')->with('status', 'Agendamento cancelado. Voce pode iniciar a consulta de um novo cidadao.');
    }

    public function recepcaoArea(Request $request): View
    {
        $today = now()->toDateString();

        $dateStart = trim((string) $request->query('date_start', $today));
        $dateEnd = trim((string) $request->query('date_end', $today));

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStart)) {
            $dateStart = $today;
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateEnd)) {
            $dateEnd = $today;
        }

        if ($dateStart > $dateEnd) {
            [$dateStart, $dateEnd] = [$dateEnd, $dateStart];
        }

        $statusOptions = [
            'AGENDADO' => 'Agendado',
            'CHECKIN' => 'Check-in',
            'FINALIZADO' => 'Finalizado',
        ];

        $dynamicStatuses = WomenClinicAppointment::query()
            ->select('status')
            ->distinct()
            ->pluck('status')
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => strtoupper(trim($value)))
            ->reject(fn (string $value): bool => isset($statusOptions[$value]))
            ->values();

        foreach ($dynamicStatuses as $dynamicStatus) {
            $statusOptions[$dynamicStatus] = ucwords(strtolower(str_replace('_', ' ', $dynamicStatus)));
        }

        $statusOptions['TODOS'] = 'Todos os status';

        $statusFilter = strtoupper(trim((string) $request->query('status', 'TODOS')));

        if (! isset($statusOptions[$statusFilter])) {
            $statusFilter = 'TODOS';
        }

        $appointmentsQuery = WomenClinicAppointment::with(['citizen', 'scheduler'])
            ->where(function ($query): void {
                $query
                    ->where('clinic_type', WomenClinicAppointment::CLINIC_WOMEN)
                    ->orWhereNull('clinic_type');
            })
            ->whereDate('scheduled_for', '>=', $dateStart)
            ->whereDate('scheduled_for', '<=', $dateEnd);

        if ($statusFilter !== 'TODOS') {
            $appointmentsQuery->where('status', $statusFilter);
        }

        $appointments = $appointmentsQuery
            ->orderBy('scheduled_for')
            ->limit(100)
            ->get();

        return view('women-clinic.recepcao', [
            'appointments' => $appointments,
            'filters' => [
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'status' => $statusFilter,
            ],
            'statusOptions' => $statusOptions,
        ]);
    }

    public function recepcaoData(Request $request): JsonResponse
    {
        $today = now()->toDateString();

        $dateStart = trim((string) $request->query('date_start', $today));
        $dateEnd = trim((string) $request->query('date_end', $today));

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStart)) {
            $dateStart = $today;
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateEnd)) {
            $dateEnd = $today;
        }

        if ($dateStart > $dateEnd) {
            [$dateStart, $dateEnd] = [$dateEnd, $dateStart];
        }

        $statusOptions = [
            'AGENDADO' => 'Agendado',
            'CHECKIN' => 'Check-in',
            'FINALIZADO' => 'Finalizado',
        ];

        $dynamicStatuses = WomenClinicAppointment::query()
            ->select('status')
            ->distinct()
            ->pluck('status')
            ->filter(fn ($value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => strtoupper(trim($value)))
            ->reject(fn (string $value): bool => isset($statusOptions[$value]))
            ->values();

        foreach ($dynamicStatuses as $dynamicStatus) {
            $statusOptions[$dynamicStatus] = ucwords(strtolower(str_replace('_', ' ', $dynamicStatus)));
        }

        $statusOptions['TODOS'] = 'Todos os status';

        $statusFilter = strtoupper(trim((string) $request->query('status', 'TODOS')));

        if (! isset($statusOptions[$statusFilter])) {
            $statusFilter = 'TODOS';
        }

        $appointmentsQuery = WomenClinicAppointment::with(['citizen', 'scheduler'])
            ->where(function ($query): void {
                $query
                    ->where('clinic_type', WomenClinicAppointment::CLINIC_WOMEN)
                    ->orWhereNull('clinic_type');
            })
            ->whereDate('scheduled_for', '>=', $dateStart)
            ->whereDate('scheduled_for', '<=', $dateEnd);

        if ($statusFilter !== 'TODOS') {
            $appointmentsQuery->where('status', $statusFilter);
        }

        $appointments = $appointmentsQuery
            ->orderBy('scheduled_for')
            ->limit(100)
            ->get();

        return response()->json([
            'rows' => $appointments->map(fn (WomenClinicAppointment $appointment): array => [
                'id' => (string) $appointment->id,
                'scheduled_for' => $appointment->scheduled_for?->format('d/m/Y H:i') ?? '—',
                'citizen_name' => (string) ($appointment->citizen->full_name ?? '—'),
                'specialty_label' => WomenClinicAppointment::specialtyLabel($appointment->specialty),
                'status' => (string) $appointment->status,
                'check_in_url' => $appointment->status === 'AGENDADO'
                    ? route('women-clinic.check-in', $appointment)
                    : null,
            ])->values(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function medicoArea(Request $request): View
    {
        $doctorSpecialty = $this->resolveDoctorSpecialtyForCheckout($request);

        $appointmentsQuery = WomenClinicAppointment::with(['citizen', 'reception'])
            ->where('status', 'CHECKIN')
            ->where(function ($query): void {
                $query
                    ->where('clinic_type', WomenClinicAppointment::CLINIC_WOMEN)
                    ->orWhereNull('clinic_type');
            })
            ->orderBy('checked_in_at');

        if ($doctorSpecialty !== null) {
            $appointmentsQuery->where(function ($query) use ($doctorSpecialty): void {
                $query
                    ->where('specialty', $doctorSpecialty)
                    ->orWhereNull('specialty')
                    ->orWhereNotIn('specialty', WomenClinicAppointment::specialtyValuesForClinic(WomenClinicAppointment::CLINIC_WOMEN));
            });
        }

        $appointments = $appointmentsQuery
            ->limit(50)
            ->get();

        return view('women-clinic.medico', [
            'appointments' => $appointments,
            'doctorSpecialtyLabel' => $doctorSpecialty !== null
                ? WomenClinicAppointment::specialtyLabel($doctorSpecialty)
                : null,
        ]);
    }

    public function medicoData(Request $request): JsonResponse
    {
        $doctorSpecialty = $this->resolveDoctorSpecialtyForCheckout($request);

        $appointmentsQuery = WomenClinicAppointment::with(['citizen'])
            ->where('status', 'CHECKIN')
            ->where(function ($query): void {
                $query
                    ->where('clinic_type', WomenClinicAppointment::CLINIC_WOMEN)
                    ->orWhereNull('clinic_type');
            })
            ->orderBy('checked_in_at');

        if ($doctorSpecialty !== null) {
            $appointmentsQuery->where(function ($query) use ($doctorSpecialty): void {
                $query
                    ->where('specialty', $doctorSpecialty)
                    ->orWhereNull('specialty')
                    ->orWhereNotIn('specialty', WomenClinicAppointment::specialtyValuesForClinic(WomenClinicAppointment::CLINIC_WOMEN));
            });
        }

        $appointments = $appointmentsQuery
            ->limit(100)
            ->get();

        return response()->json([
            'rows' => $appointments->map(fn (WomenClinicAppointment $appointment): array => [
                'id' => (string) $appointment->id,
                'citizen_name' => (string) ($appointment->citizen->full_name ?? '—'),
                'specialty_label' => WomenClinicAppointment::specialtyLabel($appointment->specialty),
                'checked_in_at' => $appointment->checked_in_at?->format('d/m/Y H:i') ?? '—',
                'check_out_url' => route('women-clinic.check-out', $appointment),
            ])->values(),
            'generated_at' => now()->toIso8601String(),
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
            'clinic_type' => ['required', 'string', Rule::in(WomenClinicAppointment::clinicValues())],
            'specialty' => ['required', 'string', Rule::in(WomenClinicAppointment::specialtyValues())],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $clinicType = WomenClinicAppointment::resolveClinicType((string) ($data['clinic_type'] ?? ''));
        if (! WomenClinicAppointment::isSpecialtyAllowedForClinic($clinicType, (string) ($data['specialty'] ?? ''))) {
            return back()->withErrors([
                'specialty' => 'A especialidade selecionada nao pertence a clinica informada.',
            ])->withInput();
        }

        $specialty = WomenClinicAppointment::normalizeSpecialty((string) ($data['specialty'] ?? ''));
        if ($specialty === null) {
            return back()->withErrors([
                'specialty' => 'Especialidade invalida para o agendamento.',
            ])->withInput();
        }

        $validation = $this->eligibility->validateAndSync((string) $flow['cpf']);

        if (! $validation['eligible'] || ! $validation['citizen']) {
            $this->audit->log($request, $this->auditModuleForClinic($clinicType), 'AGENDAMENTO_BLOQUEADO', WomenClinicAppointment::class, null, [
                'cpf' => $validation['cpf'],
                'error_code' => $validation['error_code'],
                'residence_status' => $validation['residence_status'],
                'gov_assai_level' => $validation['gov_assai_level'],
                'clinic_type' => $clinicType,
            ]);

            return back()->withErrors(['cpf' => $validation['message']])->withInput();
        }

        // Validação de conflito de agenda (evita overbooking no mesmo dia/hora, clínica e especialidade)
        $scheduledFor = \Carbon\Carbon::parse($data['scheduled_for']);
        
        $conflict = WomenClinicAppointment::where('clinic_type', $clinicType)
            ->where('specialty', $specialty)
            ->where('scheduled_for', clone $scheduledFor)
            ->whereNotIn('status', ['CANCELADO'])
            ->exists();

        if ($conflict) {
            return back()->withErrors(['scheduled_for' => 'Este horário já está preenchido para esta especialidade. Selecione outro.'])->withInput();
        }

        $appointment = WomenClinicAppointment::create([
            'citizen_id' => $validation['citizen']->id,
            'scheduler_user_id' => (int) $request->user()->id,
            'scheduled_for' => $data['scheduled_for'],
            'clinic_type' => $clinicType,
            'specialty' => $specialty,
            'gov_assai_level' => $validation['gov_assai_level'],
            'residence_status' => $validation['residence_status'],
            'status' => 'AGENDADO',
            'notes' => $data['notes'] ?? null,
        ]);

        $this->audit->log($request, $this->auditModuleForClinic($clinicType), 'AGENDAR_CONSULTA', WomenClinicAppointment::class, null, [
            'appointment_id' => $appointment->id,
            'citizen_id' => $appointment->citizen_id,
            'scheduled_for' => $appointment->scheduled_for?->toIso8601String(),
            'clinic_type' => $appointment->clinic_type,
            'specialty' => $appointment->specialty,
        ]);

        SendWomenClinicLifecycleNotificationJob::dispatch(
            (string) $appointment->id,
            SendWomenClinicLifecycleNotificationJob::TRIGGER_SCHEDULED
        )->afterCommit();

        $reminderDispatch = SendWomenClinicLifecycleNotificationJob::dispatch(
            (string) $appointment->id,
            SendWomenClinicLifecycleNotificationJob::TRIGGER_REMINDER_24H
        )->afterCommit();

        $reminderAt = $appointment->scheduled_for?->copy()->subHours(24);
        if ($reminderAt !== null && $reminderAt->greaterThan(now())) {
            $reminderDispatch->delay($reminderAt);
        }

        $this->identityChallenge->clear('women_clinic_schedule');
        $this->identityChallenge->consumeVerified('women_clinic_schedule');
        session()->forget('women_clinic.schedule_flow');

        return back()->with('status', 'Consulta da '.WomenClinicAppointment::clinicLabel($clinicType).' agendada com sucesso.');
    }

    public function checkIn(Request $request, WomenClinicAppointment $womenClinicAppointment): RedirectResponse
    {
        if (! $this->isWomenClinicAppointment($womenClinicAppointment)) {
            return back()->withErrors(['status' => 'Este agendamento pertence a outro modulo de clinica.']);
        }

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

        SendWomenClinicLifecycleNotificationJob::dispatch(
            (string) $womenClinicAppointment->id,
            SendWomenClinicLifecycleNotificationJob::TRIGGER_CHECKIN
        )->afterCommit();

        return back()->with('status', 'Check-in realizado. Cidadao liberado para aguardar consulta.');
    }

    public function checkOut(Request $request, WomenClinicAppointment $womenClinicAppointment): RedirectResponse
    {
        if (! $this->isWomenClinicAppointment($womenClinicAppointment)) {
            return back()->withErrors(['status' => 'Este agendamento pertence a outro modulo de clinica.']);
        }

        if ($womenClinicAppointment->status !== 'CHECKIN') {
            return back()->withErrors(['status' => 'Somente consultas em check-in podem ser finalizadas.']);
        }

        $doctorSpecialty = $this->resolveDoctorSpecialtyForCheckout($request);
        $appointmentSpecialty = WomenClinicAppointment::normalizeSpecialty($womenClinicAppointment->specialty);

        if (
            $doctorSpecialty !== null
            && $appointmentSpecialty !== null
            && $appointmentSpecialty !== $doctorSpecialty
        ) {
            return back()->withErrors([
                'status' => 'Este medico pode finalizar apenas consultas da especialidade '.WomenClinicAppointment::specialtyLabel($doctorSpecialty).'.',
            ]);
        }

        $womenClinicAppointment->update([
            'doctor_user_id' => (int) $request->user()->id,
            'status' => 'FINALIZADO',
            'checked_out_at' => now(),
        ]);

        $this->audit->log($request, 'MULHER', 'CHECKOUT_CONSULTA', WomenClinicAppointment::class, null, [
            'appointment_id' => $womenClinicAppointment->id,
            'appointment_specialty' => $womenClinicAppointment->specialty,
            'doctor_specialty' => $doctorSpecialty,
        ]);

        SendWomenClinicLifecycleNotificationJob::dispatch(
            (string) $womenClinicAppointment->id,
            SendWomenClinicLifecycleNotificationJob::TRIGGER_CHECKOUT
        )->afterCommit();

        return back()->with('status', 'Consulta finalizada com check-out medico.');
    }

    private function resolveDoctorSpecialtyForCheckout(Request $request): ?string
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403, 'Usuario nao autenticado para check-out.');
        }

        if ($user->role === User::ROLE_ADMIN) {
            return null;
        }

        $doctorSpecialty = WomenClinicAppointment::normalizeSpecialty($user->clinic_specialty);

        if (
            $doctorSpecialty === null
            || ! WomenClinicAppointment::isSpecialtyAllowedForClinic(WomenClinicAppointment::CLINIC_WOMEN, $doctorSpecialty)
        ) {
            abort(403, 'Perfil medico sem especialidade vinculada. Contate o administrador.');
        }

        return $doctorSpecialty;
    }

    private function auditModuleForClinic(string $clinicType): string
    {
        return $clinicType === WomenClinicAppointment::CLINIC_POLICLINICA
            ? 'POLICLINICA'
            : 'MULHER';
    }

    private function isWomenClinicAppointment(WomenClinicAppointment $appointment): bool
    {
        $clinicType = WomenClinicAppointment::normalizeClinicType($appointment->clinic_type);

        return $clinicType === null || $clinicType === WomenClinicAppointment::CLINIC_WOMEN;
    }
}
