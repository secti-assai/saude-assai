<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(App\Services\BethaIntegrationService::class);
$headers = (new ReflectionClass($service))->getMethod('getHeaders')->invoke($service);

$id = 4765984;

echo "Testing PATCH /dados/v1/clientes/inativar/{$id}...\n";
$resp = Illuminate\Support\Facades\Http::withHeaders($headers)
    ->patch("https://saude.suite.betha.cloud/dados/v1/clientes/inativar/{$id}");
echo "Status: " . $resp->status() . "\n";
echo "Body: " . substr($resp->body(), 0, 500) . "\n\n";

echo "Testing DELETE /dados/v1/clientes/{$id}...\n";
$resp2 = Illuminate\Support\Facades\Http::withHeaders($headers)
    ->delete("https://saude.suite.betha.cloud/dados/v1/clientes/{$id}");
echo "Status: " . $resp2->status() . "\n";
echo "Body: " . substr($resp2->body(), 0, 500) . "\n\n";
