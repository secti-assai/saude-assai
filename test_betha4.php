<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(App\Services\BethaIntegrationService::class);
$headers = (new ReflectionClass($service))->getMethod('getHeaders')->invoke($service);

$cns = '704002866127665';
$cpf = '10799316946';
$id = 4765984;

$payload = [
    'nomeCompleto' => 'MARCELLA DIAS CARVALHO',
    'cpf' => $cpf,
    'cns' => $cns,
    'dataNascimento' => '1999-07-21',
    'sexo' => 'FEMININO', // Changed from MASCULINO to FEMININO based on the payload earlier
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

echo "Testing PUT /dados/v1/clientes/{id}...\n";
$resp = Illuminate\Support\Facades\Http::withHeaders($headers)
    ->put("https://saude.suite.betha.cloud/dados/v1/clientes/{$id}", $payload);
echo "Status: " . $resp->status() . "\n";
echo "Body: " . substr($resp->body(), 0, 500) . "\n\n";

if ($resp->status() !== 200 && $resp->status() !== 204) {
    echo "Testing POST /dados/v1/clientes/integrar with ID in payload...\n";
    $payloadWithId = array_merge(['id' => $id], $payload);
    $resp2 = Illuminate\Support\Facades\Http::withHeaders($headers)
        ->post("https://saude.suite.betha.cloud/dados/v1/clientes/integrar", $payloadWithId);
    echo "Status: " . $resp2->status() . "\n";
    echo "Body: " . substr($resp2->body(), 0, 500) . "\n\n";
    
    // Also try PATCH just in case
    echo "Testing PATCH /dados/v1/clientes/{id} with just CPF...\n";
    $resp3 = Illuminate\Support\Facades\Http::withHeaders($headers)
        ->patch("https://saude.suite.betha.cloud/dados/v1/clientes/{$id}", ['cpf' => $cpf]);
    echo "Status: " . $resp3->status() . "\n";
    echo "Body: " . substr($resp3->body(), 0, 500) . "\n\n";
}
