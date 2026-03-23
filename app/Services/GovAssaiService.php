<?php

namespace App\Services;

class GovAssaiService
{
    public function validateResidence(string $cpf): string
    {
        // MVP: simula integracao com Gov.Assai por regra deterministica.
        $lastDigit = (int) substr($cpf, -1);

        if ($lastDigit % 5 === 0) {
            return 'PENDENTE';
        }

        return $lastDigit % 2 === 0 ? 'RESIDENTE' : 'NAO_RESIDENTE';
    }
}
