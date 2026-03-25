<?php

namespace App\Http\Controllers;

use App\Jobs\DispatchLediRecord;
use App\Models\Attendance;
use App\Models\Citizen;
use App\Models\HospitalRecord;
use App\Models\LediQueue;
use App\Models\Triage;
use App\Services\AuditService;
use App\Services\GovAssaiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HospitalController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly GovAssaiService $govAssai,
    )
    {
    }

    public function index(): View
    {
        // Carrega todos os cidadãos (MVP) e os registros do plantão atual.
        // Módulo 100% isolado (Não importa $attendance da Recepção/Triage UBS).
        $citizens = Citizen::orderBy('full_name')->get();
        
        $recentRecords = HospitalRecord::with('attendance.citizen')
            ->latest('signed_at')
            ->take(15)
            ->get();

        return view('hospital.index', compact('citizens', 'recentRecords'));
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

        $this->audit->log(
            $request,
            'M7',
            'CONSULTAR_CPF_GOV_ASSAI',
            Citizen::class,
            null,
            [
                'cpf' => $this->govAssai->normalizeCpf($cpf),
                'status' => $result['status'],
                'success' => $result['success'],
            ]
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data'],
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
            'error_code' => $result['error_code'],
        ], $result['status']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            // 1. Identificação do Paciente
            'citizen_id' => ['nullable', 'exists:citizens,id'],
            'new_citizen_name' => ['nullable', 'string', 'max:255', 'required_without_all:citizen_id,new_citizen_cpf'],
            'new_citizen_cpf' => ['required_without:citizen_id', 'nullable', 'string', 'regex:/^(\d{11}|\d{3}\.\d{3}\.\d{3}-\d{2})$/'],
            'new_citizen_birth' => ['nullable', 'date', 'required_without_all:citizen_id,new_citizen_cpf'],
            
            // 2. Sinais Vitais (Triagem Expressa)
            'systolic_pressure' => ['nullable', 'numeric'],
            'diastolic_pressure' => ['nullable', 'numeric'],
            'heart_rate' => ['nullable', 'numeric'],
            'temperature' => ['nullable', 'numeric'],
            'spo2' => ['nullable', 'numeric'],
            'hgt' => ['nullable', 'numeric'],
            'weight' => ['nullable', 'numeric'],
            
            // 3. Prontuário Médico (SOAP)
            'soap_objective' => ['required', 'string'],
            'soap_assessment' => ['required', 'string'],
            'diagnosis' => ['required', 'string'],
            'cid_10' => ['required', 'string', 'max:10'],
            'secondary_cids' => ['nullable', 'array', 'max:5'],
            'secondary_cids.*' => ['nullable', 'string', 'max:10'],
            'procedures' => ['nullable', 'string'],
            'exams' => ['nullable', 'string'],
            'guidance' => ['nullable', 'string'],
            'outcome' => ['required', 'in:ALTA,INTERNACAO,TRANSFERENCIA,OBITO'],
        ]);

        // Passo 1: Resolver Cidadão
        if (!empty($data['citizen_id'])) {
            $citizen = Citizen::find($data['citizen_id']);
        } else {
            $normalizedCpf = $this->govAssai->normalizeCpf($data['new_citizen_cpf']);
            $govLookup = $this->govAssai->fetchCitizenByCpf($normalizedCpf);
            $govCitizenData = $govLookup['success'] && is_array($govLookup['data'])
                ? $this->govAssai->mapCitizenDataForLocalCreate($govLookup['data'])
                : [];

            $resolvedName = trim((string) ($data['new_citizen_name'] ?? ($govCitizenData['name'] ?? '')));
            $resolvedBirthDate = $data['new_citizen_birth'] ?? ($govCitizenData['birth_date'] ?? null);

            if ($resolvedName === '' || $resolvedBirthDate === null) {
                return back()
                    ->withErrors([
                        'new_citizen_name' => 'Nao foi possivel preencher automaticamente. Informe nome e data de nascimento para continuar.',
                    ])
                    ->withInput();
            }

            $citizen = Citizen::create([
                'full_name' => $resolvedName,
                'cpf' => $normalizedCpf,
                'cpf_hash' => hash('sha256', $normalizedCpf),
                'birth_date' => $resolvedBirthDate,
                'social_name' => $govCitizenData['social_name'] ?? null,
                'sexo' => $govCitizenData['sexo'] ?? null,
                'phone' => $govCitizenData['phone'] ?? null,
                'email' => $govCitizenData['email'] ?? null,
                'cns' => $govCitizenData['cns'] ?? null,
                'is_resident_assai' => (bool) ($govCitizenData['is_resident_assai'] ?? false),
                'residence_validated_at' => now(),
            ]);
        }

        // Passo 2: Criar Atendimento Próprio (HOSPITALAR - Encerrado automaticamente pois a conduta já será feita)
        $attendance = Attendance::create([
            'citizen_id' => $citizen->id,
            'health_unit_id' => $request->user()?->health_unit_id ?? 1,
            'reception_user_id' => $request->user()?->id,
            'care_type' => 'HOSPITALAR',
            'queue_password' => 'HOSP-' . rand(1000, 9999), // Senha gerada apenas para tracking interno
            'residence_status' => $citizen->is_resident_assai ? 'RESIDENTE' : 'NAO_RESIDENTE',
            'status' => 'ENCERRADO',
            'arrived_at' => now(),
        ]);

        // Passo 3: Criar Triagem (Sinais Vitais Capturados)
        Triage::create([
            'attendance_id' => $attendance->id,
            'nurse_user_id' => $request->user()?->id, // Pode ser o próprio médico no módulo MVP
            'systolic_pressure' => $data['systolic_pressure'] ?? null,
            'diastolic_pressure' => $data['diastolic_pressure'] ?? null,
            'heart_rate' => $data['heart_rate'] ?? null,
            'temperature' => $data['temperature'] ?? null,
            'spo2' => $data['spo2'] ?? null,
            'hgt' => $data['hgt'] ?? null,
            'weight' => $data['weight'] ?? null,
            'risk_classification' => 'PRONTO_ATENDIMENTO_M7',
            'risk_color' => 'AZUL',
        ]);

        // Passo 4: Criar HospitalRecord
        // Filtrar CIDs secundários vazios
        $data['secondary_cids'] = array_values(array_filter($data['secondary_cids'] ?? []));

        $record = HospitalRecord::create([
            'attendance_id' => $attendance->id,
            'doctor_user_id' => $request->user()?->id,
            'soap_objective' => $data['soap_objective'],
            'soap_assessment' => $data['soap_assessment'],
            'diagnosis' => $data['diagnosis'],
            'cid_10' => $data['cid_10'],
            'secondary_cids' => $data['secondary_cids'],
            'procedures' => $data['procedures'] ?? null,
            'exams' => $data['exams'] ?? null,
            'guidance' => $data['guidance'] ?? null,
            'outcome' => $data['outcome'],
            'signed_at' => now(),
        ]);

        // Regras Finais: Integrações LEDI
        $queue1 = LediQueue::create([
            'resource_type' => HospitalRecord::class,
            'resource_id' => $record->id,
            'ledger_type' => 'FichaAtendimentoIndividual',
            'payload' => $record->toArray(),
        ]);
        DispatchLediRecord::dispatch($queue1->id);

        if ($data['outcome'] === 'ALTA') {
            $queue2 = LediQueue::create([
                'resource_type' => HospitalRecord::class,
                'resource_id' => $record->id,
                'ledger_type' => 'RAC',
                'payload' => [
                    'cid_10' => $data['cid_10'],
                    'diagnosis' => $data['diagnosis'],
                    'guidance' => $data['guidance'] ?? null,
                ],
            ]);
            DispatchLediRecord::dispatch($queue2->id);
        }

        $this->audit->log($request, 'M7', 'PRONTUARIO_HOSPITALAR_UNIFICADO', HospitalRecord::class, $record->id);

        return redirect()->route('hospital.index')->with('status', 'Atendimento hospitalar completo registrado com sucesso!');
    }
}
