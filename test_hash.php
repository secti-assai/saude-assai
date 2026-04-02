<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$pw = '$2y$12$PZhILXEObAEKaG6LZPl1W.IIOLHcn21owmKPIFb4hJIE.vishP0GC';
echo Hash::isHashed($pw) ? 'YES' : 'NO';
