<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PharmacyExternalImportRow;
use App\Models\Citizen;
use App\Models\CentralPharmacyRequest;

function pickPreferredCitizen($citizens) {
    if (count($citizens) === 0) return null;
    $bestScore = -1;
    $bestCitizen = null;
    foreach ($citizens as $citizen) {
        $score = 0;
        if ($citizen->cpf && !str_starts_with((string)$citizen->cpf, 'SYNC-')) { $score += 100; }
        if ($citizen->is_resident_assai) { $score += 50; }
        if (!$citizen->pharmacy_lock_flag) { $score += 10; }
        if ($citizen->birth_date && $citizen->birth_date !== '1900-01-01') { $score += 5; }
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestCitizen = $citizen;
        }
    }
    return $bestCitizen ?? $citizens[0];
}

$allCitizens = Citizen::all();

$rows = PharmacyExternalImportRow::with(['citizen'])->get();
$fixedCitizens = 0;
$fixedBypasses = 0;

foreach ($rows as $row) {
    $norm = $row->customer_name_normalized;
    if (!$norm) continue;

    // Find all citizens that have a similar normalized name
    $candidates = [];
    foreach ($allCitizens as $c) {
        // Just simple match for now, or use the normalizer function
        $name = str_replace([' ', '.', '-'], '', strtoupper(trim($c->full_name)));
        $normRow = str_replace([' ', '.', '-'], '', strtoupper(trim($norm)));
        if ($name === $normRow || str_replace([' ', '.', '-'], '', strtoupper(trim($row->customer_name_raw))) === $name) {
            $candidates[] = $c;
        }
    }
    
    if (count($candidates) > 1) {
        $best = pickPreferredCitizen($candidates);
        if ($best && $best->id !== $row->citizen_id) {
            $row->citizen_id = $best->id;
            $row->save();
            $fixedCitizens++;
            
            if ($row->centralPharmacyRequest) {
                $row->centralPharmacyRequest->citizen_id = $best->id;
                $row->centralPharmacyRequest->save();
            }
        }
    }

    $citizen = Citizen::find($row->citizen_id);
    if (!$citizen) continue;

    $bestLevel = CentralPharmacyRequest::where('citizen_id', $citizen->id)
        ->whereNotNull('gov_assai_level')
        ->max('gov_assai_level');

    $resolvedLevel = ($bestLevel !== null && (int)$bestLevel >= 2) ? $bestLevel : '0';
    $shouldBeBypass = ((int)$resolvedLevel < 2 && !$citizen->is_resident_assai);
    
    if ($row->bypass_detected && !$shouldBeBypass) {
        $row->bypass_detected = false;
        $row->save();
        $fixedBypasses++;
    }
}

echo "Fixed citizens: $fixedCitizens, Fixed bypasses: $fixedBypasses\n";
