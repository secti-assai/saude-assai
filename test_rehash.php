<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$p = App\Models\User::first()->password;
echo Hash::needsRehash($p) ? 'YES' : 'NO';
