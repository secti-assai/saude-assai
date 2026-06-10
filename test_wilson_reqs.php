<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$requests = \App\Models\CentralPharmacyRequest::where('citizen_id', 377)->get();
echo "Total requests for Wilson: " . $requests->count() . "\n";
foreach($requests as $req) {
    echo "ID: " . $req->id . " | Status: " . $req->status . " | Date: " . $req->created_at . " | GovLevel: " . $req->gov_assai_level . " | Med: " . $req->medication_name . " | Notes: " . $req->notes . "\n";
}
