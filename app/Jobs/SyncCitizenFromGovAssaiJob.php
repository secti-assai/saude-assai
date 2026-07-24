<?php

namespace App\Jobs;

use App\Models\Citizen;
use App\Services\GovAssaiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;

class SyncCitizenFromGovAssaiJob implements ShouldQueue
{
    use Dispatchable, Queueable;


    public int $tries = 5;

    public function __construct(public int $citizenId)
    {
    }

    public function backoff(): array
    {
        return [60, 120, 300, 600, 1800];
    }

    public function middleware(): array
    {
        return [
            (new \Illuminate\Queue\Middleware\WithoutOverlapping('gov_assai_sync_citizen_'.$this->citizenId))->releaseAfter(60),
            (new \Illuminate\Queue\Middleware\RateLimited('gov_assai_sync'))
        ];
    }

    public function handle(
        \App\Services\CitizenEligibilityService $eligibilityService, 
        \App\Services\BethaIntegrationService $bethaService
    ): void {
        $citizen = Citizen::find($this->citizenId);

        if (! $citizen || empty($citizen->cpf)) {
            return;
        }

        // Valida e já sincroniza localmente no banco
        $result = $eligibilityService->validateAndSync((string) $citizen->cpf);
        
        $citizen->refresh();

        if (! $result['eligible']) {
            // Falhou em todas as validações (N1, não validado pelo ACS, maior de idade)
            $bethaService->inactivateClient($citizen->cpf, $citizen->cns, $citizen->full_name);
        }
    }
}
