<?php
use Illuminate\Support\Facades\Auth;
use App\Models\User;
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = User::where('email', 'admin.teste@saudeassai.local')->first();
$oldHash = $user->password;

Auth::attempt(['email' => 'admin.teste@saudeassai.local', 'password' => 'password'], true);

$user->refresh();
$newHash = $user->password;

echo "Old: $oldHash\n";
echo "New: $newHash\n";
echo "Match? " . ($oldHash === $newHash ? "YES" : "NO") . "\n";
