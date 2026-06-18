<?php
use App\Models\Citizen;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$citizens = Citizen::all();
foreach ($citizens as $c) {
    if (strpos(strtoupper($c->full_name), 'ALEXSANDER') !== false || strpos(strtoupper($c->full_name), 'KAKUBO') !== false) {
        echo "Found by name: ID " . $c->id . " Name: " . $c->full_name . " CPF: " . $c->cpf . "\n";
    }
    
    if (strpos((string)$c->cpf, '900') !== false || strpos((string)$c->cpf, '90012640930') !== false) {
        if ($c->cpf === '90012640930' || $c->cpf === '900.126.409-30') {
            echo "Found by exact CPF: ID " . $c->id . " Name: " . $c->full_name . " CPF: " . $c->cpf . "\n";
        }
    }
}
echo "Done.\n";
