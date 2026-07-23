<?php

namespace App\Console\Commands;

use App\Models\Citizen;
use App\Services\BethaIntegrationService;
use App\Services\CitizenEligibilityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SanitizeBethaClients extends Command
{
    protected $signature = 'betha:sanitize-clients {--limit=1000 : Registros por página na API} {--max=0 : Máximo total a processar}';
    protected $description = 'Varre todos os clientes ativos na Betha e inativa os que não atendem às regras (N2/ACS/Menor)';

    public function handle(CitizenEligibilityService $eligibilityService, BethaIntegrationService $bethaService)
    {
        $token = config('services.betha.bearer_token');
        $userAccess = config('services.betha.user_access');
        $baseUrl = 'https://saude.suite.betha.cloud';
        $limit = (int) $this->option('limit');
        $max = (int) $this->option('max');

        if (!$token || !$userAccess) {
            $this->error('Credenciais da Betha não configuradas no .env');
            return 1;
        }

        $this->info("Iniciando varredura na API da Betha (Paginando de {$limit} em {$limit})");

        $offset = 0;
        $totalProcessed = 0;
        $totalInactivated = 0;
        $totalSkipped = 0;

        while (true) {
            $url = "{$baseUrl}/dados/v1/clientes?limit={$limit}&offset={$offset}";
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'User-Access' => $userAccess,
                'Accept' => 'application/json',
            ])->get($url);

            if (!$response->successful()) {
                $this->error("Erro ao buscar clientes: " . $response->status());
                break;
            }

            $data = $response->json();
            $clients = $data['content'] ?? [];
            $count = count($clients);

            if ($count === 0) {
                break;
            }

            foreach ($clients as $clientData) {
                $totalProcessed++;
                $cpf = preg_replace('/\D/', '', (string) ($clientData['cpf'] ?? ''));
                $cns = preg_replace('/\D/', '', (string) ($clientData['cns'] ?? ''));
                $nome = $clientData['nome'] ?? $clientData['nomeCompleto'] ?? '';

                // Se já está inativo na Betha, ignora
                if (($clientData['inativo'] ?? false) === true) {
                    $totalSkipped++;
                    continue;
                }

                if (empty($cpf)) {
                    // Cidadão só tem CNS ou não tem documento. (Os sem documento deveriam ter sido removidos pela Betha)
                    if (empty($cns)) {
                        $this->warn("Ignorado ID {$clientData['id']}: Sem CPF e sem CNS");
                        $totalSkipped++;
                        continue;
                    }

                    // Se tem apenas CNS, não temos como validar no Gov.Assai (que exige CPF).
                    // Portanto, como não conseguimos validar se ele é N2, inativamos por precaução.
                    $this->info("Inativando ID {$clientData['id']} ({$nome}): Não possui CPF para validação no Gov.Assai");
                    $bethaService->inactivateClient(null, $cns, $nome);
                    $totalInactivated++;
                    continue;
                }

                // Cidadão tem CPF. Vamos validar as regras locais
                $result = $eligibilityService->validateAndSync($cpf);

                $isMinor = false;
                // Tentamos pegar a data de nascimento do resultado da integração (já criada/atualizada no banco)
                $localCitizen = Citizen::where('cpf_hash', hash('sha256', $cpf))->first();
                if ($localCitizen && $localCitizen->birth_date) {
                    $isMinor = \Carbon\Carbon::parse($localCitizen->birth_date)->age < 18;
                }

                if ($result['eligible'] || $isMinor) {
                    $this->line("Mantido ID {$clientData['id']} ({$cpf}): " . ($isMinor ? "Menor de idade" : "Elegível (N2 ou ACS)"));
                    // Opcional: já envia o payload mais atualizado para a Betha
                    // $bethaService->syncClient($localCitizen, false);
                } else {
                    $this->info("Inativando ID {$clientData['id']} ({$cpf}): Maior de idade e N1/Não validado");
                    $bethaService->inactivateClient($cpf, $cns, $nome);
                    $totalInactivated++;
                }

                if ($max > 0 && $totalProcessed >= $max) {
                    break 2;
                }
            }

            $this->info("-> Página lida (offset {$offset}): +{$count} registros. Processados: {$totalProcessed}, Inativados: {$totalInactivated}");

            if (empty($data['hasNext'])) {
                break;
            }

            $offset += $limit;
        }

        $this->info("\n--- RESUMO DA VARREDURA ---");
        $this->info("Total de cadastros processados: {$totalProcessed}");
        $this->info("Total inativados por não cumprir regras: {$totalInactivated}");
        $this->info("Total ignorados (já inativos ou sem doc): {$totalSkipped}");

        return 0;
    }
}
