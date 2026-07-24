<?php

namespace App\Jobs;

use App\Models\Citizen;
use App\Services\BethaIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncCitizenToBethaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public int $citizenId)
    {
    }

    public function backoff(): array
    {
        return [60, 120, 300, 600, 1800];
    }

    public function handle(BethaIntegrationService $bethaService): void
    {
        $citizen = Citizen::find($this->citizenId);

        if (! $citizen) {
            return;
        }

        $bethaService->syncClient($citizen, false);
    }
}
