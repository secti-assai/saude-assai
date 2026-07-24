<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Bus;
use App\Services\CitizenEligibilityService;
use App\Services\GovAssaiService;
use App\Jobs\SyncCitizenToBethaJob;
use App\Models\Citizen;

Bus::fake();

// Mock GovAssaiService
$mockGovAssai = Mockery::mock(GovAssaiService::class)->makePartial();
$mockGovAssai->shouldReceive('normalizeCpf')->andReturn('49025313566');
$mockGovAssai->shouldReceive('isValidCpfFormat')->andReturn(true);
$mockGovAssai->shouldReceive('fetchCitizenByCpf')->andReturn([
    'success' => true,
    'status' => 200,
    'origem' => 'gov_assai',
    'data' => [
        'gov_assai' => ['nivel' => '2'], // Nivel 2 = Eligible
        'cidadao' => [
            'nome' => 'Teste Elegibilidade e Sync Job',
            'data_nascimento' => '1990-01-01',
            'sexo' => 'M',
            'nacionalidade_sigla' => 'BR',
            'naturalidade' => 'Londrina',
            'naturalidade_uf' => 'PR',
        ],
        'endereco' => [
            'cep' => '86200000',
            'logradouro' => 'Rua Ficticia',
            'numero' => '123',
            'bairro' => 'Centro'
        ],
        'contato' => ['celular' => '43999999999', 'email' => 'teste@assai.pr.gov.br']
    ]
]);

$app->instance(GovAssaiService::class, $mockGovAssai);

$eligibilityService = $app->make(CitizenEligibilityService::class);

echo "Iniciando validateAndSync()...\n";
$result = $eligibilityService->validateAndSync('49025313566');

if ($result['eligible']) {
    echo "Sucesso: Cidadão avaliado como elegível!\n";
    echo "ID do cidadão criado/atualizado: " . $result['citizen']->id . "\n";
    
    // Check if the Job was dispatched
    Bus::assertDispatched(SyncCitizenToBethaJob::class, function ($job) use ($result) {
        return $job->citizenId === $result['citizen']->id;
    });
    
    echo "Sucesso: Job SyncCitizenToBethaJob foi disparado corretamente na fila!\n";
} else {
    echo "Falha: Cidadão não foi avaliado como elegível.\n";
    print_r($result);
}

// Clean up mock citizen
if (isset($result['citizen'])) {
    $result['citizen']->delete();
    echo "Registro de teste limpo do banco de dados.\n";
}

