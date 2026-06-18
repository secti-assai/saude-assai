<?php

namespace App\Console\Commands;

use App\Models\Citizen;
use App\Models\PharmacyExternalImportRow;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ApplyValidationDatesCommand extends Command
{
    protected $signature = 'bypass:apply-validation-dates {file}';
    protected $description = 'Aplica a data mais antiga de validação do cidadão (CSV) e recalcula o bypass de dispensações antigas';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("Arquivo não encontrado: {$filePath}");
            return 1;
        }

        $this->info("Lendo arquivo CSV para agregar as datas mais antigas por CPF...");

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $this->error("Não foi possível abrir o arquivo.");
            return 1;
        }

        $header = fgetcsv($handle, 0, ',', '"', "\\");

        $oldestDates = [];

        while (($row = fgetcsv($handle, 0, ',', '"', "\\")) !== false) {
            if (count($header) !== count($row)) {
                continue;
            }

            $data = array_combine($header, $row);

            $cpf = preg_replace('/\D+/', '', $data['CPF'] ?? '');
            if (strlen($cpf) !== 11) {
                continue;
            }

            $dateStr = trim($data['data-liberacao'] ?? '');
            if (empty($dateStr) || strtolower($dateStr) === 'null') {
                continue;
            }

            try {
                $dateObj = Carbon::parse($dateStr);
            } catch (\Exception $e) {
                continue;
            }

            if (!isset($oldestDates[$cpf])) {
                $oldestDates[$cpf] = $dateObj;
            } else {
                if ($dateObj->lt($oldestDates[$cpf])) {
                    $oldestDates[$cpf] = $dateObj;
                }
            }
        }
        fclose($handle);

        $totalFound = count($oldestDates);
        $this->info("Identificados {$totalFound} CPFs únicos com data de validação válida. Processando banco de dados...");

        $this->output->progressStart($totalFound);

        $processed = 0;
        $citizensFound = 0;

        foreach ($oldestDates as $cpf => $oldestDateObj) {
            $cpfHash = hash('sha256', $cpf);
            $citizen = Citizen::where('cpf_hash', $cpfHash)->first();

            if ($citizen) {
                $citizensFound++;
                $dateLimit = $oldestDateObj->format('Y-m-d H:i:s');

                // Bypass mantido (true) para dispensações ANTERIORES à data mais antiga
                PharmacyExternalImportRow::where('citizen_id', $citizen->id)
                    ->where('dispensed_at', '<', $dateLimit)
                    ->update(['bypass_detected' => true]);

                // Bypass perdoado (false) para dispensações A PARTIR da data mais antiga
                PharmacyExternalImportRow::where('citizen_id', $citizen->id)
                    ->where('dispensed_at', '>=', $dateLimit)
                    ->update(['bypass_detected' => false]);
            }

            $processed++;
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        $this->info("Recálculo concluído! {$citizensFound} cidadãos encontrados e recalculados no banco.");

        return 0;
    }
}
