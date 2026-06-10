<?php

namespace App\Jobs;

use App\Models\PharmacyExternalImportRow;
use App\Services\GovAssaiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class SweepImportedBypassesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $deleteWhenMissingModels = true;
    public $timeout = 3600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly ?int $importBatchId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GovAssaiService $govAssai): void
    {
        $query = PharmacyExternalImportRow::query()
            ->with(['citizen', 'centralPharmacyRequest'])
            ->where('bypass_detected', true);

        if ($this->importBatchId !== null) {
            $query->where('import_id', $this->importBatchId);
        }

        $query->whereHas('centralPharmacyRequest', function ($q) {
            $q->whereIn('gov_assai_level', ['0', '']);
        });

        // Get 100 UNIQUE citizens that need processing to avoid timeouts
        // By processing unique citizens, we can update ALL their rows at once.
        $rows = $query->take(200)->get();

        if ($rows->isEmpty()) {
            return; // Done!
        }

        $citizensCache = [];

        foreach ($rows as $row) {
            $citizen = $row->citizen;
            $request = $row->centralPharmacyRequest;

            if (! $citizen || ! $request) {
                continue;
            }

            if (str_starts_with($citizen->cpf, 'SYNC-')) {
                continue;
            }

            if (! array_key_exists($citizen->cpf, $citizensCache)) {
                try {
                    $govResponse = $govAssai->fetchCitizenByCpf($citizen->cpf);

                    if ($govResponse && ($govResponse['status'] ?? 200) === 429) {
                        Log::warning("SweepImportedBypassesJob: Rate limit hit (429). Delaying next batch by 60 seconds.");
                        self::dispatch($this->importBatchId)->delay(now()->addSeconds(60));
                        return; // Stop processing this batch
                    }

                    $citizensCache[$citizen->cpf] = $govResponse;
                    usleep(1500000); // 1.5s sleep per UNIQUE citizen to avoid rate limits
                } catch (\Throwable $e) {
                    Log::error("SweepImportedBypassesJob: Failed to sync citizen {$citizen->id} ({$citizen->cpf}): {$e->getMessage()}");
                    $citizensCache[$citizen->cpf] = null;
                }
            }

            $govResponse = $citizensCache[$citizen->cpf];

            if ($govResponse && ($govResponse['success'] ?? false) === true && is_array($govResponse['data'] ?? null)) {
                $govData = $govResponse['data'];
                $level = $this->extractGovLevel($govData);
                $resolvedLevel = $level !== null ? (int) $level : 0;
                $origem = Arr::get($govResponse, 'origem');
                
                $isEsusIntegration = $origem === 'integracao_esus';

                if ($resolvedLevel >= 2 || $isEsusIntegration) {
                    $request->update(['gov_assai_level' => (string) $resolvedLevel]);
                    $row->update(['bypass_detected' => false]);

                    if ((bool) $citizen->pharmacy_lock_flag) {
                        $citizen->update(['pharmacy_lock_flag' => false]);
                    }
                    Log::info("SweepImportedBypassesJob: Corrected citizen {$citizen->id} to level {$resolvedLevel}. Row {$row->id} is no longer bypass.");
                } else {
                    $request->update(['gov_assai_level' => (string) $resolvedLevel]);
                }
            } else {
                 // Even if GovAssai fails or returns not found, we should mark the request so it's not '0' and infinitely retried?
                 // No, if the API is down, we want to retry later. But if it's genuinely 0, the next time it will retry unless we set it to '1' or '-1'.
                 // Let's set it to '1' if it's found but level is 0, so it stops retrying.
                 if ($govResponse && ($govResponse['status'] ?? 0) === 404) {
                     $request->update(['gov_assai_level' => '1']); // Mark as processed (1 means not authorized but checked)
                 }
            }
        }

        // If there are more rows, dispatch another job to continue processing
        $hasMore = PharmacyExternalImportRow::query()
            ->where('bypass_detected', true)
            ->when($this->importBatchId !== null, fn($q) => $q->where('import_id', $this->importBatchId))
            ->whereHas('centralPharmacyRequest', function ($q) {
                $q->whereIn('gov_assai_level', ['0', '']);
            })
            ->exists();

        if ($hasMore) {
            self::dispatch($this->importBatchId)->delay(now()->addSeconds(5));
        }
    }

    private function extractGovLevel(array $data): ?string
    {
        $candidate = Arr::first([
            Arr::get($data, 'gov_assai.nivel'),
            Arr::get($data, 'gov_assai.nivel_conta'),
            Arr::get($data, 'cidadao.nivel'),
            Arr::get($data, 'cidadao.nivel_conta'),
            Arr::get($data, 'usuario.nivel'),
            Arr::get($data, 'nivel'),
        ], fn ($value) => $this->normalizeGovLevelValue($value) !== null);

        return $this->normalizeGovLevelValue($candidate);
    }

    private function normalizeGovLevelValue(mixed $value, int $depth = 0): ?string
    {
        if ($value === null || $depth > 4) {
            return null;
        }

        if (is_int($value) || is_float($value) || is_string($value) || is_bool($value)) {
            $normalized = trim((string) $value);
            return $normalized !== '' ? $normalized : null;
        }

        if (is_object($value)) {
            $value = (array) $value;
        }

        if (! is_array($value)) {
            return null;
        }

        foreach (['nivel', 'nivel_conta', 'value', 'valor', 'codigo', 'id'] as $key) {
            if (array_key_exists($key, $value)) {
                $nested = $this->normalizeGovLevelValue($value[$key], $depth + 1);
                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        foreach ($value as $item) {
            $nested = $this->normalizeGovLevelValue($item, $depth + 1);
            if ($nested !== null) {
                return $nested;
            }
        }

        return null;
    }
}
