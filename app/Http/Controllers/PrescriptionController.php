<?php

namespace App\Http\Controllers;

use App\Jobs\DispatchLediRecord;
use App\Models\Attendance;
use App\Models\Delivery;
use App\Models\LediQueue;
use App\Models\Medication;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrescriptionController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function index(): View
    {
        $prescriptions = Prescription::with('citizen', 'items.medication')->latest()->take(30)->get();
        $attendances = Attendance::with('citizen')->latest()->take(30)->get();
        $medications = Medication::orderBy('name')->get();

        return view('prescriptions.index', compact('prescriptions', 'attendances', 'medications'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'attendance_id' => ['required', 'exists:attendances,id'],
            'medication_id' => ['required', 'exists:medications,id'],
            'dosage' => ['nullable', 'string'],
            'frequency' => ['nullable', 'string'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1'],
            'delivery_type' => ['required', 'in:RETIRADA,ENTREGA'],
            'notes' => ['nullable', 'string'],
        ]);

        $attendance = Attendance::with('citizen')->findOrFail((int) $data['attendance_id']);

        $prescription = Prescription::create([
            'attendance_id' => $attendance->id,
            'citizen_id' => $attendance->citizen_id,
            'doctor_user_id' => $request->user()?->id,
            'delivery_type' => $data['delivery_type'],
            'status' => 'ASSINADA',
            'notes' => $data['notes'] ?? null,
            'signed_at' => now(),
        ]);

        PrescriptionItem::create([
            'prescription_id' => $prescription->id,
            'medication_id' => (int) $data['medication_id'],
            'dosage' => $data['dosage'] ?? null,
            'frequency' => $data['frequency'] ?? null,
            'administration_route' => 'VO',
            'duration_days' => (int) $data['duration_days'],
            'quantity' => (int) $data['quantity'],
        ]);

        if ($data['delivery_type'] === 'ENTREGA') {
            Delivery::create([
                'prescription_id' => $prescription->id,
                'status' => 'PENDENTE',
                'address' => $attendance->citizen->address,
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
}
