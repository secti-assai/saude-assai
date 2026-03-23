<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Delivery;
use App\Models\Dispensation;
use App\Models\LediQueue;
use App\Models\Triage;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $kpis = [
            'atendimentos_hoje' => Attendance::whereDate('created_at', today())->count(),
            'fila_ubs' => Attendance::whereIn('status', ['RECEPCAO', 'TRIAGEM_CONCLUIDA'])->count(),
            'entregas_hoje' => Delivery::whereDate('created_at', today())->count(),
            'ledis_pendentes' => LediQueue::where('status', 'PENDENTE')->count(),
            'ledis_enviadas' => LediQueue::where('status', 'ENVIADO')->count(),
            'triagens_hoje' => Triage::whereDate('created_at', today())->count(),
            'dispensacoes_hoje' => Dispensation::whereDate('created_at', today())->count(),
        ];

        $usage = User::query()
            ->select('users.id', 'users.name', 'users.role')
            ->withCount('triages')
            ->withCount('prescriptions')
            ->get();

        return view('dashboard', compact('kpis', 'usage'));
    }
}
