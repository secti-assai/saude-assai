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
use App\Services\GovAssaiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $prescriptions = Prescription::with('citizen', 'items.medication')->latest()->take(30)->get();
        $attendances = Attendance::with('citizen')->latest()->take(30)->get();
        $medications = Medication::orderBy('name')->get();

        return view('prescriptions.index', compact('prescriptions', 'attendances', 'medications'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'attendance_id' => ['required', 'exists:attendances,id'],
            'delivery_type' => ['required', 'in:RETIRADA,ENTREGA'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medication_id' => ['required', 'exists:medications,id'],
            'items.*.dosage' => ['nullable', 'string'],
            'items.*.frequency' => ['nullable', 'string'],
            'items.*.administration_route' => ['nullable', 'string'],
            'items.*.duration_days' => ['required', 'integer', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
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

        // Criar todos os itens da prescrição
        foreach ($data['items'] as $item) {
            PrescriptionItem::create([
                'prescription_id' => $prescription->id,
                'medication_id' => (int) $item['medication_id'],
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
}
