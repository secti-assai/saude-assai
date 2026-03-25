<?php

namespace App\Http\Controllers;

use App\Jobs\DispatchLediRecord;
use App\Models\Dispensation;
use App\Models\DispensationItem;
use App\Models\LediQueue;
use App\Models\Prescription;
use App\Models\StockItem;
use App\Models\Delivery;
use App\Services\AuditService;
use App\Services\GovAssaiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PharmacyController extends Controller
{
    public function __construct(
        private readonly GovAssaiService $govAssai,
        private readonly AuditService $audit
    ) {
    }

    public function index(): View
    {
        $this->authorize('viewAny', Prescription::class);

        $user = auth()->user();
        $isCentral = in_array($user?->role, ['admin_secti', 'gestor', 'auditor'], true);

        $prescriptions = Prescription::with('citizen', 'attendance', 'items.medication')
            ->whereIn('status', ['ASSINADA', 'PENDENTE'])
            ->when(! $isCentral && $user?->health_unit_id, function ($query) use ($user) {
                $query->whereHas('attendance', fn ($q) => $q->where('health_unit_id', $user->health_unit_id));
            })
            ->latest()
            ->get();

        $dispensations = Dispensation::query()
            ->when(! $isCentral && $user?->health_unit_id, function ($query) use ($user) {
                $query->whereHas('prescription.attendance', fn ($q) => $q->where('health_unit_id', $user->health_unit_id));
            })
            ->latest()
            ->take(20)
            ->get();

        return view('pharmacy.index', compact('prescriptions', 'dispensations'));
    }

    public function dispense(Request $request, Prescription $prescription): RedirectResponse
    {
        $this->authorize('dispense', $prescription);

        $residence = $this->govAssai->validateResidence($prescription->citizen->cpf);
        $blocked = $residence === 'NAO_RESIDENTE' && ! $request->boolean('emergency_override');

        $dispensation = Dispensation::create([
            'prescription_id' => $prescription->id,
            'citizen_id' => $prescription->citizen_id,
            'pharmacist_user_id' => $request->user()?->id,
            'residence_status' => $residence,
            'blocked' => $blocked,
            'justification' => $request->input('justification'),
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        if (! $blocked) {
            foreach ($prescription->items as $item) {
                DispensationItem::create([
                    'dispensation_id' => $dispensation->id,
                    'medication_id' => $item->medication_id,
                    'batch' => 'MVP-'.strtoupper(substr(md5((string) $item->id), 0, 6)),
                    'expiry_date' => now()->addMonths(8),
                    'quantity' => $item->quantity,
                ]);

                $stock = StockItem::firstOrCreate([
                    'medication_id' => $item->medication_id,
                    'health_unit_id' => $request->user()?->health_unit_id ?? 1,
                ], ['quantity' => 200]);

                $stock->decrement('quantity', $item->quantity);
            }

            $prescription->update(['status' => 'DISPENSADA']);

            // 👇 INÍCIO DA NOVA AUTOMAÇÃO DE ENTREGA 👇
            // Busca se existe uma entrega pendente atrelada a esta receita
            $delivery = Delivery::where('prescription_id', $prescription->id)
                ->whereIn('status', ['PENDENTE', 'Pendente']) 
                ->first();

            if ($delivery) {
                // Atualiza o status para "Em Rota". 
                // Obs: Coloquei 'Em Rota' baseado no seu print, mas se no seu banco 
                // salva como 'EM_ROTA' (tudo maiúsculo), é só alterar aqui!
                $delivery->update(['status' => 'Em Rota']);
            }
            // 👆 FIM DA NOVA AUTOMAÇÃO DE ENTREGA 👆
        }

        $queue = LediQueue::create([
            'resource_type' => Dispensation::class,
            'resource_id' => $dispensation->id,
            'ledger_type' => 'FichaProcedimento',
            'payload' => $dispensation->toArray(),
        ]);

        DispatchLediRecord::dispatch($queue->id);
        $this->audit->log($request, 'M6', 'DISPENSACAO', Dispensation::class, $dispensation->id);

        return back()->with('status', $blocked ? 'Dispensacao bloqueada por residencia.' : 'Dispensacao concluida. Medicamentos liberados.');
    }
}