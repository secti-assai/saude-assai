<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Illuminate\Support\Facades\DB::statement("UPDATE pharmacy_external_import_rows SET bypass_detected = NOT (c.is_resident_assai = true OR EXISTS (SELECT 1 FROM central_pharmacy_requests req WHERE req.citizen_id = pharmacy_external_import_rows.citizen_id AND req.gov_assai_level IN ('2', '3', '4', '5'))) FROM citizens c WHERE pharmacy_external_import_rows.citizen_id = c.id");
echo "Done\n";
