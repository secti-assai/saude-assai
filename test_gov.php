<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$govAssai = $app->make(\App\Services\GovAssaiService::class);
$citizen = \App\Models\Citizen::find(377);

$start = microtime(true);
$res = $govAssai->fetchCitizenByCpf($citizen->cpf);
$end = microtime(true);

echo "Time taken: " . ($end - $start) . " seconds\n";
echo "Response:\n";
print_r($res);
