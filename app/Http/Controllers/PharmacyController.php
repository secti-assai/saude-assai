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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PharmacyController extends Controller
{
    public function __construct(
        private readonly GovAssaiService $govAssai,
        private readonly AuditService $audit
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Prescription::class);

        $user = auth()->user();
        $search = trim((string) $request->input('search'));
        $searchDigits = preg_replace('/\D+/', '', $search) ?? '';

        // Definimos quem pode ver TUDO (Gestores da cidade ou a própria Farmácia se for centralizada)
        $isCentral = in_array($user?->role, ['admin_secti', 'gestor', 'auditor', 'farmacia', 'farmaceutico'], true);

        $applyHealthUnitFilter = function (Builder $query) use ($user, $isCentral): void {
            // Se o usuário for central, aborta o filtro e mostra as receitas de todas as UBSs
            if ($isCentral) {
                return;
            }

            // Caso contrário (se for uma farmácia de bairro específica), filtra só as daquela UBS
            $query->when($user?->health_unit_id, function (Builder $q) use ($user) {
                $q->where(function (Builder $nested) use ($user) {
                    $nested->whereHas('attendance', fn(Builder $q2) => $q2->where('health_unit_id', $user->health_unit_id))
                        ->orWhereNull('attendance_id');
                });
            });
        };

        $applySearchFilter = function (Builder $query) use ($search, $searchDigits): void {
            if ($search === '') {
                return;
            }

            $query->where(function (Builder $searchQuery) use ($search, $searchDigits) {
                $searchQuery->whereHas('citizen', function (Builder $citizenQuery) use ($search, $searchDigits) {
                    $citizenQuery->where(function (Builder $nested) use ($search, $searchDigits) {
                        $nested->whereRaw("unaccent(lower(full_name)) LIKE unaccent(lower(?))", ['%' . $search . '%']);

                        if ($searchDigits !== '') {
                            $nested->orWhereRaw("regexp_replace(cpf, '[^0-9]', '', 'g') LIKE ?", ['%' . $searchDigits . '%']);
                        }
                    });
                })->orWhereHas('items.medication', function (Builder $medicationQuery) use ($search) {
                    $medicationQuery->whereRaw("unaccent(lower(name)) LIKE unaccent(lower(?))", ['%' . $search . '%']);
                });
            });
        };

        $prescriptions = Prescription::with(['citizen', 'attendance', 'items.medication'])
            ->whereIn('status', ['PENDENTE', 'ASSINADA'])
            ->tap($applyHealthUnitFilter)
            ->tap($applySearchFilter)
            ->whereHas('items')
            ->latest()
            ->get();

        $history = Prescription::with(['citizen', 'attendance', 'items.medication', 'delivery'])
            ->where('status', 'DISPENSADA')
            ->where(function (Builder $query) {
                $query->whereHas('delivery', function (Builder $deliveryQuery) {
                    $deliveryQuery->whereIn('status', ['EM_ROTA', 'ENTREGUE']);
                })->orDoesntHave('delivery');
            })
            ->tap($applyHealthUnitFilter)
            ->tap($applySearchFilter)
            ->whereHas('items')
            ->latest()
            ->paginate(12)
            ->appends($request->query());

        $failures = Prescription::with(['citizen', 'attendance', 'items.medication', 'delivery'])
            ->where('status', 'DISPENSADA')
            ->whereHas('delivery', function (Builder $deliveryQuery) {
                $deliveryQuery->where('status', 'FALHA');
            })
            ->tap($applyHealthUnitFilter)
            ->tap($applySearchFilter)
            ->whereHas('items')
            ->latest()
            ->get();

        $drivers = \App\Models\User::where('role', 'motorista')->orderBy('name')->get();

        return view('pharmacy.index', compact('prescriptions', 'history', 'failures', 'drivers', 'search'));
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
                    'batch' => 'MVP-' . strtoupper(substr(md5((string) $item->id), 0, 6)),
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

            $driverId = $request->input('delivery_user_id');
            $delivery = Delivery::where('prescription_id', $prescription->id)->first();

            if ($driverId) {
                if ($delivery) {
                    $delivery->update([
                        'delivery_user_id' => $driverId,
                        'status' => 'EM_ROTA',
                        'latitude'  => $prescription->citizen->latitude,
                        'longitude' => $prescription->citizen->longitude,
                    ]);
                } else {
                    Delivery::create([
                        'prescription_id' => $prescription->id,
                        'delivery_user_id' => $driverId,
                        'status' => 'EM_ROTA',
                        'address' => $prescription->citizen->address ?? 'Endereço não cadastrado',
                        'latitude'         => $prescription->citizen->latitude,
                        'longitude'        => $prescription->citizen->longitude,
                    ]);
                }
            } else {
                if ($delivery && in_array($delivery->status, ['PENDENTE', 'Pendente'])) {
                    $delivery->delete();
                }
            }
        }

        $queue = LediQueue::create([
            'resource_type' => Dispensation::class,
            'resource_id' => $dispensation->id,
            'ledger_type' => 'FichaProcedimento',
            'payload' => $dispensation->toArray(),
        ]);

        DispatchLediRecord::dispatch($queue->id);
        $this->audit->log($request, 'M6', 'DISPENSACAO', Dispensation::class, $dispensation->id);

        return back()->with('status', $blocked ? 'Dispensação bloqueada por residência.' : 'Dispensação concluída com sucesso.');
    }

    public function reassign(Request $request, Delivery $delivery): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_user_id' => 'required|exists:users,id'
        ]);

        $delivery->update([
            'delivery_user_id' => $validated['delivery_user_id'],
            'status' => 'EM_ROTA',
            'failure_reason' => null
        ]);

        return back()->with('status', 'Entrega reatribuída com sucesso! O pacote já pode ser retirado pelo motorista.');
    }
}