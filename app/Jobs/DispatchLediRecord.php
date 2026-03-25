<?php

namespace App\Jobs;

use App\Models\LediLog;
use App\Models\LediQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchLediRecord implements ShouldQueue
{
    use Queueable;

    public string $queue = 'ledi';

    public int $tries = 5;

    public function __construct(public int $lediQueueId)
    {
    }

    public function backoff(): array
    {
        return [5, 15, 30, 60, 120];
    }

    public function handle(): void
    {
        $item = LediQueue::find($this->lediQueueId);

        if (! $item || $item->status === 'ENVIADO') {
            return;
        }

        try {
            // MVP: simula chamada LEDI APS/PEC com sucesso.
            $item->update([
                'status' => 'ENVIADO',
                'attempts' => $item->attempts + 1,
                'processed_at' => now(),
                'last_error' => null,
            ]);

            LediLog::create([
                'ledi_queue_id' => $item->id,
                'status' => 'ENVIADO',
                'response' => 'Mock LEDI APS 7.3.3 accepted',
            ]);
        } catch (\Throwable $e) {
            $item->update([
                'status' => 'ERRO',
                'attempts' => $item->attempts + 1,
                'last_error' => $e->getMessage(),
            ]);

            LediLog::create([
                'ledi_queue_id' => $item->id,
                'status' => 'ERRO',
                'response' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
