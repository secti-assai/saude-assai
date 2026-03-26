<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Call;
use App\Models\Attendance;

class CallController extends Controller
{
    public function call(Request $request, Attendance $attendance)
    {
        $request->validate([
            'type' => ['required', 'in:TRIAGEM,ATENDIMENTO'],
        ]);

        $call = \App\Models\Call::create([
            'attendance_id' => $attendance->id,
            'type' => $request->type, // TRIAGEM ou ATENDIMENTO
            'room' => $request->room,
            'status' => 'CHAMADO',
            'called_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'call' => $call->load('attendance.citizen')
        ]);
    }

    public function panel($unit)
    {
        $unit = \App\Models\HealthUnit::where('id', $unit)
            ->firstOrFail();

        return view('panel.calls', compact('unit'));
    }
}
