<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(App\Services\BethaIntegrationService::class);
$headers = (new ReflectionClass($service))->getMethod('getHeaders')->invoke($service);

$cns = '704002866127665';

echo "Testing buscarPorCns...\n";
$resp = Illuminate\Support\Facades\Http::withHeaders($headers)
    ->get("https://saude.suite.betha.cloud/dados/v1/clientes/buscarPorCns/{$cns}");

echo "Status: " . $resp->status() . "\n";
echo "Body: " . $resp->body() . "\n\n";

echo "Testing buscarPorCpf with dummy CPF...\n";
$resp2 = Illuminate\Support\Facades\Http::withHeaders($headers)
    ->get("https://saude.suite.betha.cloud/dados/v1/clientes/buscarPorCpf/00000000000");

echo "Status: " . $resp2->status() . "\n";
echo "Body: " . $resp2->body() . "\n\n";

// Also test the response of the /integrar when it fails with 422 for this CNS
$payload = [
    'nomeCompleto' => 'MARCELLA DIAS CARVALHO',
    'cpf' => '10799316946',
    'cns' => $cns,
    'dataNascimento' => '1999-07-21',
    'sexo' => 'FEMININO',
    'raca' => 'BRANCA',
    'paisNacionalidade' => ['iso2' => 'BR'],
    'municipioNaturalidade' => ['codigoIBGE' => 4101903],
    'endereco' => [
        'cep' => '86220000',
        'municipio' => ['codigoIBGE' => 4101903],
        'bairro' => ['municipio' => ['codigoIBGE' => 4101903], 'nome' => 'Centro'],
        'logradouro' => ['municipio' => ['codigoIBGE' => 4101903], 'cep' => '86220000', 'abreviaturaTipoLogradouro' => 'R', 'nome' => 'Sem Logradouro'],
        'semNumero' => true
    ]
];

echo "Testing integrar...\n";
$resp3 = Illuminate\Support\Facades\Http::withHeaders($headers)
    ->post("https://saude.suite.betha.cloud/dados/v1/clientes/integrar", $payload);

echo "Status: " . $resp3->status() . "\n";
echo "Body: " . $resp3->body() . "\n";
