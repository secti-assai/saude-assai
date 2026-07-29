<?php

namespace App\Http\Controllers;

use App\Models\HealthUnit;
use App\Models\User;
use App\Models\WomenClinicAppointment;
use App\Models\Citizen;
use App\Models\PharmacyExternalImportRow;
use App\Models\SystemLog;
use App\Models\CentralPharmacyRequest;
use App\Services\AuditService;
use App\Services\PharmacyExternalImportService;
use App\Services\GovAssaiService;
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
                User::ROLE_TRIAGEM,
                User::ROLE_MEDICO,
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
                User::ROLE_TRIAGEM,
                User::ROLE_MEDICO,
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
                User::ROLE_TRIAGEM,
                User::ROLE_MEDICO,
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

    public function triggerBypassSweep(Request $request): RedirectResponse
    {
        \App\Jobs\SweepImportedBypassesJob::dispatch();
        
        $this->audit->log($request, 'ADMIN', 'VARREDURA_BYPASS_DISPARADA', null, null, []);

        return back()->with('status', 'Varredura de Bypasses iniciada em segundo plano. Os registros bloqueados serão atualizados em breve.');
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
        $viewType = $request->input('view_type', 'item');

        // Query for paginated rows
        $baseQuery = PharmacyExternalImportRow::query()
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

        if ($viewType === 'citizen') {
            $rows = (clone $baseQuery)
                ->select('citizen_id')
                ->selectRaw('COUNT(*) as total_dispensations')
                ->selectRaw('SUM(quantity) as total_quantity')
                ->selectRaw('MAX(dispensed_at) as last_dispensed_at')
                ->selectRaw('MAX(CASE WHEN bypass_detected THEN 1 ELSE 0 END) as has_bypass')
                ->with(['citizen'])
                ->groupBy('citizen_id')
                ->orderByDesc('last_dispensed_at')
                ->paginate(20)
                ->withQueryString();
        } else {
            $rows = (clone $baseQuery)
                ->with(['citizen', 'pharmacistUser', 'importBatch', 'centralPharmacyRequest'])
                ->orderByDesc('dispensed_at')
                ->paginate(20)
                ->withQueryString();
        }

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

        if ($viewType === 'citizen') {
            $totalDispensations = (clone $statsQuery)->distinct('citizen_id')->count('citizen_id');
            $totalBypasses = (clone $statsQuery)->where('bypass_detected', true)->distinct('citizen_id')->count('citizen_id');
            $totalRegular = (clone $statsQuery)->where('bypass_detected', false)->distinct('citizen_id')->count('citizen_id');
            $complianceRate = $totalDispensations > 0 ? round(($totalRegular / $totalDispensations) * 100, 1) : 100.0;
        } else {
            $totalDispensations = (clone $statsQuery)->count();
            $totalBypasses = (clone $statsQuery)->where('bypass_detected', true)->count();
            $totalRegular = $totalDispensations - $totalBypasses;
            $complianceRate = $totalDispensations > 0 ? round(($totalRegular / $totalDispensations) * 100, 1) : 100.0;
        }



        // New stats:
        // 1. Most dispensed medication
        $mostDispensedMed = (clone $statsQuery)
            ->select('medication_name_raw', DB::raw('COUNT(*) as total'))
            ->groupBy('medication_name_raw')
            ->orderByDesc('total')
            ->first();

        // 2. Most dispensed high cost medication
        $highCostKeywords = config('pharmacy.alto_custo_keywords', []);
        $mostDispensedHighCostMed = null;
        if (!empty($highCostKeywords)) {
            $mostDispensedHighCostMed = (clone $statsQuery)
                ->select('medication_name_raw', DB::raw('COUNT(*) as total'))
                ->where(function ($q) use ($highCostKeywords) {
                    foreach ($highCostKeywords as $keyword) {
                        $needle = '%' . strtolower($keyword) . '%';
                        $q->orWhereRaw('LOWER(medication_name_raw) LIKE ?', [$needle]);
                    }
                })
                ->groupBy('medication_name_raw')
                ->orderByDesc('total')
                ->first();
        }

        // 3. Dispensations with Gov.Assai
        $dispensationsGovAssai = (clone $statsQuery)
            ->where('bypass_detected', false)
            ->whereHas('centralPharmacyRequest', function ($q) {
                $q->whereIn('gov_assai_level', ['2', '3', '4', '5']);
            })
            ->count();

        // 4. Dispensations released by ACS
        $dispensationsAcs = (clone $statsQuery)
            ->where('bypass_detected', false)
            ->whereHas('centralPharmacyRequest', function ($q) {
                $q->whereIn('gov_assai_level', ['0', '1'])
                  ->orWhereNull('gov_assai_level');
            })
            ->count();

        // 5. Citizens level 2 in period
        $citizensLevel2 = (clone $statsQuery)
            ->where('bypass_detected', false)
            ->whereHas('centralPharmacyRequest', function ($q) {
                $q->whereIn('gov_assai_level', ['2', '3', '4', '5']);
            })
            ->distinct('citizen_id')
            ->count('citizen_id');

        // 6. Citizens validated by ACS in period
        if ($viewType === 'citizen') {
            // For citizen view, ensure the sum perfectly matches totalRegular by making them mutually exclusive
            $citizensValidatedAcs = max(0, $totalRegular - $citizensLevel2);
        } else {
            $citizensValidatedAcs = (clone $statsQuery)
                ->where('bypass_detected', false)
                ->whereHas('centralPharmacyRequest', function ($q) {
                    $q->whereIn('gov_assai_level', ['0', '1'])
                      ->orWhereNull('gov_assai_level');
                })
                ->distinct('citizen_id')
                ->count('citizen_id');
        }

        // 7. Women Clinic appointments scheduled in period
        $appointmentsQuery = \App\Models\WomenClinicAppointment::query()
            ->where('clinic_type', \App\Models\WomenClinicAppointment::CLINIC_WOMEN)
            ->whereDate('scheduled_for', '>=', $dateStart)
            ->whereDate('scheduled_for', '<=', $dateEnd);
        if ($citizenSearch !== '') {
            $needle = '%' . strtolower($citizenSearch) . '%';
            $appointmentsQuery->whereHas('citizen', function ($qc) use ($needle) {
                $qc->whereRaw('LOWER(full_name) LIKE ?', [$needle]);
            });
        }
        $womenClinicAppointments = $appointmentsQuery->count();

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

        // 8. Avg dispensations per day
        $avgDispensationsPerDay = count($dailyData) > 0 ? round($totalDispensations / count($dailyData), 1) : 0.0;

        // 9. Medications Report
        $medicationsReport = (clone $statsQuery)
            ->select(
                'medication_name_raw',
                DB::raw('COUNT(*) as total_dispensations'),
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(CASE WHEN bypass_detected THEN 1 ELSE 0 END) as total_bypasses')
            )
            ->groupBy('medication_name_raw')
            ->orderByDesc('total_dispensations')
            ->get()
            ->map(function ($med) {
                $total = (int) $med->total_dispensations;
                $bypass = (int) $med->total_bypasses;
                $regular = $total - $bypass;
                $rate = $total > 0 ? round(($regular / $total) * 100, 1) : 100.0;
                return [
                    'name' => $med->medication_name_raw,
                    'total' => $total,
                    'quantity' => (int) $med->total_quantity,
                    'bypass' => $bypass,
                    'regular' => $regular,
                    'rate' => $rate
                ];
            });

        $allPeriodRows = (clone $statsQuery)
            ->with(['citizen'])
            ->orderByDesc('dispensed_at')
            ->limit(2000)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'dispensed_at' => $row->dispensed_at ? $row->dispensed_at->format('Y-m-d H:i:s') : 'N/A',
                    'dispensed_at_formatted' => $row->dispensed_at ? $row->dispensed_at->format('d/m/Y H:i') : 'N/A',
                    'date_only' => $row->dispensed_at ? $row->dispensed_at->toDateString() : null,
                    'external_dispense_number' => $row->external_dispense_number ?? 'N/A',
                    'customer_name' => $row->citizen ? $row->citizen->full_name : $row->customer_name_raw,
                    'cpf' => $row->citizen && $row->citizen->cpf ? preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "$1.$2.$3-$4", $row->citizen->cpf) : 'N/A',
                    'gov_level' => $row->citizen ? ($row->citizen->gov_assai_level ?? '0') : '0',
                    'is_resident_assai' => $row->citizen ? ($row->citizen->is_resident_assai ? 'Assaí' : 'Pendente') : 'Pendente',
                    'pharmacy_lock_flag' => $row->citizen ? (bool) $row->citizen->pharmacy_lock_flag : false,
                    'citizen_id' => $row->citizen ? (int) $row->citizen->id : null,
                    'medication_name' => $row->medication_name_raw,
                    'quantity' => $row->quantity,
                    'bypass_detected' => (bool) $row->bypass_detected,
                ];
            });

        return view('admin.border-control', [
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
            'bypass_only' => $bypassOnly,
            'high_cost_only' => $highCostOnly,
            'citizen_search' => $citizenSearch,
            'medication_search' => $medicationSearch,
            'view_type' => $viewType,
            'rows' => $rows,
            'stats' => [
                'total' => $totalDispensations,
                'regular' => $totalRegular,
                'bypass' => $totalBypasses,
                'compliance_rate' => $complianceRate,
                'most_dispensed_med' => $mostDispensedMed ? ($mostDispensedMed->medication_name_raw . ' (' . $mostDispensedMed->total . ')') : 'Nenhum',
                'most_dispensed_high_cost_med' => $mostDispensedHighCostMed ? ($mostDispensedHighCostMed->medication_name_raw . ' (' . $mostDispensedHighCostMed->total . ')') : 'Nenhum',
                'dispensations_gov_assai' => $dispensationsGovAssai,
                'dispensations_acs' => $dispensationsAcs,
                'citizens_level_2' => $citizensLevel2,
                'citizens_validated_acs' => $citizensValidatedAcs,
                'women_clinic_appointments' => $womenClinicAppointments,
                'avg_dispensations_per_day' => $avgDispensationsPerDay,
            ],
            'dailyData' => $dailyData,
            'medicationsReport' => $medicationsReport,
            'allPeriodRows' => $allPeriodRows,
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
                'Nivel Consulta à População à descrita de Assaí',
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

    public function fixSyntheticCitizen(Request $request, Citizen $citizen, GovAssaiService $govAssai)
    {
        $request->validate([
            'cpf' => 'required|string',
            'validation_date' => 'required|date'
        ]);

        $cpf = preg_replace('/\D+/', '', $request->input('cpf'));
        if (strlen($cpf) !== 11) {
            return response()->json(['success' => false, 'message' => 'CPF inválido.']);
        }

        $result = $govAssai->fetchCitizenByCpf($cpf);

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => 'Cidadão não encontrado no Gov.Assaí.']);
        }

        try {
            DB::beginTransaction();

            $mapped = $govAssai->mapCitizenDataForLocalCreate($result['data']);
            $cpfHash = hash('sha256', $cpf);

            $realCitizen = Citizen::updateOrCreate(
                ['cpf_hash' => $cpfHash],
                [
                    'cpf' => $cpf,
                    'full_name' => $mapped['name'] ?? 'NOME NAO INFORMADO',
                    'social_name' => $mapped['social_name'],
                    'birth_date' => $mapped['birth_date'] ?? '1900-01-01',
                    'sexo' => $mapped['sexo'],
                    'email' => $mapped['email'],
                    'phone' => $mapped['phone'],
                    'cns' => $mapped['cns'],
                    'is_resident_assai' => $mapped['is_resident_assai'] ?? false,
                ]
            );

            $dateLimit = Carbon::parse($request->input('validation_date'))->format('Y-m-d 00:00:00');

            // Move CentralPharmacyRequest
            CentralPharmacyRequest::where('citizen_id', $citizen->id)
                ->update(['citizen_id' => $realCitizen->id]);

            // Move PharmacyExternalImportRow
            PharmacyExternalImportRow::where('citizen_id', $citizen->id)
                ->update(['citizen_id' => $realCitizen->id]);

            // Recalculate bypass based on the date Limit
            PharmacyExternalImportRow::where('citizen_id', $realCitizen->id)
                ->where('dispensed_at', '<', $dateLimit)
                ->update(['bypass_detected' => true]);

            PharmacyExternalImportRow::where('citizen_id', $realCitizen->id)
                ->where('dispensed_at', '>=', $dateLimit)
                ->update(['bypass_detected' => false]);

            $citizen->delete();

            DB::commit();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erro interno ao vincular: ' . $e->getMessage()]);
        }
    }
}
