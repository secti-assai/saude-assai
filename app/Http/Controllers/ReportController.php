<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Dispensation;
use App\Models\Triage;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function conformity(): View
    {
        $rows = User::query()
            ->select('id', 'name', 'role')
            ->get()
            ->map(function (User $user) {
                $attendances = Attendance::where('reception_user_id', $user->id)->count();
                $triages = Triage::where('nurse_user_id', $user->id)->count();
                $dispensations = Dispensation::where('pharmacist_user_id', $user->id)->count();
                $total = $attendances + $triages + $dispensations;
                $expected = max(1, $attendances);

                return [
                    'name' => $user->name,
                    'role' => $user->role,
                    'attendances' => $attendances,
                    'triages' => $triages,
                    'dispensations' => $dispensations,
                    'conformity' => round(($total / $expected) * 100, 1),
                ];
            });

        return view('reports.conformity', compact('rows'));
    }

    public function conformityCsv(): Response
    {
        $lines = ["nome;perfil;atendimentos;triagens;dispensacoes;indice_conformidade"]; 

        User::all()->each(function (User $user) use (&$lines) {
            $att = Attendance::where('reception_user_id', $user->id)->count();
            $tri = Triage::where('nurse_user_id', $user->id)->count();
            $dis = Dispensation::where('pharmacist_user_id', $user->id)->count();
            $idx = round((($att + $tri + $dis) / max(1, $att)) * 100, 1);
            $lines[] = implode(';', [$user->name, $user->role, $att, $tri, $dis, $idx]);
        });

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="conformidade_uso.csv"',
        ]);
    }
}
