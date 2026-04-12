<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEventToGovAssaiJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = [10, 30, 60, 120, 300];

    protected $queueItemId;

    /**
     * Create a new job instance.
     */
    public function __construct($queueItemId)
    {
        $this->queueItemId = $queueItemId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $queueItem = \App\Models\GovAssaiEventoQueue::find($this->queueItemId);

        if (!$queueItem || $queueItem->status_envio === 'enviado') {
            return;
        }

        $queueItem->increment('tentativas');
        $queueItem->update(['ultima_tentativa_em' => now()]);

        $baseUrl = config('services.gov_assai.base_url');
        $apiKey = config('services.gov_assai.api_key');

        if (empty($baseUrl) || empty($apiKey)) {
            \Log::warning('GovAssai API missing configuration.');
            $this->release(60);
            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'X-API-Key' => $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->timeout(config('services.gov_assai.timeout', 15))
              ->post(rtrim($baseUrl, '/') . '/api/saude/servicos', $queueItem->payload_json);

            $queueItem->update([
                'ultima_resposta_http' => $response->status(),
                'ultima_resposta_body' => $response->body(),
            ]);

            if ($response->successful()) {
                $queueItem->update([
                    'status_envio' => 'enviado',
                    'enviado_em' => now(),
                ]);
            } elseif ($response->status() === 422) {
                $queueItem->update(['status_envio' => 'erro_validacao']);
                \Log::error('Validation error when sending to GovAssai', ['payload' => $queueItem->payload_json, 'response' => $response->json()]);
            } else {
                $queueItem->update(['status_envio' => 'erro_http']);
                $this->release($this->backoff[$this->attempts() - 1] ?? 600);
            }
        } catch (\Exception $e) {
            $queueItem->update([
                'status_envio' => 'falha_rede',
                'ultima_resposta_body' => $e->getMessage(),
            ]);
            $this->release($this->backoff[$this->attempts() - 1] ?? 600);
        }
    }
}
