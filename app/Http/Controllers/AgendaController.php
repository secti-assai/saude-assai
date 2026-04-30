<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WomenClinicAppointment;
use Carbon\Carbon;

class AgendaController extends Controller
{
    private function getSpecialAgendaRules(string $clinicType, string $specialty, Carbon $date): ?array
    {
        if ($clinicType === WomenClinicAppointment::CLINIC_WOMEN) {
            if ($specialty === WomenClinicAppointment::SPECIALTY_ORTOPEDIA) {
                // 25 pessoas das 07h30 as 09h00 (qualquer dia que tenha agenda aberta)
                return [
                    ['time' => '07:30', 'capacity' => WomenClinicAppointment::getSlotCapacity($clinicType, $specialty, $date, '07:30')],
                ];
            }
            if ($specialty === WomenClinicAppointment::SPECIALTY_CARDIOLOGIA) {
                // 50 pessoas das 14 as 17h, somente a terca-feira
                if ($date->dayOfWeek === Carbon::TUESDAY) {
                    return [
                        ['time' => '14:00', 'capacity' => WomenClinicAppointment::getSlotCapacity($clinicType, $specialty, $date, '14:00')],
                    ];
                }
                return []; // Vazio nos outros dias para bloquear agendamentos
            }
        }
        return null;
    }

    public function getSlots(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));
        $specialty = WomenClinicAppointment::normalizeSpecialty($request->get('specialty'));
        $clinicType = WomenClinicAppointment::normalizeClinicType($request->get('clinic_type', 'CLINICA_MULHER'));

        if (!$specialty) {
            return response()->json(['error' => 'Especialidade inválida'], 400);
        }

        $dateObj = Carbon::parse($date);
        $specialRules = $this->getSpecialAgendaRules($clinicType, $specialty, $dateObj);

        // Busca registros ignorando CANCELADOS e agrupa TODOS por horario
        $busySlots = WomenClinicAppointment::where('clinic_type', $clinicType)
            ->where('specialty', $specialty)
            ->whereDate('scheduled_for', $dateObj)
            ->whereNotIn('status', ['CANCELADO'])
            ->with('citizen:id,full_name,phone')
            ->get()
            ->groupBy(fn ($app) => $app->scheduled_for->format('H:i'));

        $slots = [];

        if ($specialRules !== null) {
            // Usa as regras customizadas (agendamento em massa / encaixe configurado)
            foreach ($specialRules as $rule) {
                $timeStr = $rule['time'];
                $capacity = $rule['capacity'];
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
                $remaining = $capacity - count($busyArray);
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
        } else {
            // Regra comum baseada em minutos por paciente
            $duration = config("clinic.durations.{$specialty}", config('clinic.durations.DEFAULT'));
            
            $start = Carbon::parse($date . ' ' . config('clinic.work_hours.start'));
            $end = Carbon::parse($date . ' ' . config('clinic.work_hours.end'));
            $lunchStart = Carbon::parse($date . ' ' . config('clinic.work_hours.lunch_start'));
            $lunchEnd = Carbon::parse($date . ' ' . config('clinic.work_hours.lunch_end'));

            $current = $start->copy();

            while ($current->lt($end)) {
                // Pular o almoco
                if ($current->gte($lunchStart) && $current->lt($lunchEnd)) {
                    $current->addMinutes($duration);
                    continue;
                }

                $timeStr = $current->format('H:i');
                $busyArray = $busySlots->get($timeStr) ?? [];
                $capacity = 1; // Default: 1 pessoa por slot especifico
                
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

                $remaining = $capacity - count($busyArray);
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

                $current->addMinutes($duration);
            }
        }

        return response()->json($slots);
    }
}