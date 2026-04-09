<?php

namespace App\Http\Controllers;

use App\Jobs\SendWomenClinicLifecycleNotificationJob;
use App\Models\User;
use App\Models\WomenClinicAppointment;
use App\Services\AuditService;
use App\Services\CitizenEligibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PoliclinicaController extends Controller
{
    public function __construct(
        private readonly CitizenEligibilityService $eligibility,
        private readonly AuditService $audit,
    ) {
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
            ->where('clinic_type', WomenClinicAppointment::CLINIC_POLICLINICA)
            ->whereDate('scheduled_for', '>=', $dateStart)
            ->whereDate('scheduled_for', '<=', $dateEnd);

        if ($statusFilter !== 'TODOS') {
            $appointmentsQuery->where('status', $statusFilter);
        }

        $appointments = $appointmentsQuery
            ->orderBy('scheduled_for')
            ->limit(100)
            ->get();

        return view('policlinica.recepcao', [
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
            ->where('clinic_type', WomenClinicAppointment::CLINIC_POLICLINICA)
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
                    ? route('policlinica.check-in', $appointment)
                    : null,
            ])->values(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function medicoArea(Request $request): View
    {
        $doctorSpecialty = $this->resolveDoctorSpecialtyForCheckout($request);

        $appointmentsQuery = WomenClinicAppointment::with(['citizen', 'reception'])
            ->where('clinic_type', WomenClinicAppointment::CLINIC_POLICLINICA)
            ->where('status', 'CHECKIN')
            ->orderBy('checked_in_at');

        if ($doctorSpecialty !== null) {
            $appointmentsQuery->where(function ($query) use ($doctorSpecialty): void {
                $query
                    ->where('specialty', $doctorSpecialty)
                    ->orWhereNull('specialty')
                    ->orWhereNotIn('specialty', WomenClinicAppointment::specialtyValuesForClinic(WomenClinicAppointment::CLINIC_POLICLINICA));
            });
        }

        $appointments = $appointmentsQuery
            ->limit(50)
            ->get();

        return view('policlinica.medico', [
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
            ->where('clinic_type', WomenClinicAppointment::CLINIC_POLICLINICA)
            ->where('status', 'CHECKIN')
            ->orderBy('checked_in_at');

        if ($doctorSpecialty !== null) {
            $appointmentsQuery->where(function ($query) use ($doctorSpecialty): void {
                $query
                    ->where('specialty', $doctorSpecialty)
                    ->orWhereNull('specialty')
                    ->orWhereNotIn('specialty', WomenClinicAppointment::specialtyValuesForClinic(WomenClinicAppointment::CLINIC_POLICLINICA));
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
                'check_out_url' => route('policlinica.check-out', $appointment),
            ])->values(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function checkIn(Request $request, WomenClinicAppointment $womenClinicAppointment): RedirectResponse
    {
        if (! $this->isPoliclinicaAppointment($womenClinicAppointment)) {
            return back()->withErrors(['status' => 'Este agendamento pertence a outro modulo de clinica.']);
        }

        if ($womenClinicAppointment->status !== 'AGENDADO') {
            return back()->withErrors(['status' => 'Somente consultas agendadas podem receber check-in.']);
        }

        $validation = $this->eligibility->validateAndSync((string) $womenClinicAppointment->citizen->cpf);

        if (! $validation['eligible']) {
            $this->audit->log($request, 'POLICLINICA', 'CHECKIN_BLOQUEADO', WomenClinicAppointment::class, null, [
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

        $this->audit->log($request, 'POLICLINICA', 'CHECKIN_CONSULTA', WomenClinicAppointment::class, null, [
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
        if (! $this->isPoliclinicaAppointment($womenClinicAppointment)) {
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

        $this->audit->log($request, 'POLICLINICA', 'CHECKOUT_CONSULTA', WomenClinicAppointment::class, null, [
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
            || ! WomenClinicAppointment::isSpecialtyAllowedForClinic(WomenClinicAppointment::CLINIC_POLICLINICA, $doctorSpecialty)
        ) {
            abort(403, 'Perfil medico sem especialidade vinculada para a Policlínica. Contate o administrador.');
        }

        return $doctorSpecialty;
    }

    private function isPoliclinicaAppointment(WomenClinicAppointment $appointment): bool
    {
        return WomenClinicAppointment::normalizeClinicType($appointment->clinic_type) === WomenClinicAppointment::CLINIC_POLICLINICA;
    }
}
