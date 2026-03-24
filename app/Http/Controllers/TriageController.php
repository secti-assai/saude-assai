<?php

namespace App\Http\Controllers;

use App\Jobs\DispatchLediRecord;
use App\Models\AlertVitalSign;
use App\Models\Attendance;
use App\Models\LediQueue;
use App\Models\Triage;
use App\Services\AuditService;
use App\Services\ManchesterRiskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TriageController extends Controller
{
    public function __construct(
        private readonly ManchesterRiskService $risk,
        private readonly AuditService $audit
    ) {
    }

    public function index(): View
    {
        $attendances = Attendance::with('citizen')->doesntHave('triage')->latest()->get();

        return view('triage.index', compact('attendances'));
    }

    public function store(Request $request, Attendance $attendance): RedirectResponse
    {
        $data = $request->validate([
            'nursing_history' => ['nullable', 'string'],
            'consciousness_level' => ['required', 'string'],
            'comorbidities' => ['nullable', 'array'],
            'comorbidities.*' => ['string'],
            'systolic_pressure' => ['nullable', 'integer'],
            'diastolic_pressure' => ['nullable', 'integer'],
            'temperature' => ['nullable', 'numeric'],
            'heart_rate' => ['nullable', 'integer'],
            'spo2' => ['nullable', 'integer'],
            'hgt' => ['nullable', 'integer'],
            'weight' => ['nullable', 'numeric'],
        ]);

        $classification = $this->risk->classify($data);

        $triage = Triage::create([
            ...$data,
            'attendance_id' => $attendance->id,
            'nurse_user_id' => $request->user()?->id,
            'comorbidities' => $data['comorbidities'] ?? [],
            'risk_color' => $classification['color'],
            'risk_classification' => $classification['classification'],
        ]);

        // ── Alertas automáticos de sinais vitais (Seção 4.3.1 da doc) ──
        $this->generateVitalSignAlerts($triage, $data);

        $attendance->update([
            'priority_color' => $classification['color'],
            'status' => 'TRIAGEM_CONCLUIDA',
        ]);

        $queue = LediQueue::create([
            'resource_type' => Triage::class,
            'resource_id' => $triage->id,
            'ledger_type' => 'FichaAtendimentoIndividual',
            'payload' => $triage->toArray(),
        ]);

        DispatchLediRecord::dispatch($queue->id);
        $this->audit->log($request, 'M4', 'TRIAGEM_CONCLUIDA', Triage::class, $triage->id);

        return back()->with('status', 'Triagem registrada com classificacao automatica.');
    }

    /**
     * Gera todos os alertas de sinais vitais conforme os limites da documentação.
     */
    private function generateVitalSignAlerts(Triage $triage, array $data): void
    {
        $alerts = [];

        // SpO₂ < 94%
        $spo2 = $data['spo2'] ?? null;
        if ($spo2 !== null && $spo2 < 94) {
            $severity = $spo2 < 90 ? 'CRITICO' : 'ALTO';
            $alerts[] = ['kind' => 'SPO2', 'severity' => $severity, 'message' => "Saturacao O2 em {$spo2}% (limite: 94%)."];
        }

        // PA sistólica > 180 ou diastólica > 120
        $sys = $data['systolic_pressure'] ?? null;
        $dia = $data['diastolic_pressure'] ?? null;
        if (($sys !== null && $sys > 180) || ($dia !== null && $dia > 120)) {
            $alerts[] = ['kind' => 'PA', 'severity' => 'ALTO', 'message' => "Pressao arterial elevada: {$sys}/{$dia} mmHg."];
        }

        // PA sistólica < 80 (choque)
        if ($sys !== null && $sys > 0 && $sys < 80) {
            $alerts[] = ['kind' => 'PA', 'severity' => 'CRITICO', 'message' => "PA sistolica critica: {$sys} mmHg (possivel choque)."];
        }

        // Temperatura > 37.8 ou < 35.0
        $temp = $data['temperature'] ?? null;
        if ($temp !== null) {
            if ($temp > 37.8) {
                $severity = $temp > 39 ? 'CRITICO' : 'ALTO';
                $alerts[] = ['kind' => 'TEMP', 'severity' => $severity, 'message' => "Temperatura elevada: {$temp}°C."];
            } elseif ($temp > 0 && $temp < 35.0) {
                $alerts[] = ['kind' => 'TEMP', 'severity' => 'ALTO', 'message' => "Hipotermia: {$temp}°C."];
            }
        }

        // FC < 50 ou > 120
        $fc = $data['heart_rate'] ?? null;
        if ($fc !== null) {
            if ($fc > 120) {
                $severity = $fc > 150 ? 'CRITICO' : 'ALTO';
                $alerts[] = ['kind' => 'FC', 'severity' => $severity, 'message' => "Frequencia cardiaca elevada: {$fc} bpm."];
            } elseif ($fc > 0 && $fc < 50) {
                $alerts[] = ['kind' => 'FC', 'severity' => 'ALTO', 'message' => "Bradicardia: {$fc} bpm."];
            }
        }

        // HGT < 70 ou > 300
        $hgt = $data['hgt'] ?? null;
        if ($hgt !== null) {
            if ($hgt > 0 && $hgt < 70) {
                $severity = $hgt < 50 ? 'CRITICO' : 'ALTO';
                $alerts[] = ['kind' => 'HGT', 'severity' => $severity, 'message' => "Hipoglicemia: {$hgt} mg/dL."];
            } elseif ($hgt > 300) {
                $alerts[] = ['kind' => 'HGT', 'severity' => 'ALTO', 'message' => "Hiperglicemia: {$hgt} mg/dL."];
            }
        }

        // Inconsciência → alerta vermelho imediato
        $consciousness = strtoupper($data['consciousness_level'] ?? 'LUCIDO');
        if ($consciousness === 'INCONSCIENTE') {
            $alerts[] = ['kind' => 'CONSCIENCIA', 'severity' => 'CRITICO', 'message' => 'Paciente inconsciente — atencao imediata requerida.'];
        }

        // Persistir todos os alertas
        foreach ($alerts as $alert) {
            AlertVitalSign::create([
                'triage_id' => $triage->id,
                ...$alert,
            ]);
        }
    }
}
