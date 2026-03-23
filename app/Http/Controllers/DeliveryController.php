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

    public function index(): View
    {
        $deliveries = Delivery::with('prescription.citizen')->latest()->get();

        return view('deliveries.index', compact('deliveries'));
    }

    public function updateStatus(Request $request, Delivery $delivery): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:PENDENTE,EM_ROTA,ENTREGUE,FALHA'],
            'failure_reason' => ['nullable', 'string'],
            'gps_lat' => ['nullable', 'string'],
            'gps_lng' => ['nullable', 'string'],
            'signature_data' => ['nullable', 'string'],
        ]);

        $delivery->update([
            ...$data,
            'delivery_user_id' => $request->user()?->id,
            'confirmed_at' => $data['status'] === 'ENTREGUE' ? now() : null,
        ]);

        $this->audit->log($request, 'M5', 'ATUALIZAR_ENTREGA', Delivery::class, $delivery->id, ['status' => $data['status']]);

        return back()->with('status', 'Status de entrega atualizado.');
    }
}
