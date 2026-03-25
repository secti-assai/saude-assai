<?php

namespace App\Http\Controllers;

use App\Jobs\SyncCitizenFromGovAssaiJob;
use App\Jobs\DispatchLediRecord;
use App\Models\Attendance;
use App\Models\Delivery;
use App\Models\LediQueue;
use App\Models\Medication;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Services\AuditService;
use App\Services\GovAssaiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PrescriptionController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly GovAssaiService $govAssai,
    )
    {
    }

    public function index(): View
    {
        $this->authorize('viewAny', Prescription::class);

        $user = auth()->user();
        $isCentral = in_array($user?->role, ['admin_secti', 'gestor', 'auditor'], true);

        $prescriptions = Prescription::with('citizen', 'attendance', 'items.medication')
            ->when(! $isCentral && $user?->health_unit_id, function ($query) use ($user) {
                $query->whereHas('attendance', fn ($q) => $q->where('health_unit_id', $user->health_unit_id));
            })
            ->latest()
            ->take(30)
            ->get();

        $attendances = Attendance::with('citizen')
            ->when(! $isCentral && $user?->health_unit_id, fn ($query) => $query->where('health_unit_id', $user->health_unit_id))
            ->latest()
            ->take(30)
            ->get();
        $medications = Medication::orderBy('name')->get();

        return view('prescriptions.index', compact('prescriptions', 'attendances', 'medications'));
    }

    public function searchAttendances(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Prescription::class);

        $term = trim((string) $request->query('q', ''));
        if ($term === '') {
            return response()->json(['success' => true, 'data' => []]);
        }

        $user = $request->user();
        $isCentral = in_array($user?->role, ['admin_secti', 'gestor', 'auditor'], true);
        $normalizedCpf = $this->govAssai->normalizeCpf($term);

        $attendances = Attendance::with('citizen')
            ->when(! $isCentral && $user?->health_unit_id, fn ($query) => $query->where('health_unit_id', $user->health_unit_id))
            ->where(function ($query) use ($term, $normalizedCpf) {
                $query->where('queue_password', 'ilike', '%'.$term.'%')
                    ->orWhereHas('citizen', function ($citizenQuery) use ($term, $normalizedCpf) {
                        $citizenQuery->where('full_name', 'ilike', '%'.$term.'%');

                        if (strlen($normalizedCpf) === 11) {
                            $citizenQuery->orWhere('cpf_hash', hash('sha256', $normalizedCpf));
                        }
                    });
            })
            ->latest()
            ->limit(12)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $attendances->map(function (Attendance $attendance) {
                return [
                    'id' => $attendance->id,
                    'queue_password' => $attendance->queue_password,
                    'care_type' => $attendance->care_type,
                    'status' => $attendance->status,
                    'arrived_at' => $attendance->arrived_at?->format('d/m/Y H:i'),
                    'citizen_name' => $attendance->citizen?->full_name,
                    'citizen_cpf' => $this->maskCpf($attendance->citizen?->cpf),
                ];
            })->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Prescription::class);

        $data = $request->validate([
            'attendance_id' => ['required', 'exists:attendances,id'],
            'delivery_type' => ['required', 'in:RETIRADA,ENTREGA'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medication_id' => ['nullable', 'exists:medications,id'],
            'items.*.new_medication_name' => ['nullable', 'string', 'max:255'],
            'items.*.new_medication_presentation' => ['nullable', 'string', 'max:120'],
            'items.*.new_medication_concentration' => ['nullable', 'string', 'max:120'],
            'items.*.dosage' => ['nullable', 'string'],
            'items.*.frequency' => ['nullable', 'string'],
            'items.*.administration_route' => ['nullable', 'string'],
            'items.*.duration_days' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        foreach ($data['items'] as $index => $item) {
            $hasMedicationId = ! empty($item['medication_id']);
            $hasNewMedication = trim((string) ($item['new_medication_name'] ?? '')) !== '';

            if (! $hasMedicationId && ! $hasNewMedication) {
                throw ValidationException::withMessages([
                    "items.$index.medication_id" => 'Selecione um medicamento existente ou informe um novo medicamento.',
                ]);
            }
        }

        $attendance = Attendance::with('citizen')->findOrFail((int) $data['attendance_id']);
        $this->authorize('prescribe', $attendance);

        if (! empty($attendance->citizen?->cpf)) {
            SyncCitizenFromGovAssaiJob::dispatch($attendance->citizen->id);
        }

        $prescription = Prescription::create([
            'attendance_id' => $attendance->id,
            'citizen_id' => $attendance->citizen_id,
            'doctor_user_id' => $request->user()?->id,
            'delivery_type' => $data['delivery_type'],
            'status' => 'ASSINADA',
            'notes' => $data['notes'] ?? null,
            'signed_at' => now(),
        ]);

        // Criar todos os itens da prescrição
        foreach ($data['items'] as $item) {
            $medicationId = $this->resolveMedicationId($item);

            PrescriptionItem::create([
                'prescription_id' => $prescription->id,
                'medication_id' => $medicationId,
                'dosage' => $item['dosage'] ?? null,
                'frequency' => $item['frequency'] ?? null,
                'administration_route' => $item['administration_route'] ?? 'VO',
                'duration_days' => (int) $item['duration_days'],
                'quantity' => (int) $item['quantity'],
            ]);
        }

        if ($data['delivery_type'] === 'ENTREGA') {
            $address = $attendance->citizen->address;

            if (($address === null || trim($address) === '') && ! empty($attendance->citizen->cpf)) {
                $govResult = $this->govAssai->fetchCitizenByCpf($attendance->citizen->cpf);
                $govAddress = data_get($govResult, 'data.endereco', []);
                $resolvedAddress = trim(implode(', ', array_filter([
                    $govAddress['logradouro'] ?? null,
                    $govAddress['numero'] ?? null,
                    $govAddress['bairro'] ?? null,
                    $govAddress['distrito'] ?? null,
                ], fn ($value) => $value !== null && $value !== '')));

                if ($resolvedAddress !== '') {
                    $address = $resolvedAddress;
                    $attendance->citizen->update(['address' => $resolvedAddress]);
                }
            }

            Delivery::create([
                'prescription_id' => $prescription->id,
                'status' => 'PENDENTE',
                'address' => $address,
            ]);
        }

        $queue = LediQueue::create([
            'resource_type' => Prescription::class,
            'resource_id' => $prescription->id,
            'ledger_type' => 'FichaProcedimento',
            'payload' => $prescription->load('items')->toArray(),
        ]);

        DispatchLediRecord::dispatch($queue->id);
        $this->audit->log($request, 'M5', 'PRESCRICAO_DIGITAL', Prescription::class, $prescription->id);

        return back()->with('status', 'Prescricao digital emitida com sucesso.');
    }

    private function resolveMedicationId(array $item): int
    {
        if (! empty($item['medication_id'])) {
            return (int) $item['medication_id'];
        }

        $name = trim((string) ($item['new_medication_name'] ?? ''));
        $presentation = trim((string) ($item['new_medication_presentation'] ?? ''));
        $concentration = trim((string) ($item['new_medication_concentration'] ?? ''));

        $medication = Medication::firstOrCreate(
            ['name' => $name],
            [
                'presentation' => $presentation !== '' ? $presentation : null,
                'concentration' => $concentration !== '' ? $concentration : null,
                'is_remume' => false,
            ]
        );

        return (int) $medication->id;
    }

    private function maskCpf(?string $cpf): ?string
    {
        if ($cpf === null || $cpf === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $cpf);
        if (strlen($digits) !== 11) {
            return $cpf;
        }

        return substr($digits, 0, 3).'.***.***-'.substr($digits, -2);
    }
}
