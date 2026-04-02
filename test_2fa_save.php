<?php
use Illuminate\Support\Facades\Auth;
use App\Models\User;
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = User::where('email', 'admin.teste@saudeassai.local')->first();
$oldHash = $user->password;

$user->forceFill([
    'two_factor_enabled' => true,
])->save();

$user->refresh();
$newHash = $user->password;

echo "Old: $oldHash\n";
echo "New: $newHash\n";
echo "Match? " . ($oldHash === $newHash ? "YES" : "NO") . "\n";
