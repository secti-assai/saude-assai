<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Call;
use App\Models\Attendance;
use App\Models\User;
use App\Services\QueueService;
use App\Services\AuditService;
use App\Notifications\DoctorIdleNotification;

class CallController extends Controller
{
    public function call(Request $request, QueueService $queueService, AuditService $auditService)
    {

        $doctor = $request->user();
        if (!$doctor instanceof User) {
            return response()->json(['success' => false, 'message' => 'Usuário não autenticado.'], 401);
        }

        // Buscar todos os atendimentos aguardando (exemplo: status = AGUARDANDO)
        $attendances = Attendance::where('status', 'AGUARDANDO')->get();

        // Monitoramento de ociosidade: se o médico está sem atendimento há mais de 20 minutos, notificar gestor
        $lastCall = $doctor->calls()->latest('called_at')->first();
        $idleMinutes = null;
        if ($lastCall && $lastCall->called_at) {
            $idleMinutes = now()->diffInMinutes($lastCall->called_at);
            if ($idleMinutes >= 20) {
                // Notificar gestor (exemplo: admin_secti)
                $admins = User::where('role', User::ROLE_ADMIN)->get();
                foreach ($admins as $admin) {
                    $admin->notify(new DoctorIdleNotification($doctor, $idleMinutes));
                }
            }
        }

        // Usar o QueueService para selecionar o próximo paciente para o médico
        $attendance = $queueService->nextAttendanceForDoctor($doctor, $attendances);

        // Log de auditoria da decisão da fila
        $auditService->log(
            $request,
            'FILA',
            'DECISAO_PRIORIZACAO',
            Attendance::class,
            $attendance?->id,
            [
                'doctor_id' => $doctor->id,
                'doctor_name' => $doctor->name,
                'idle_minutes' => $idleMinutes,
                'fila_total' => $attendances->count(),
                'attendance_id' => $attendance?->id,
                'attendance_priority_color' => $attendance?->priority_color,
                'attendance_arrived_at' => $attendance?->arrived_at,
                'criteria' => 'especialidade, gravidade, tempo_espera',
            ]
        );

        if (!$attendance) {
            return response()->json(['success' => false, 'message' => 'Nenhum paciente compatível na fila.'], 404);
        }

        $call = Call::create([

            'attendance_id' => $attendance->id,
            'type' => $request->type, // TRIAGEM ou ATENDIMENTO
            'room' => $request->room,
            'status' => 'CHAMADO',
            'called_at' => now(),
        ]);

        // Atualizar status do atendimento para EM_ATENDIMENTO
        $attendance->update(['status' => 'EM_ATENDIMENTO']);

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
