<?php

namespace App\Http\Controllers;

use App\Jobs\DispatchLediRecord;
use App\Models\Attendance;
use App\Models\HospitalRecord;
use App\Models\LediQueue;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HospitalController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function index(): View
    {
        $attendances = Attendance::with('citizen', 'triage')->latest()->take(30)->get();

        return view('hospital.index', compact('attendances'));
    }

    public function store(Request $request, Attendance $attendance): RedirectResponse
    {
        $data = $request->validate([
            'soap_objective' => ['required', 'string'],
            'soap_assessment' => ['required', 'string'],
            'diagnosis' => ['required', 'string'],
            'cid_10' => ['required', 'string', 'max:10'],
            'guidance' => ['nullable', 'string'],
            'outcome' => ['required', 'in:ALTA,INTERNACAO,TRANSFERENCIA,OBITO'],
        ]);

        $record = HospitalRecord::create([
            ...$data,
            'attendance_id' => $attendance->id,
            'doctor_user_id' => $request->user()?->id,
            'signed_at' => now(),
        ]);

        $attendance->update(['status' => 'ENCERRADO']);

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

        $this->audit->log($request, 'M7', 'PRONTUARIO_HOSPITALAR', HospitalRecord::class, $record->id);

        return back()->with('status', 'Prontuario hospitalar salvo e enviado para fila LEDI.');
    }
}
