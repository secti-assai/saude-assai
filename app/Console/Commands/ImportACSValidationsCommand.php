<?php

namespace App\Console\Commands;

use App\Models\Citizen;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportACSValidationsCommand extends Command
{
    protected $signature = 'import:acs-validations {file}';
    protected $description = 'Importa as validações de residência feitas pelos ACS (arquivo CSV)';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("Arquivo não encontrado: {$filePath}");
            return 1;
        }

        $this->info("Iniciando importação de validações do ACS...");

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
        $batchSize = 500;
        $batch = [];

        $this->output->progressStart();

        while (($row = fgetcsv($handle, 0, ',', '"', "\\")) !== false) {
            if (count($header) !== count($row)) {
                continue;
            }

            $data = array_combine($header, $row);

            $cpf = trim($data['cpf'] ?? '');
            $cpfCorrigido = trim($data['cpf_corrigido'] ?? '');
            
            $finalCpf = ($cpfCorrigido !== '' && strtolower($cpfCorrigido) !== 'null') ? $cpfCorrigido : $cpf;

            if ($finalCpf === '' || strtolower($finalCpf) === 'null') {
                $skipped++;
                continue;
            }

            $cleanCpf = preg_replace('/\D+/', '', $finalCpf);
            if (strlen($cleanCpf) !== 11) {
                $skipped++;
                continue;
            }

            $cpfHash = hash('sha256', $cleanCpf);

            $isResident = strtolower(trim($data['status'] ?? '')) === 'aprovado';
            
            $validatedAt = trim($data['validado_em'] ?? '');
            $validatedAtDate = ($validatedAt !== '' && strtolower($validatedAt) !== 'null') ? $validatedAt : null;

            $cns = trim($data['cns'] ?? '');
            $cleanCns = ($cns !== '' && strtolower($cns) !== 'null') ? preg_replace('/\D+/', '', $cns) : null;

            $addressParts = array_filter([
                $data['rua_corrigida'] !== 'NULL' && $data['rua_corrigida'] !== '' ? $data['rua_corrigida'] : null,
                $data['numero_corrigido'] !== 'NULL' && $data['numero_corrigido'] !== '' ? $data['numero_corrigido'] : null,
                $data['bairro_corrigido'] !== 'NULL' && $data['bairro_corrigido'] !== '' ? $data['bairro_corrigido'] : null,
                $data['cep_corrigido'] !== 'NULL' && $data['cep_corrigido'] !== '' ? $data['cep_corrigido'] : null,
            ]);

            $batch[] = [
                'cpf_hash' => $cpfHash,
                'cpf' => $cleanCpf,
                'is_resident_assai' => $isResident,
                'residence_validated_at' => $validatedAtDate,
                'cns' => $cleanCns,
                'address' => !empty($addressParts) ? implode(', ', $addressParts) : null,
                'updated_at' => now(),
            ];

            if (count($batch) >= $batchSize) {
                $this->processBatch($batch);
                $count += count($batch);
                $batch = [];
                $this->output->progressAdvance($batchSize);
            }
        }

        if (count($batch) > 0) {
            $this->processBatch($batch);
            $count += count($batch);
            $this->output->progressAdvance(count($batch));
        }

        fclose($handle);
        $this->output->progressFinish();

        $this->info("Importação de validações concluída. Validations aplicadas: {$count} | Ignoradas (Sem CPF ou Inválido): {$skipped}");

        return 0;
    }

    private function processBatch(array $batch): void
    {
        DB::transaction(function () use ($batch) {
            foreach ($batch as $data) {
                $cpfHash = $data['cpf_hash'];
                unset($data['cpf_hash']);

                $citizen = Citizen::where('cpf_hash', $cpfHash)->first();

                if ($citizen) {
                    $updateData = [
                        'is_resident_assai' => $data['is_resident_assai'],
                        'residence_validated_at' => $data['residence_validated_at'],
                        'updated_at' => $data['updated_at'],
                    ];
                    
                    if (!empty($data['cns'])) {
                        $updateData['cns'] = $data['cns'];
                    }

                    if (!empty($data['address'])) {
                        $updateData['address'] = $data['address'];
                    }

                    $citizen->update($updateData);
                } else {
                    $insertData = [
                        'cpf_hash' => $cpfHash,
                        'cpf' => $data['cpf'],
                        'full_name' => 'NOME NAO INFORMADO',
                        'birth_date' => '1900-01-01',
                        'is_resident_assai' => $data['is_resident_assai'],
                        'residence_validated_at' => $data['residence_validated_at'],
                        'cns' => $data['cns'],
                        'address' => $data['address'],
                    ];
                    Citizen::create($insertData);
                }
            }
        });
    }
}
