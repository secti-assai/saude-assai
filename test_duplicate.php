<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = \App\Models\Citizen::where('full_name', 'LIKE', '%WILSON FERREIRA DE AGUIAR%')->get(['id', 'full_name', 'cpf']);
foreach($c as $x) {
    echo $x->id . ' - ' . $x->full_name . ' - ' . $x->cpf . PHP_EOL;
}
