<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = \App\Models\PharmacyExternalImportRow::with('citizen')->where('bypass_detected', true)->latest()->take(10)->get();

foreach($rows as $row) {
    echo "Imported Name: " . $row->customer_name_raw . "\n";
    echo "Linked Citizen Name: " . $row->citizen->full_name . " | CPF: " . $row->citizen->cpf . "\n";
    echo "---------------------------\n";
}
