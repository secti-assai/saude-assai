<?php

namespace App\Jobs;

use App\Models\Citizen;
use App\Services\GovAssaiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncCitizenFromGovAssaiJob implements ShouldQueue
{
    use Queueable;

    public string $queue = 'gov_assai';

    public int $tries = 5;

    public function __construct(public int $citizenId)
    {
    }

    public function backoff(): array
    {
        return [5, 15, 30, 60, 120];
    }

    public function handle(GovAssaiService $govAssai): void
    {
        $citizen = Citizen::find($this->citizenId);

        if (! $citizen || empty($citizen->cpf)) {
            return;
        }

        $result = $govAssai->fetchCitizenByCpf((string) $citizen->cpf);

        if (! $result['success']) {
            return;
        }

        $govCitizen = data_get($result, 'data.cidadao', []);
        $govHealth = data_get($result, 'data.saude', []);
        $govContact = data_get($result, 'data.contato', []);
        $govAddress = data_get($result, 'data.endereco', []);

        $resolvedAddress = trim(implode(', ', array_filter([
            $govAddress['logradouro'] ?? null,
            $govAddress['numero'] ?? null,
            $govAddress['bairro'] ?? null,
            $govAddress['distrito'] ?? null,
        ], fn ($value) => $value !== null && $value !== '')));

        $citizen->update([
            'full_name' => $govCitizen['nome'] ?? $citizen->full_name,
            'social_name' => $govCitizen['nome_social'] ?? $citizen->social_name,
            'birth_date' => $govCitizen['data_nascimento'] ?? $citizen->birth_date,
            'sexo' => $govCitizen['sexo'] ?? $citizen->sexo,
            'cns' => $govHealth['cns_numero'] ?? $citizen->cns,
            'phone' => $govContact['celular'] ?? $citizen->phone,
            'email' => $govContact['email'] ?? $citizen->email,
            'address' => $resolvedAddress !== '' ? $resolvedAddress : $citizen->address,
            'is_resident_assai' => true,
            'residence_validated_at' => now(),
        ]);
    }
}
