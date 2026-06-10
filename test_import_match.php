<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rows = \App\Models\PharmacyExternalImportRow::with('citizen')->where('bypass_detected', true)->latest()->take(10)->get();

foreach($rows as $row) {
    echo "Imported Name: " . $row->customer_name_raw . " (Norm: " . $row->customer_name_normalized . ")\n";
    $firstWord = explode(' ', trim($row->customer_name_raw))[0];
    
    // Look for exact matches by name (just different spacing/accents)
    $similar = \App\Models\Citizen::where('full_name', 'LIKE', '%' . $firstWord . '%')
        ->where('cpf', 'NOT LIKE', 'SYNC-%')
        ->get();
        
    $found = false;
    foreach($similar as $sim) {
        similar_text($row->customer_name_normalized, \Illuminate\Support\Str::upper(\Illuminate\Support\Str::ascii($sim->full_name)), $score);
        if ($score > 85) {
            echo "   -> CANDIDATE FOUND: " . $sim->full_name . " | CPF: " . $sim->cpf . " | Level: " . $sim->gov_assai_level . " | Score: " . $score . "\n";
            $found = true;
        }
    }
    if (!$found) {
        echo "   -> No obvious candidate found.\n";
    }
    echo "---------------------------\n";
}
