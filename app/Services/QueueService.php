<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Collection;

class QueueService
{
    /**
     * Calcula o tempo médio de atendimento do médico com base no histórico.
     */
    public function getDoctorAverageTime(User $doctor): float
    {
        // Busca os atendimentos finalizados do médico
        $attendances = $doctor->prescriptions() // ou outro relacionamento de atendimentos finalizados
            ->whereNotNull('created_at')
            ->whereNotNull('updated_at')
            ->whereColumn('created_at', '<', 'updated_at')
            ->orderByDesc('updated_at')
            ->take(30)
            ->get();

        if ($attendances->isEmpty()) {
            return 15.0; // valor padrão em minutos
        }

        $total = $attendances->reduce(function ($carry, $attendance) {
            $diff = $attendance->updated_at->diffInSeconds($attendance->created_at);
            return $carry + $diff;
        }, 0);

        return round($total / $attendances->count() / 60, 1); // minutos
    }

    /**
     * Retorna a próxima chamada da fila considerando:
     * - Capacidade do médico
     * - Tempo médio de atendimento dinâmico
     * - Especialidade
     * - Gravidade (classificação de risco)
     * - Tempo de espera
     */
    public function nextAttendanceForDoctor(User $doctor, Collection $attendances): ?Attendance
    {
        // Filtrar por especialidade
        $filtered = $attendances->filter(function ($attendance) use ($doctor) {
            // Supondo que Attendance tenha campo 'required_specialty' e User tenha 'role' ou 'specialty'
            return empty($attendance->required_specialty) || $attendance->required_specialty === $doctor->role;
        });

        // Ordenar por gravidade (prioridade), tempo de espera e chegada
        $ordered = $filtered->sort(function ($a, $b) {
            $priorityOrder = [
                'VERMELHO' => 1,
                'LARANJA' => 2,
                'AMARELO' => 3,
                'VERDE' => 4,
                'AZUL' => 5,
            ];
            $aPriority = $priorityOrder[$a->priority_color ?? 'AZUL'] ?? 99;
            $bPriority = $priorityOrder[$b->priority_color ?? 'AZUL'] ?? 99;
            if ($aPriority !== $bPriority) {
                return $aPriority <=> $bPriority;
            }
            // Se mesma prioridade, ordenar por tempo de espera
            $aWait = now()->diffInSeconds($a->arrived_at ?? now());
            $bWait = now()->diffInSeconds($b->arrived_at ?? now());
            if ($aWait !== $bWait) {
                return $bWait <=> $aWait; // maior tempo de espera primeiro
            }
            // Se empate, por ordem de chegada
            return ($a->arrived_at ?? now()) <=> ($b->arrived_at ?? now());
        });

        // Aqui pode-se adicionar lógica de capacidade baseada no tempo médio
        // Exemplo: se o médico está com muitos atendimentos em aberto, não retorna próximo
        // (Necessário implementar controle de atendimentos em andamento por médico)

        return $ordered->first();
    }

    /**
     * Retorna médicos disponíveis para um determinado atendimento
     */
    public function availableDoctorsForAttendance(Attendance $attendance, Collection $doctors): Collection
    {
        // Exemplo: filtrar médicos pela especialidade necessária
        return $doctors->filter(function ($doctor) use ($attendance) {
            return empty($attendance->required_specialty) || $doctor->role === $attendance->required_specialty;
        });
    }
}
