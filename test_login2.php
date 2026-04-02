<?php

use Illuminate\Support\Facades\Auth;
use App\Models\User;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = User::where('email', 'admin.teste@saudeassai.local')->first();
echo "Before login password (hash): " . $user->password . "\n";
Auth::attempt(['email' => 'admin.teste@saudeassai.local', 'password' => 'password']);
$user->refresh();
echo "After login password (hash): " . $user->password . "\n";
