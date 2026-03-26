<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DeliveryController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

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
                $query->whereHas('prescription.attendance', fn($q) => $q->where('health_unit_id', $user->health_unit_id));
            })
            // Aplica os filtros
            ->when($statusFilter === 'ativos', fn($q) => $q->whereIn('status', ['PENDENTE', 'EM_ROTA']))
            ->when($statusFilter === 'historico', fn($q) => $q->whereIn('status', ['ENTREGUE', 'FALHA']))
            ->when($search, function ($q) use ($search) {
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

        // Bloqueio de Concorrência
        if ($delivery->delivery_user_id && $delivery->delivery_user_id !== $user->id && !$isCentral) {
            return back()->withErrors(['status' => 'Esta entrega já está em rota com outro entregador.']);
        }

        // Validação
        $validated = $request->validate([
            'status' => ['required', 'in:PENDENTE,EM_ROTA,ENTREGUE,FALHA'],
            'failure_reason' => ['required_if:status,FALHA', 'nullable', 'string', 'max:255'],
            'gps_lat' => ['nullable', 'numeric'],
            'gps_lng' => ['nullable', 'numeric'],
            'signature' => ['nullable', 'string'],
        ]);

        $dataToUpdate = [
            'status' => $validated['status'],
            'failure_reason' => $validated['status'] === 'FALHA' ? $validated['failure_reason'] : null,
            'delivery_user_id' => $validated['status'] === 'PENDENTE' ? null : ($delivery->delivery_user_id ?? $user->id),
            'confirmed_at' => $validated['status'] === 'ENTREGUE' ? now() : null,
        ];

        // 1. Processa o GPS (Agora salvando APENAS nas colunas oficiais decimais)
        if ($request->filled('gps_lat') && $request->filled('gps_lng')) {
            $dataToUpdate['latitude'] = $validated['gps_lat'];
            $dataToUpdate['longitude'] = $validated['gps_lng'];
        }

        // 2. Processa a Imagem da Assinatura (Apenas caminho físico)
        if ($validated['status'] === 'ENTREGUE' && $request->filled('signature')) {
            $image_parts = explode(";base64,", $request->signature);

            if (count($image_parts) === 2) {
                $image_base64 = base64_decode($image_parts[1]);
                $fileName = 'signatures/del_' . $delivery->id . '_' . \Illuminate\Support\Str::random(8) . '.png';
                \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $image_base64);

                // Salva apenas o caminho do arquivo criado
                $dataToUpdate['signature_path'] = $fileName;
            }
        }

        $delivery->update($dataToUpdate);

        $this->audit->log($request, 'M5', 'ATUALIZAR_ENTREGA', Delivery::class, $delivery->id, ['status' => $validated['status']]);

        return back()->with('status', 'Status de entrega atualizado com sucesso.');
    }
}
