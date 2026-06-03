<?php

namespace App\Http\Controllers;

use App\Models\HealthUnit;
use App\Models\User;
use App\Models\WomenClinicAppointment;
use App\Models\Citizen;
use App\Models\PharmacyExternalImportRow;
use App\Services\AuditService;
use App\Services\PharmacyExternalImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminManagementController extends Controller
{
    private const ALLOWED_USER_UNIT_NAMES = [
        'Clinica da Mulher',
        'Clínica da Mulher',
        'Policlinica',
        'Policlínica',
        'Farmacia Central',
        'Farmácia Central',
    ];

    public function __construct(
        private readonly AuditService $audit,
        private readonly PharmacyExternalImportService $externalImportService,
    )
    {
    }

    public function usersArea(): View
    {
        $users = User::with('healthUnit')->orderBy('name')->get();
        $healthUnits = $this->allowedUserHealthUnitsQuery()
            ->orderBy('name')
            ->get();

        return view('admin.users', [
            'users' => $users,
            'healthUnits' => $healthUnits,
            'roles' => [
                User::ROLE_ADMIN,
                User::ROLE_AGENDADOR,
                User::ROLE_RECEPCAO_CLINICA,
                User::ROLE_MEDICO_CLINICA,
                User::ROLE_RECEPCAO_POLICLINICA,
                User::ROLE_MEDICO_POLICLINICA,
                User::ROLE_FARMACIA,
            ],
            'permissions' => User::allPermissionOptions(),
            'clinicSpecialties' => User::clinicSpecialtyOptions(),
        ]);
    }

    public function createUser(Request $request): RedirectResponse
    {
        $allowedHealthUnitIds = $this->allowedUserHealthUnitIds();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in([
                User::ROLE_ADMIN,
                User::ROLE_AGENDADOR,
                User::ROLE_RECEPCAO_CLINICA,
                User::ROLE_MEDICO_CLINICA,
                User::ROLE_RECEPCAO_POLICLINICA,
                User::ROLE_MEDICO_POLICLINICA,
                User::ROLE_FARMACIA,
            ])],
            'health_unit_id' => ['nullable', Rule::in($allowedHealthUnitIds)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(User::allPermissionOptions())],
            'clinic_specialty' => [
                'nullable',
                'string',
                Rule::requiredIf($this->isDoctorRole((string) $request->input('role'))),
                Rule::in(WomenClinicAppointment::specialtyValues()),
            ],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'health_unit_id' => $data['health_unit_id'] ?? null,
            'permissions' => $data['permissions'] ?? null,
            'clinic_specialty' => $this->resolveClinicSpecialty($data),
            'email_verified_at' => now(),
        ]);

        $this->audit->log($request, 'ADMIN', 'CRIAR_USUARIO', User::class, (int) $user->id, [
            'target_user_email' => $user->email,
            'target_user_role' => $user->role,
            'target_user_clinic_specialty' => $user->clinic_specialty,
        ]);

        return back()->with('status', 'Usuario criado com sucesso.');
    }

    public function updatePermissions(Request $request, User $user): RedirectResponse
    {
        $allowedHealthUnitIds = $this->allowedUserHealthUnitIds();

        $data = $request->validate([
            'role' => ['required', 'string', Rule::in([
                User::ROLE_ADMIN,
                User::ROLE_AGENDADOR,
                User::ROLE_RECEPCAO_CLINICA,
                User::ROLE_MEDICO_CLINICA,
                User::ROLE_RECEPCAO_POLICLINICA,
                User::ROLE_MEDICO_POLICLINICA,
                User::ROLE_FARMACIA,
            ])],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(User::allPermissionOptions())],
            'health_unit_id' => ['nullable', Rule::in($allowedHealthUnitIds)],
            'clinic_specialty' => [
                'nullable',
                'string',
                Rule::requiredIf($this->isDoctorRole((string) $request->input('role'))),
                Rule::in(WomenClinicAppointment::specialtyValues()),
            ],
        ]);

        $user->update([
            'role' => $data['role'],
            'permissions' => $data['permissions'] ?? null,
            'health_unit_id' => $data['health_unit_id'] ?? null,
            'clinic_specialty' => $this->resolveClinicSpecialty($data),
        ]);

        $this->audit->log($request, 'ADMIN', 'ATUALIZAR_PERMISSOES', User::class, (int) $user->id, [
            'target_user_email' => $user->email,
            'target_user_role' => $user->role,
            'target_user_clinic_specialty' => $user->clinic_specialty,
            'permissions' => $user->permissions,
        ]);

        return back()->with('status', 'Permissoes atualizadas com sucesso.');
    }

    public function removeUser(Request $request, User $user): RedirectResponse
    {
        if ((int) $request->user()->id === (int) $user->id) {
            return back()->withErrors(['user' => 'Voce nao pode remover seu proprio usuario.']);
        }

        $email = $user->email;
        $userId = (int) $user->id;
        $user->delete();

        $this->audit->log($request, 'ADMIN', 'REMOVER_USUARIO', User::class, $userId, [
            'target_user_email' => $email,
        ]);

        return back()->with('status', 'Usuario removido com sucesso.');
    }

    public function reportsArea(): View
    {
        $externalDashboard = $this->externalImportService->buildDashboard();

        $usersByRole = User::query()
            ->select('role', DB::raw('COUNT(*) as total'))
            ->groupBy('role')
            ->orderBy('role')
            ->get();

        $activityByModule = DB::table('audit_logs')
            ->select('module', DB::raw('COUNT(*) as total'))
            ->groupBy('module')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        $recentAudits = DB::table('audit_logs')
            ->orderByDesc('created_at')
            ->limit(40)
            ->get();

        return view('admin.reports', [
            'usersByRole' => $usersByRole,
            'activityByModule' => $activityByModule,
            'recentAudits' => $recentAudits,
            'externalImportTotals' => $externalDashboard['externalImportTotals'],
            'recentPharmacyImports' => $externalDashboard['recentPharmacyImports'],
            'recentBypassRows' => $externalDashboard['recentBypassRows'],
            'recurrenceAlerts' => $externalDashboard['recurrenceAlerts'],
        ]);
    }

    public function importPharmacyExternalDispensations(Request $request): RedirectResponse
    {
        $request->validate([
            'betha_csv' => ['required', 'file', 'max:20480', 'extensions:csv,txt'],
            'daily_txt' => ['required', 'file', 'max:20480', 'extensions:txt,csv'],
        ]);

        $bethaFile = $request->file('betha_csv');
        $dailyFile = $request->file('daily_txt');

        if ($bethaFile === null || $dailyFile === null) {
            return back()->withErrors(['betha_csv' => 'Arquivos de importacao invalidos.']);
        }

        $result = $this->externalImportService->import($request, $bethaFile, $dailyFile);
        $summary = $result['summary'] ?? [];

        $message = sprintf(
            'Importacao concluida. Processados: %d | Atendimentos criados: %d | Bypass detectados: %d | Bloqueios acionados: %d',
            (int) ($summary['processed_rows'] ?? 0),
            (int) ($summary['synthetic_created'] ?? 0),
            (int) ($summary['bypass_detected'] ?? 0),
            (int) ($summary['citizens_locked'] ?? 0),
        );

        return redirect()->route('admin.reports')
            ->with('status', $message)
            ->with('import_summary', $result);
    }

    public function borderControlArea(Request $request): View
    {
        $dateStart = trim((string) ($request->input('date_start') ?? now()->subDays(30)->toDateString()));
        $dateEnd = trim((string) ($request->input('date_end') ?? now()->toDateString()));

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStart)) {
            $dateStart = now()->subDays(30)->toDateString();
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateEnd)) {
            $dateEnd = now()->toDateString();
        }

        if ($dateStart > $dateEnd) {
            [$dateStart, $dateEnd] = [$dateEnd, $dateStart];
        }

        $bypassOnly = $request->boolean('bypass_only');
        $highCostOnly = $request->boolean('high_cost_only');
        $citizenSearch = trim((string) $request->input('citizen_search'));
        $medicationSearch = trim((string) $request->input('medication_search'));

        // Query for paginated rows
        $baseQuery = PharmacyExternalImportRow::query()
            ->with(['citizen', 'pharmacistUser', 'importBatch'])
            ->whereDate('dispensed_at', '>=', $dateStart)
            ->whereDate('dispensed_at', '<=', $dateEnd);

        if ($bypassOnly) {
            $baseQuery->where('bypass_detected', true);
        }

        if ($highCostOnly) {
            $highCostKeywords = config('pharmacy.alto_custo_keywords', []);
            if (!empty($highCostKeywords)) {
                $baseQuery->where(function ($q) use ($highCostKeywords) {
                    foreach ($highCostKeywords as $keyword) {
                        $needle = '%' . strtolower($keyword) . '%';
                        $q->orWhereRaw('LOWER(medication_name_raw) LIKE ?', [$needle]);
                    }
                });
            }
        }

        if ($citizenSearch !== '') {
            $needle = '%' . strtolower($citizenSearch) . '%';
            $baseQuery->where(function ($q) use ($needle) {
                $q->whereRaw('LOWER(customer_name_raw) LIKE ?', [$needle])
                  ->orWhereRaw('LOWER(customer_name_normalized) LIKE ?', [$needle])
                  ->orWhereHas('citizen', function ($qc) use ($needle) {
                      $qc->whereRaw('LOWER(full_name) LIKE ?', [$needle]);
                  });
            });
        }

        if ($medicationSearch !== '') {
            $needle = '%' . strtolower($medicationSearch) . '%';
            $baseQuery->whereRaw('LOWER(medication_name_raw) LIKE ?', [$needle]);
        }

        $rows = (clone $baseQuery)
            ->orderByDesc('dispensed_at')
            ->paginate(20)
            ->withQueryString();

        // Calculations for period (based on date filters + query filters for synchronized KPIs)
        $statsQuery = PharmacyExternalImportRow::query()
            ->whereDate('dispensed_at', '>=', $dateStart)
            ->whereDate('dispensed_at', '<=', $dateEnd);

        if ($bypassOnly) {
            $statsQuery->where('bypass_detected', true);
        }

        if ($highCostOnly) {
            $highCostKeywords = config('pharmacy.alto_custo_keywords', []);
            if (!empty($highCostKeywords)) {
                $statsQuery->where(function ($q) use ($highCostKeywords) {
                    foreach ($highCostKeywords as $keyword) {
                        $needle = '%' . strtolower($keyword) . '%';
                        $q->orWhereRaw('LOWER(medication_name_raw) LIKE ?', [$needle]);
                    }
                });
            }
        }

        if ($citizenSearch !== '') {
            $needle = '%' . strtolower($citizenSearch) . '%';
            $statsQuery->where(function ($q) use ($needle) {
                $q->whereRaw('LOWER(customer_name_raw) LIKE ?', [$needle])
                  ->orWhereRaw('LOWER(customer_name_normalized) LIKE ?', [$needle])
                  ->orWhereHas('citizen', function ($qc) use ($needle) {
                      $qc->whereRaw('LOWER(full_name) LIKE ?', [$needle]);
                  });
            });
        }

        if ($medicationSearch !== '') {
            $needle = '%' . strtolower($medicationSearch) . '%';
            $statsQuery->whereRaw('LOWER(medication_name_raw) LIKE ?', [$needle]);
        }

        $totalDispensations = (clone $statsQuery)->count();
        $totalBypasses = (clone $statsQuery)->where('bypass_detected', true)->count();
        $totalRegular = $totalDispensations - $totalBypasses;
        $complianceRate = $totalDispensations > 0 ? round(($totalRegular / $totalDispensations) * 100, 1) : 100.0;

        $uniqueLockedCitizens = (clone $statsQuery)
            ->where('bypass_detected', true)
            ->distinct('citizen_id')
            ->count('citizen_id');

        // Dynamic Daily Breakdown: group by date
        $dailyData = (clone $statsQuery)
            ->select(
                DB::raw('DATE(dispensed_at) as date_only'),
                DB::raw('COUNT(*) as total_day'),
                DB::raw('SUM(CASE WHEN bypass_detected THEN 1 ELSE 0 END) as bypass_day')
            )
            ->groupBy(DB::raw('DATE(dispensed_at)'))
            ->orderBy('date_only', 'asc')
            ->get()
            ->map(function ($day) {
                $total = (int) $day->total_day;
                $bypass = (int) $day->bypass_day;
                $regular = $total - $bypass;
                $rate = $total > 0 ? round(($regular / $total) * 100, 1) : 100.0;
                return [
                    'date' => Carbon::parse($day->date_only)->format('d/m/Y'),
                    'date_raw' => $day->date_only,
                    'total' => $total,
                    'bypass' => $bypass,
                    'regular' => $regular,
                    'rate' => $rate
                ];
            });

        return view('admin.border-control', [
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
            'bypass_only' => $bypassOnly,
            'high_cost_only' => $highCostOnly,
            'citizen_search' => $citizenSearch,
            'medication_search' => $medicationSearch,
            'rows' => $rows,
            'stats' => [
                'total' => $totalDispensations,
                'regular' => $totalRegular,
                'bypass' => $totalBypasses,
                'compliance_rate' => $complianceRate,
                'unique_locked' => $uniqueLockedCitizens,
            ],
            'dailyData' => $dailyData,
        ]);
    }

    public function exportBorderControlCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $dateStart = trim((string) ($request->input('date_start') ?? now()->subDays(30)->toDateString()));
        $dateEnd = trim((string) ($request->input('date_end') ?? now()->toDateString()));

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStart)) {
            $dateStart = now()->subDays(30)->toDateString();
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateEnd)) {
            $dateEnd = now()->toDateString();
        }

        if ($dateStart > $dateEnd) {
            [$dateStart, $dateEnd] = [$dateEnd, $dateStart];
        }

        $bypassOnly = $request->boolean('bypass_only');
        $highCostOnly = $request->boolean('high_cost_only');
        $citizenSearch = trim((string) $request->input('citizen_search'));
        $medicationSearch = trim((string) $request->input('medication_search'));

        $query = PharmacyExternalImportRow::query()
            ->with(['citizen', 'pharmacistUser'])
            ->whereDate('dispensed_at', '>=', $dateStart)
            ->whereDate('dispensed_at', '<=', $dateEnd);

        if ($bypassOnly) {
            $query->where('bypass_detected', true);
        }

        if ($highCostOnly) {
            $highCostKeywords = config('pharmacy.alto_custo_keywords', []);
            if (!empty($highCostKeywords)) {
                $query->where(function ($q) use ($highCostKeywords) {
                    foreach ($highCostKeywords as $keyword) {
                        $needle = '%' . strtolower($keyword) . '%';
                        $q->orWhereRaw('LOWER(medication_name_raw) LIKE ?', [$needle]);
                    }
                });
            }
        }

        if ($citizenSearch !== '') {
            $needle = '%' . strtolower($citizenSearch) . '%';
            $query->where(function ($q) use ($needle) {
                $q->whereRaw('LOWER(customer_name_raw) LIKE ?', [$needle])
                  ->orWhereRaw('LOWER(customer_name_normalized) LIKE ?', [$needle])
                  ->orWhereHas('citizen', function ($qc) use ($needle) {
                      $qc->whereRaw('LOWER(full_name) LIKE ?', [$needle]);
                  });
            });
        }

        if ($medicationSearch !== '') {
            $needle = '%' . strtolower($medicationSearch) . '%';
            $query->whereRaw('LOWER(medication_name_raw) LIKE ?', [$needle]);
        }

        $filename = sprintf('auditoria-controle-borda-%s-a-%s.csv', $dateStart, $dateEnd);

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Headers
            fputcsv($file, [
                'ID Registro',
                'Numero Guia Externa',
                'Data Dispensacao',
                'CPF Cidadao',
                'Nome Cidadao',
                'Nivel Gov.Assai',
                'Status Residencia',
                'Bloqueado Farmacia',
                'Medicamento',
                'Quantidade',
                'Farmaceutico Importado',
                'Farmaceutico Sistema',
                'Bypass Detectado',
                'Intervalo Recorrencia (Dias)',
                'Alerta Recorrencia',
            ], ';');

            $query->chunk(500, function ($rows) use ($file) {
                foreach ($rows as $row) {
                    $cpf = 'N/A';
                    $govLevel = 'N/A';
                    $residenceStatus = 'N/A';
                    $isLocked = 'N/A';

                    if ($row->citizen) {
                        $cpf = $row->citizen->cpf ?? 'N/A';
                        $govLevel = $row->citizen->gov_assai_level ?? '0';
                        $residenceStatus = $row->citizen->is_resident_assai ? 'RESIDENTE' : 'PENDENTE';
                        $isLocked = $row->citizen->pharmacy_lock_flag ? 'SIM' : 'NAO';
                    }

                    fputcsv($file, [
                        $row->id,
                        $row->external_dispense_number ?? 'N/A',
                        $row->dispensed_at ? $row->dispensed_at->format('d/m/Y H:i:s') : 'N/A',
                        $cpf,
                        $row->citizen ? $row->citizen->full_name : $row->customer_name_raw,
                        $govLevel,
                        $residenceStatus,
                        $isLocked,
                        $row->medication_name_raw ?? 'N/A',
                        $row->quantity ?? 1,
                        $row->pharmacist_name_raw ?? 'N/A',
                        $row->pharmacistUser ? $row->pharmacistUser->name : 'N/A',
                        $row->bypass_detected ? 'SIM - BYPASS' : 'NAO',
                        $row->recurrence_interval_days ?? 'N/A',
                        $row->recurrence_alert_level ?? 'N/A',
                    ], ';');
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function toggleCitizenLock(Request $request, Citizen $citizen): RedirectResponse
    {
        $newFlag = ! (bool) $citizen->pharmacy_lock_flag;
        $citizen->update(['pharmacy_lock_flag' => $newFlag]);

        $action = $newFlag ? 'BLOQUEIO_FARMA_ATIVADO' : 'BLOQUEIO_FARMA_DESATIVADO';

        $this->audit->log(
            $request,
            'ADMIN_CONTROLE_BORDA',
            $action,
            Citizen::class,
            (int) $citizen->id,
            [
                'citizen_name' => $citizen->full_name,
                'pharmacy_lock_flag' => $newFlag,
            ]
        );

        $message = $newFlag 
            ? "Cidadao {$citizen->full_name} foi bloqueado com sucesso na farmacia." 
            : "Cidadao {$citizen->full_name} foi desbloqueado com sucesso na farmacia.";

        return back()->with('status', $message);
    }

    private function allowedUserHealthUnitsQuery()
    {
        return HealthUnit::query()
            ->whereIn('name', self::ALLOWED_USER_UNIT_NAMES);
    }

    private function allowedUserHealthUnitIds(): array
    {
        return $this->allowedUserHealthUnitsQuery()
            ->pluck('id')
            ->map(fn ($id): string => (string) $id)
            ->all();
    }

    private function resolveClinicSpecialty(array $data): ?string
    {
        if (! $this->isDoctorRole((string) ($data['role'] ?? ''))) {
            return null;
        }

        return WomenClinicAppointment::normalizeSpecialty((string) ($data['clinic_specialty'] ?? ''));
    }

    private function isDoctorRole(string $role): bool
    {
        return in_array($role, [User::ROLE_MEDICO_CLINICA, User::ROLE_MEDICO_POLICLINICA], true);
    }
}
