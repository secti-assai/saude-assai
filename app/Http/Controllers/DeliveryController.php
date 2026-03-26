<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Delivery::class);

        $user = auth()->user();
        $isCentral = in_array($user?->role, ['admin_secti', 'gestor', 'auditor'], true);

        // Filtros da URL
        $statusFilter = $request->query('status', 'ativos'); // Padrão: mostra só os ativos
        $search = $request->query('search');

        $deliveries = Delivery::with('prescription.citizen', 'prescription.attendance', 'prescription.items.medication')
            ->when(! $isCentral && $user?->health_unit_id, function ($query) use ($user) {
                $query->whereHas('prescription.attendance', fn ($q) => $q->where('health_unit_id', $user->health_unit_id));
            })
            // Aplica os filtros
            ->when($statusFilter === 'ativos', fn($q) => $q->whereIn('status', ['PENDENTE', 'EM_ROTA']))
            ->when($statusFilter === 'historico', fn($q) => $q->whereIn('status', ['ENTREGUE', 'FALHA']))
            ->when($search, function($q) use ($search) {
                $q->whereHas('prescription.citizen', fn($q2) => $q2->where('full_name', 'ilike', "%{$search}%"));
            })
            ->latest()
            ->get();

        return view('deliveries.index', compact('deliveries', 'statusFilter', 'search'));
    }

    public function updateStatus(Request $request, Delivery $delivery): RedirectResponse
    {
        $this->authorize('update', $delivery);

        $user = $request->user();
        $isCentral = in_array($user?->role, ['admin_secti', 'gestor', 'auditor'], true);

        // Bloqueio de Concorrência: Impede que outro entregador pegue uma entrega já assumida
        if ($delivery->delivery_user_id && $delivery->delivery_user_id !== $user->id && !$isCentral) {
            return back()->withErrors(['status' => 'Esta entrega já está em rota com outro entregador.']);
        }

        $data = $request->validate([
            'status' => ['required', 'in:PENDENTE,EM_ROTA,ENTREGUE,FALHA'],
            'failure_reason' => ['required_if:status,FALHA', 'nullable', 'string', 'max:255'], // Obriga motivo se for FALHA
            'gps_lat' => ['nullable', 'string'],
            'gps_lng' => ['nullable', 'string'],
            'signature_data' => ['nullable', 'string'],
        ]);

        $delivery->update([
            ...$data,
            'failure_reason' => $data['status'] === 'FALHA' ? $data['failure_reason'] : null, // Limpa o motivo se não for falha
            // Se mudou pra EM_ROTA ou ENTREGUE, amarra o usuário. Se voltou pra PENDENTE, solta.
            'delivery_user_id' => $data['status'] === 'PENDENTE' ? null : ($delivery->delivery_user_id ?? $user->id),
            'confirmed_at' => $data['status'] === 'ENTREGUE' ? now() : null,
        ]);

        $this->audit->log($request, 'M5', 'ATUALIZAR_ENTREGA', Delivery::class, $delivery->id, ['status' => $data['status']]);

        return back()->with('status', 'Status de entrega atualizado.');
    }
}
