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

        if (($data['spo2'] ?? 100) < 94) {
            AlertVitalSign::create([
                'triage_id' => $triage->id,
                'kind' => 'SPO2',
                'severity' => 'ALTO',
                'message' => 'Saturacao abaixo do ideal.',
            ]);
        }

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
}
