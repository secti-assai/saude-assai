<?php

namespace App\Console\Commands;

use App\Models\Citizen;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportESUSCitizensCommand extends Command
{
    protected $signature = 'import:esus-citizens {file}';
    protected $description = 'Importa cidadãos do arquivo e-SUS PEC (CSV)';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("Arquivo não encontrado: {$filePath}");
            return 1;
        }

        $this->info("Iniciando importação de cidadãos do e-SUS...");

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $this->error("Não foi possível abrir o arquivo.");
            return 1;
        }

        $header = fgetcsv($handle, 0, ',', '"', "\\");
        if (!$header) {
            $this->error("O arquivo está vazio ou o cabeçalho é inválido.");
            return 1;
        }

        $count = 0;
        $skipped = 0;
        $batch = [];
        $batchSize = 500;

        $this->output->progressStart();

        while (($row = fgetcsv($handle, 0, ',', '"', "\\")) !== false) {
            if (count($header) !== count($row)) {
                continue;
            }

            $data = array_combine($header, $row);

            $cpf = trim($data['cpf'] ?? '');
            if ($cpf === '' || strtolower($cpf) === 'null') {
                $skipped++;
                continue;
            }

            $cleanCpf = preg_replace('/\D+/', '', $cpf);
            if (strlen($cleanCpf) !== 11) {
                $skipped++;
                continue;
            }

            $cpfHash = hash('sha256', $cleanCpf);

            $addressParts = array_filter([
                $data['rua'] !== 'NULL' ? $data['rua'] : null,
                $data['numero'] !== 'NULL' ? $data['numero'] : null,
                $data['bairro'] !== 'NULL' ? $data['bairro'] : null,
                $data['cep'] !== 'NULL' ? $data['cep'] : null,
            ]);

            $address = !empty($addressParts) ? implode(', ', $addressParts) : null;
            $phone = ($data['celular'] !== 'NULL' && $data['celular'] !== '') ? $data['celular'] : null;
            $cns = ($data['cns'] !== 'NULL' && $data['cns'] !== '') ? $data['cns'] : null;

            $batch[] = [
                'cpf_hash' => $cpfHash,
                'cpf' => $cleanCpf,
                'full_name' => $data['nome'] !== 'NULL' ? strtoupper(trim($data['nome'])) : 'NOME NAO INFORMADO',
                'social_name' => null,
                'birth_date' => ($data['dt_nascimento'] !== 'NULL' && $data['dt_nascimento'] !== '') ? $data['dt_nascimento'] : '1900-01-01',
                'sexo' => ($data['sexo'] !== 'NULL' && $data['sexo'] !== '') ? substr($data['sexo'], 0, 1) : null,
                'address' => $address,
                'phone' => $phone,
                'cns' => $cns,
                'is_resident_assai' => true,
                'pharmacy_lock_flag' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= $batchSize) {
                $this->upsertBatch($batch);
                $count += count($batch);
                $batch = [];
                $this->output->progressAdvance($batchSize);
            }
        }

        if (count($batch) > 0) {
            $this->upsertBatch($batch);
            $count += count($batch);
            $this->output->progressAdvance(count($batch));
        }

        fclose($handle);
        $this->output->progressFinish();

        $this->info("Importação concluída. Importados/Atualizados: {$count} | Ignorados (Sem CPF ou Inválido): {$skipped}");

        return 0;
    }

    private function upsertBatch(array $batch): void
    {
        DB::transaction(function () use ($batch) {
            foreach ($batch as $data) {
                $cpfHash = $data['cpf_hash'];
                unset($data['cpf_hash']);
                Citizen::updateOrCreate(['cpf_hash' => $cpfHash], $data);
            }
        });
    }
}
