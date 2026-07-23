<?php

namespace App\Console\Commands;

use App\Services\BethaIntegrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExecuteCsvInactivation extends Command
{
    protected $signature = 'betha:inactivate-from-csv {--file=lista_para_inativar.csv : Caminho do arquivo CSV}';
    protected $description = 'Lê um arquivo CSV e inativa todos os cidadãos listados na API da Betha';

    public function handle(BethaIntegrationService $bethaService)
    {
        $file = $this->option('file');

        if (!file_exists($file)) {
            $this->error("Arquivo {$file} não encontrado.");
            return 1;
        }

        $this->info("Iniciando inativação em lote lendo o arquivo: {$file}");

        $handle = fopen($file, 'r');
        $header = fgetcsv($handle, 0, ';');

        $total = 0;
        $sucesso = 0;
        $falhas = 0;

        while (($data = fgetcsv($handle, 0, ';')) !== false) {
            $total++;
            $id = $data[0] ?? '';
            $nome = $data[1] ?? '';
            $cpf = preg_replace('/\D/', '', $data[2] ?? '');
            $cns = preg_replace('/\D/', '', $data[3] ?? '');

            $this->line("Processando {$total} | ID: {$id} | Nome: {$nome}");

            if (empty($cpf) && empty($cns)) {
                $this->warn("  -> Ignorado: Cidadão não possui CPF nem CNS.");
                $falhas++;
                continue;
            }

            try {
                $result = $bethaService->inactivateClient($cpf, $cns, $nome, $id);
                if ($result) {
                    $this->info("  -> [SUCESSO] Inativado na Betha.");
                    $sucesso++;
                } else {
                    $this->error("  -> [ERRO] Falha ao inativar na API da Betha.");
                    $falhas++;
                }
            } catch (\Exception $e) {
                $this->error("  -> [EXCEPTION] Erro fatal: " . $e->getMessage());
                Log::error("Erro na inativação CSV", ['cpf' => $cpf, 'exception' => $e]);
                $falhas++;
            }
        }

        fclose($handle);

        $this->info("\n--- RESUMO DA INATIVAÇÃO EM LOTE ---");
        $this->info("Total processados: {$total}");
        $this->info("Total inativados com sucesso: {$sucesso}");
        $this->info("Total de falhas: {$falhas}");

        return 0;
    }
}
