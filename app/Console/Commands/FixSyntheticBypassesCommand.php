<?php

namespace App\Console\Commands;

use App\Models\Citizen;
use App\Models\CentralPharmacyRequest;
use App\Models\PharmacyExternalImportRow;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FixSyntheticBypassesCommand extends Command
{
    protected $signature = 'bypass:fix-synthetics';
    protected $description = 'Mescla cidadãos sintéticos gerados por bypass com os reais recém-importados';

    public function handle()
    {
        $this->info("Buscando cidadãos sintéticos para mesclar...");

        $synthetics = Citizen::where('birth_date', '1900-01-01')->get()->filter(function ($c) {
            return Str::startsWith($c->cpf, 'SYNC-') || strlen(preg_replace('/\D/', '', $c->cpf)) === 11;
        });

        if ($synthetics->isEmpty()) {
            $this->info("Nenhum cidadão sintético encontrado.");
            return 0;
        }

        $this->info("Encontrados {$synthetics->count()} cidadãos sintéticos.");
        $mergedCount = 0;

        foreach ($synthetics as $synthetic) {
            $normalizedName = $this->normalizeName($synthetic->full_name);

            // Fast path: Exact match (case insensitive)
            $realCitizen = Citizen::where('id', '!=', $synthetic->id)
                ->where('birth_date', '!=', '1900-01-01')
                ->whereRaw('LOWER(full_name) = ?', [strtolower($synthetic->full_name)])
                ->get()
                ->filter(function ($c) { return !Str::startsWith($c->cpf, 'SYNC-'); })
                ->first();

            // Slow path: Fuzzy match
            if (!$realCitizen) {
                // To avoid OOM, only fetch citizens that have at least one word from the name
                $words = explode(' ', $normalizedName);
                $query = Citizen::where('id', '!=', $synthetic->id)
                    ->where('birth_date', '!=', '1900-01-01');

                if (count($words) > 0) {
                    $query->where(function($q) use ($words) {
                        foreach ($words as $word) {
                            if (strlen($word) > 3) {
                                $q->orWhere('full_name', 'like', '%' . $word . '%');
                            }
                        }
                    });
                }

                $realCitizen = $query->get()
                    ->filter(function ($c) { return !Str::startsWith($c->cpf, 'SYNC-'); })
                    ->first(function ($c) use ($normalizedName) {
                        $cNormalized = $this->normalizeName($c->full_name);
                        if ($cNormalized === $normalizedName) { return true; }
                        similar_text($normalizedName, $cNormalized, $percent);
                        return $percent >= 92;
                    });
            }

            if ($realCitizen) {
                $this->info("Mesclando sintético [{$synthetic->id}] {$synthetic->full_name} -> Real [{$realCitizen->id}] {$realCitizen->full_name} ({$realCitizen->cpf})");

                CentralPharmacyRequest::where('citizen_id', $synthetic->id)
                    ->update(['citizen_id' => $realCitizen->id]);

                PharmacyExternalImportRow::where('citizen_id', $synthetic->id)
                    ->update(['citizen_id' => $realCitizen->id]);

                $bestLevel = CentralPharmacyRequest::where('citizen_id', $realCitizen->id)
                    ->whereNotNull('gov_assai_level')
                    ->orderByDesc('gov_assai_level')
                    ->value('gov_assai_level');

                $resolvedLevel = ($bestLevel !== null && (int) $bestLevel >= 2) ? $bestLevel : '0';
                $bypassDetected = (int) $resolvedLevel < 2 && !$realCitizen->is_resident_assai;

                // Opção Híbrida: Perdoar apenas as dispensações a partir de 15/06/2026
                PharmacyExternalImportRow::where('citizen_id', $realCitizen->id)
                    ->where('bypass_detected', true)
                    ->where('created_at', '>=', '2026-06-15 00:00:00')
                    ->update(['bypass_detected' => $bypassDetected]);

                $synthetic->delete();
                $mergedCount++;
            } else {
                $this->warn("Real citizen not found for: {$synthetic->full_name}");
            }
        }

        $this->info("Concluído. {$mergedCount} cidadãos sintéticos foram mesclados e corrigidos.");
        return 0;
    }

    private function normalizeName(string $name): string
    {
        $normalized = Str::upper(Str::ascii($name));
        $normalized = preg_replace('/[^A-Z0-9\s]/', ' ', $normalized) ?? '';
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? '';
        return trim($normalized);
    }
}
