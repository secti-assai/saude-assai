<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WomenClinicAppointment;
use Carbon\Carbon;

class AgendaController extends Controller
{
    public function getSlots(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));
        $specialty = WomenClinicAppointment::normalizeSpecialty($request->get('specialty'));
        $clinicType = WomenClinicAppointment::normalizeClinicType($request->get('clinic_type', 'CLINICA_MULHER'));

        if (!$specialty) {
            return response()->json(['error' => 'Especialidade inválida'], 400);
        }

        $duration = config("clinic.durations.{$specialty}", config('clinic.durations.DEFAULT'));
        
        $start = Carbon::parse($date . ' ' . config('clinic.work_hours.start'));
        $end = Carbon::parse($date . ' ' . config('clinic.work_hours.end'));
        $lunchStart = Carbon::parse($date . ' ' . config('clinic.work_hours.lunch_start'));
        $lunchEnd = Carbon::parse($date . ' ' . config('clinic.work_hours.lunch_end'));

        // Busy slots
        $busySlots = WomenClinicAppointment::where('clinic_type', $clinicType)
            ->where('specialty', $specialty)
            ->whereDate('scheduled_for', $date)
            ->whereNotIn('status', ['CANCELADO'])
            ->with('citizen:id,full_name,phone')
            ->get()
            ->keyBy(fn ($app) => $app->scheduled_for->format('H:i'));

        $slots = [];
        $current = $start->copy();

        while ($current->lt($end)) {
            // Skip lunch
            if ($current->gte($lunchStart) && $current->lt($lunchEnd)) {
                $current->addMinutes($duration);
                continue;
            }

            $timeStr = $current->format('H:i');
            $busy = $busySlots->get($timeStr);

            $slots[] = [
                'time' => $timeStr,
                'available' => !$busy,
                'patient_name' => $busy ? ($busy->citizen->full_name ?? 'Cidadão') : null,
                'patient_phone' => $busy ? ($busy->citizen->phone ?? '') : null,
                'is_conected_sus' => $busy ? true : false, // Simulating PEC UI
            ];

            $current->addMinutes($duration);
        }

        return response()->json($slots);
    }
}
