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
        $user = auth()->user();
        $isCentral = in_array($user?->role, ['admin_secti', 'gestor', 'auditor'], true);
        $unitId = $user?->health_unit_id;

        $attendanceQuery = Attendance::query();
        $triageQuery = Triage::query();
        $prescriptionQuery = \App\Models\Prescription::query();
        $dispensationQuery = Dispensation::query();

        if (! $isCentral && $unitId) {
            $attendanceQuery->where('health_unit_id', $unitId);

            $triageQuery->whereHas('attendance', function ($query) use ($unitId) {
                $query->where('health_unit_id', $unitId);
            });

            $prescriptionQuery->whereHas('attendance', function ($query) use ($unitId) {
                $query->where('health_unit_id', $unitId);
            });

            $dispensationQuery->whereHas('prescription.attendance', function ($query) use ($unitId) {
                $query->where('health_unit_id', $unitId);
            });
        }

        $stats = [
            'atendimentos' => (clone $attendanceQuery)->whereDate('created_at', today())->count(),
            'triagens' => (clone $triageQuery)->whereDate('created_at', today())->count(),
            'prescricoes' => (clone $prescriptionQuery)->whereDate('created_at', today())->count(),
            'dispensacoes' => (clone $dispensationQuery)->whereDate('created_at', today())->count(),
        ];

        $usageRaw = User::query()
            ->select('users.id', 'users.name', 'users.role')
            ->when(! $isCentral && $unitId, fn ($query) => $query->where('health_unit_id', $unitId))
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
