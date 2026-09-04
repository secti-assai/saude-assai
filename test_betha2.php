<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(App\Services\BethaIntegrationService::class);
$headers = (new ReflectionClass($service))->getMethod('getHeaders')->invoke($service);

$cns = '704002866127665';

echo "Testing GET /dados/v1/clientes?cns=...\n";
$resp = Illuminate\Support\Facades\Http::withHeaders($headers)
    ->get("https://saude.suite.betha.cloud/dados/v1/clientes", ['cns' => $cns]);
echo "Status: " . $resp->status() . "\n";
echo "Body: " . substr($resp->body(), 0, 500) . "\n\n";

echo "Testing GET /dados/v1/clientes?filtro=cns=...\n";
$resp2 = Illuminate\Support\Facades\Http::withHeaders($headers)
    ->get("https://saude.suite.betha.cloud/dados/v1/clientes", ['filtro' => 'cns=' . $cns]);
echo "Status: " . $resp2->status() . "\n";
echo "Body: " . substr($resp2->body(), 0, 500) . "\n\n";

echo "Testing GET /dados/v1/clientes/buscarPorCartaoSus/{$cns}...\n";
$resp3 = Illuminate\Support\Facades\Http::withHeaders($headers)
    ->get("https://saude.suite.betha.cloud/dados/v1/clientes/buscarPorCartaoSus/{$cns}");
echo "Status: " . $resp3->status() . "\n";
echo "Body: " . substr($resp3->body(), 0, 500) . "\n\n";
