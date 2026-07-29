<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$clientId = config('services.betha.client_id');
$clientSecret = config('services.betha.client_secret');

if (!$clientId || !$clientSecret) {
    die("Client ID or Secret not found in .env\n");
}

echo "Generating token for Client ID: {$clientId}\n";

$response = Illuminate\Support\Facades\Http::asForm()
    ->withBasicAuth($clientId, $clientSecret)
    ->post('https://accounts.betha.cloud/oauth2/token', [
        'grant_type' => 'client_credentials'
    ]);

if ($response->successful()) {
    $data = $response->json();
    echo "New Token: " . $data['access_token'] . "\n";
    echo "Expires in: " . $data['expires_in'] . " seconds\n";
    
    // Test the new token
    $headers = [
        'Authorization' => 'Bearer ' . $data['access_token'], 
        'User-Access' => config('services.betha.user_access'), 
        'Accept' => 'application/json'
    ];
    $baseUrl = 'https://saude.suite.betha.cloud';
    
    echo "\nTesting /dados/v1/produto com novo token...\n";
    $prodResp = Illuminate\Support\Facades\Http::withHeaders($headers)->get("{$baseUrl}/dados/v1/produto", ['limit' => 1]);
    echo "Status: " . $prodResp->status() . "\n";
    
    echo "\nTesting /dados/v1/produtoSaldo/listSaldo com novo token...\n";
    $saldoResp = Illuminate\Support\Facades\Http::withHeaders($headers)->get("{$baseUrl}/dados/v1/produtoSaldo/listSaldo", ['limit' => 10]);
    echo "Status: " . $saldoResp->status() . "\n";
    echo "Body: " . $saldoResp->body() . "\n";
    
} else {
    echo "Failed to generate token:\n";
    echo $response->body() . "\n";
}
