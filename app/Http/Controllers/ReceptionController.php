<?php

namespace App\Http\Controllers;

use App\Jobs\DispatchLediRecord;
use App\Models\Attendance;
use App\Models\Citizen;
use App\Models\HealthUnit;
use App\Models\LediQueue;
use App\Services\AuditService;
use App\Services\GovAssaiService;
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
        $attendances = Attendance::with('citizen')->latest()->take(20)->get();
        $units = HealthUnit::where('is_active', true)->orderBy('name')->get();

        return view('reception.index', compact('attendances', 'units'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cpf' => ['required', 'digits:11'],
            'cns' => ['nullable', 'string', 'max:15'],
            'full_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
            'care_type' => ['required', 'string'],
            'summary_reason' => ['nullable', 'string', 'max:200'],
            'health_unit_id' => ['required', 'exists:health_units,id'],
            'work_accident' => ['nullable', 'boolean'],
        ]);

        $residenceStatus = $this->govAssai->validateResidence($data['cpf']);

        $citizen = Citizen::updateOrCreate(
            ['cpf' => $data['cpf']],
            [
                'full_name' => $data['full_name'],
                'birth_date' => $data['birth_date'],
                'cns' => $data['cns'] ?? null,
                'is_resident_assai' => $residenceStatus === 'RESIDENTE',
                'residence_validated_at' => now(),
            ]
        );

        $attendance = Attendance::create([
            'citizen_id' => $citizen->id,
            'health_unit_id' => (int) $data['health_unit_id'],
            'reception_user_id' => $request->user()?->id,
            'care_type' => $data['care_type'],
            'queue_password' => 'A'.str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT),
            'residence_status' => $residenceStatus,
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
