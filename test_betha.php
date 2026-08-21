<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(\App\Services\BethaIntegrationService::class);
$baseUrl = 'https://saude.suite.betha.cloud';
$headers = [
    'Authorization' => 'Bearer ' . config('services.betha.bearer_token'),
    'User-Access' => config('services.betha.user_access'),
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
];

echo "1. Buscando CPF...\n";
$resp = \Illuminate\Support\Facades\Http::withHeaders($headers)->get("$baseUrl/dados/v1/clientes/buscarPorCpf/49025313566");
echo "Status: " . $resp->status() . "\n";
echo "Body:\n" . json_encode($resp->json(), JSON_PRETTY_PRINT) . "\n\n";

$citizen = [
    'name' => 'Teste Integracao Gov Assai',
    'cpf' => '49025313566',
    'cns' => '718009519680001',
    'birth_date' => '1990-01-01',
    'sexo' => 'M',
    'raca_cor' => 'BRANCA',
    'nacionalidade_sigla' => 'BR',
    'naturalidade' => 'Assaí',
    'naturalidade_uf' => 'PR',
    'address' => [
        'cep' => '86220000',
        'numero' => 'S/N',
        'logradouro' => 'Rua Teste',
        'bairro' => 'Centro',
    ]
];

echo "2. Executando syncClient...\n";
$result = $service->syncClient($citizen, false);
echo "Resultado syncClient: " . var_export($result, true) . "\n";
