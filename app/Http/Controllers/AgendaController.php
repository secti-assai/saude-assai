<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WomenClinicAppointment;
use App\Models\ClinicScheduleRule;
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

        $dateObj = Carbon::parse($date);
        $weekOfMonth = (int) ceil($dateObj->day / 7);
        $dayOfWeek = $dateObj->dayOfWeek;

        // Busca as regras cadastradas e ativas para a clínica, especialidade e dia da semana
        $rules = ClinicScheduleRule::where('clinic_type', $clinicType)
            ->where('specialty', $specialty)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->orderBy('time')
            ->get();

        $matchedRules = $rules->filter(function ($rule) use ($weekOfMonth) {
            return in_array($weekOfMonth, $rule->weeks_of_month ?? []);
        });

        // Busca registros agendados ignorando CANCELADOS e agrupa por horário
        $busySlots = WomenClinicAppointment::where('clinic_type', $clinicType)
            ->where('specialty', $specialty)
            ->whereDate('scheduled_for', $dateObj)
            ->whereNotIn('status', ['CANCELADO'])
            ->with('citizen:id,full_name,phone')
            ->get()
            ->groupBy(fn ($app) => $app->scheduled_for->format('H:i'));

        $slots = [];

        foreach ($matchedRules as $rule) {
            $timeStr = substr($rule->time, 0, 5);
            $capacity = (int) $rule->capacity;
            $busyArray = $busySlots->get($timeStr) ?? [];

            // Emite os ocupados primeiro
            foreach ($busyArray as $busy) {
                $slots[] = [
                    'time' => $timeStr,
                    'available' => false,
                    'appointment_id' => $busy->id,
                    'appointment_status' => $busy->status,
                    'patient_name' => $busy->citizen->full_name ?? 'Cidadão',
                    'patient_phone' => $busy->citizen->phone ?? '',
                    'is_conected_sus' => true,
                ];
            }

            // Emite os slots livres restantes
            $remaining = max(0, $capacity - count($busyArray));
            for ($i = 0; $i < $remaining; $i++) {
                $slots[] = [
                    'time' => $timeStr,
                    'available' => true,
                    'appointment_id' => null,
                    'appointment_status' => null,
                    'patient_name' => null,
                    'patient_phone' => null,
                    'is_conected_sus' => false,
                ];
            }
        }

        return response()->json($slots);
    }
}