<?php

namespace App\Http\Controllers;

use App\Jobs\SyncCitizenFromGovAssaiJob;
use App\Jobs\DispatchLediRecord;
use App\Models\Attendance;
use App\Models\Citizen;
use App\Models\HealthUnit;
use App\Models\LediQueue;
use App\Services\AuditService;
use App\Services\GovAssaiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReceptionController extends Controller
{
    public function __construct(
        private readonly GovAssaiService $govAssai,
        private readonly AuditService $audit
    ) {
    }

    public function index(): View
    {
        $this->authorize('viewAny', Attendance::class);

        $user = auth()->user();
        $isCentral = in_array($user?->role, ['admin_secti', 'gestor', 'auditor'], true);

        $attendances = Attendance::with('citizen')
            ->when(! $isCentral && $user?->health_unit_id, fn ($query) => $query->where('health_unit_id', $user->health_unit_id))
            ->latest()
            ->take(20)
            ->get();

        $units = HealthUnit::where('is_active', true)
            ->when(! $isCentral && $user?->health_unit_id, fn ($query) => $query->where('id', $user->health_unit_id))
            ->orderBy('name')
            ->get();

        return view('reception.index', compact('attendances', 'units'));
    }

    public function lookupCitizenByCpf(Request $request, string $cpf): JsonResponse
    {
        if (! $this->govAssai->isValidCpfFormat($cpf)) {
            return response()->json([
                'success' => false,
                'message' => 'CPF invalido. Informe 11 digitos ou formato 000.000.000-00.',
                'error_code' => 'INVALID_CPF_FORMAT',
            ], 400);
        }

        $result = $this->govAssai->fetchCitizenByCpf($cpf);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['error_code'],
            ], $result['status']);
        }

        $name = trim((string) data_get($result, 'data.cidadao.nome', ''));
        $birthDate = data_get($result, 'data.cidadao.data_nascimento');

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data'],
            'requires_manual_fields' => $name === '' || $birthDate === null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Attendance::class);

        $data = $request->validate([
            'cpf' => ['required', 'regex:/^(\d{11}|\d{3}\.\d{3}\.\d{3}-\d{2})$/'],
            'cns' => ['nullable', 'string', 'max:15'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'care_type' => ['required', 'string'],
            'summary_reason' => ['nullable', 'string', 'max:200'],
            'health_unit_id' => ['required', 'exists:health_units,id'],
            'work_accident' => ['nullable', 'boolean'],
        ]);

        $data['cpf'] = $this->govAssai->normalizeCpf($data['cpf']);

        $govResult = $this->govAssai->fetchCitizenByCpf($data['cpf']);

        if (! $govResult['success']) {
            $isNotResident = $govResult['status'] === 404;
            $message = $isNotResident
                ? 'CPF nao localizado no Gov.Assai. Para fluxos de recepcao, o atendimento so pode prosseguir com validacao positiva no Gov.Assai.'
                : 'Nao foi possivel validar o CPF no Gov.Assai no momento. Tente novamente para prosseguir com a recepcao.';

            $this->audit->log(
                $request,
                'M3',
                'RECEPCAO_BLOQUEADA_GOV_ASSAI',
                Citizen::class,
                null,
                [
                    'cpf' => $data['cpf'],
                    'status' => $govResult['status'],
                    'error_code' => $govResult['error_code'],
                ]
            );

            return back()
                ->withErrors(['cpf' => $message])
                ->withInput();
        }

        $govCitizen = data_get($govResult, 'data.cidadao', []);
        $govHealth = data_get($govResult, 'data.saude', []);
        $govAddress = data_get($govResult, 'data.endereco', []);

        $resolvedName = trim((string) (($govCitizen['nome'] ?? null) ?: ($data['full_name'] ?? '')));
        $resolvedBirthDate = ($govCitizen['data_nascimento'] ?? null) ?: ($data['birth_date'] ?? null);
        $resolvedCns = ($govHealth['cns_numero'] ?? null) ?: ($data['cns'] ?? null);
        $resolvedAddress = trim(implode(', ', array_filter([
            $govAddress['logradouro'] ?? null,
            $govAddress['numero'] ?? null,
            $govAddress['bairro'] ?? null,
            $govAddress['distrito'] ?? null,
        ], fn ($value) => $value !== null && $value !== '')));
        $cpfHash = hash('sha256', $data['cpf']);

        if ($resolvedName === '' || $resolvedBirthDate === null) {
            return back()
                ->withErrors([
                    'full_name' => 'Gov.Assai nao retornou todos os dados obrigatorios. Informe nome completo e data de nascimento para continuar.',
                ])
                ->withInput();
        }

        $citizen = Citizen::updateOrCreate(
            ['cpf_hash' => $cpfHash],
            [
                'full_name' => $resolvedName,
                'cpf' => $data['cpf'],
                'cpf_hash' => $cpfHash,
                'birth_date' => $resolvedBirthDate,
                'cns' => $resolvedCns,
                'address' => $resolvedAddress !== '' ? $resolvedAddress : null,
                'is_resident_assai' => true,
                'residence_validated_at' => now(),
            ]
        );

        SyncCitizenFromGovAssaiJob::dispatch($citizen->id);

        $attendance = Attendance::create([
            'citizen_id' => $citizen->id,
            'health_unit_id' => in_array($request->user()?->role, ['admin_secti', 'gestor', 'auditor'], true)
                ? (int) $data['health_unit_id']
                : (int) ($request->user()?->health_unit_id ?? $data['health_unit_id']),
            'reception_user_id' => $request->user()?->id,
            'care_type' => $data['care_type'],
            'queue_password' => 'A'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
            'residence_status' => 'RESIDENTE',
            'summary_reason' => $data['summary_reason'] ?? null,
            'work_accident' => (bool) ($data['work_accident'] ?? false),
            'status' => 'RECEPCAO',
            'arrived_at' => now(),
        ]);

        $queue = LediQueue::create([
            'resource_type' => Attendance::class,
            'resource_id' => $attendance->id,
            'ledger_type' => 'FichaAtendimentoIndividual',
            'payload' => $attendance->toArray(),
        ]);

        DispatchLediRecord::dispatch($queue->id);
        $this->audit->log($request, 'M3', 'RECEPCIONAR_PACIENTE', Attendance::class, $attendance->id);

        return back()->with('status', 'Paciente recepcionado e enviado para fila digital.');
    }
}
