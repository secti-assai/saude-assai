<?php

namespace App\Services;

class ManchesterRiskService
{
    public function classify(array $data): array
    {
        $spo2 = (int) ($data['spo2'] ?? 0);
        $sys = (int) ($data['systolic_pressure'] ?? 0);
        $fc = (int) ($data['heart_rate'] ?? 0);
        $hgt = (int) ($data['hgt'] ?? 0);
        $t = (float) ($data['temperature'] ?? 0);
        $consciousness = strtoupper((string) ($data['consciousness_level'] ?? 'LUCIDO'));

        if ($consciousness === 'INCONSCIENTE' || $spo2 < 90 || ($sys > 0 && $sys < 80) || $fc > 150 || ($hgt > 0 && $hgt < 50)) {
            return ['color' => 'VERMELHO', 'classification' => 'EMERGENCIA'];
        }

        if (($spo2 >= 90 && $spo2 <= 93) || ($sys > 0 && $sys < 90) || ($fc >= 130 && $fc <= 150) || ($hgt >= 50 && $hgt <= 70) || $t > 39) {
            return ['color' => 'LARANJA', 'classification' => 'MUITO_URGENTE'];
        }

        if ($sys > 180 || $fc > 120 || $t > 38 || $consciousness === 'CONFUSO') {
            return ['color' => 'AMARELO', 'classification' => 'URGENTE'];
        }

        if ($consciousness === 'LUCIDO') {
            return ['color' => 'VERDE', 'classification' => 'POUCO_URGENTE'];
        }

        return ['color' => 'AZUL', 'classification' => 'NAO_URGENTE'];
    }
}
