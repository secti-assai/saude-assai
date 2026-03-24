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
        $stats = [
            'atendimentos' => Attendance::whereDate('created_at', today())->count(),
            'triagens' => Triage::whereDate('created_at', today())->count(),
            'prescricoes' => \App\Models\Prescription::whereDate('created_at', today())->count(),
            'dispensacoes' => Dispensation::whereDate('created_at', today())->count(),
        ];

        $usageRaw = User::query()
            ->select('users.id', 'users.name', 'users.role')
            ->withCount('triages')
            ->withCount('prescriptions')
            ->get();

        $usage = $usageRaw->map(function ($user) {
            return [
                'name' => $user->name,
                'role' => $user->role,
                'actions' => $user->triages_count + $user->prescriptions_count,
                'last' => 'Hoje'
            ];
        })->toArray();

        return view('dashboard', compact('stats', 'usage'));
    }
}
