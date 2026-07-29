<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$baseUrl = 'https://saude.suite.betha.cloud';
$token = config('services.betha.bearer_token');
$userAccess = config('services.betha.user_access');
$headers = ['Authorization' => 'Bearer ' . $token, 'User-Access' => $userAccess, 'Accept' => 'application/json'];

$resp = Illuminate\Support\Facades\Http::withHeaders($headers)->get("{$baseUrl}/dados/v1/unidade");
echo 'Unidades: ' . substr($resp->body(), 0, 500) . PHP_EOL;

$idUnidade = 5797;
echo "Testando listSaldo com idUnidade {$idUnidade} e dataSaldo hoje" . PHP_EOL;
$saldoResp = Illuminate\Support\Facades\Http::withHeaders($headers)
    ->get("{$baseUrl}/dados/v1/produtoSaldo/listSaldo", [
        'idsUnidade' => $idUnidade, 
        'dataSaldo' => date('Y-m-d'),
        'isSaldoPositivo' => 'true'
    ]);
echo 'Status: ' . $saldoResp->status() . PHP_EOL;
echo 'Saldo: ' . substr($saldoResp->body(), 0, 500) . PHP_EOL;

echo "Testando listSaldo com idsCentroDeCusto" . PHP_EOL;
$saldoResp2 = Illuminate\Support\Facades\Http::withHeaders($headers)
    ->get("{$baseUrl}/dados/v1/produtoSaldo/listSaldo", [
        'idsCentroDeCusto' => 5797, 
        'isSaldoPositivo' => 'true'
    ]);
echo 'Status 2: ' . $saldoResp2->status() . PHP_EOL;
echo 'Saldo 2: ' . substr($saldoResp2->body(), 0, 500) . PHP_EOL;

