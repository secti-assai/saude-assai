<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$bypasses = \App\Models\PharmacyExternalImportRow::with('citizen')->where('bypass_detected', true)->latest()->take(50)->get();
$realLocked = 0;
$syntheticLocked = 0;

foreach($bypasses as $row) {
    if (str_starts_with($row->citizen->cpf, 'SYNC-')) {
        $syntheticLocked++;
    } else {
        $realLocked++;
        echo "Real citizen locked: " . $row->citizen->full_name . " (CPF: " . $row->citizen->cpf . ")\n";
    }
}

echo "Total real locked: " . $realLocked . "\n";
echo "Total synthetic locked: " . $syntheticLocked . "\n";
