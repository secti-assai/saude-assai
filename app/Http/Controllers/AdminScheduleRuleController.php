<?php

namespace App\Http\Controllers;

use App\Models\ClinicScheduleRule;
use App\Models\WomenClinicAppointment;
use Illuminate\Http\Request;

class AdminScheduleRuleController extends Controller
{
    public function index()
    {
        $rules = ClinicScheduleRule::orderBy('clinic_type')
            ->orderBy('specialty')
            ->orderBy('day_of_week')
            ->orderBy('time')
            ->get();

        return view('admin.schedules.index', compact('rules'));
    }

    public function create()
    {
        return view('admin.schedules.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'clinic_type' => 'required|string',
            'specialty' => 'required|string',
            'day_of_week' => 'required|integer|min:0|max:6',
            'weeks_of_month' => 'required|array',
            'weeks_of_month.*' => 'integer|min:1|max:5',
            'time' => 'required|date_format:H:i',
            'capacity' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        // Optional: Check for duplicate rule (same clinic, specialty, day, time)
        // A more advanced check would also verify intersecting weeks_of_month.
        
        ClinicScheduleRule::create($validated);

        return redirect()->route('admin.schedules.index')
            ->with('status', 'Regra de horário criada com sucesso.');
    }

    public function edit(ClinicScheduleRule $schedule)
    {
        return view('admin.schedules.edit', compact('schedule'));
    }

    public function update(Request $request, ClinicScheduleRule $schedule)
    {
        $validated = $request->validate([
            'clinic_type' => 'required|string',
            'specialty' => 'required|string',
            'day_of_week' => 'required|integer|min:0|max:6',
            'weeks_of_month' => 'required|array',
            'weeks_of_month.*' => 'integer|min:1|max:5',
            'time' => 'required|date_format:H:i',
            'capacity' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $schedule->update($validated);

        return redirect()->route('admin.schedules.index')
            ->with('status', 'Regra de horário atualizada com sucesso.');
    }

    public function destroy(ClinicScheduleRule $schedule)
    {
        $schedule->delete();

        return redirect()->route('admin.schedules.index')
            ->with('status', 'Regra de horário removida com sucesso.');
    }
}
