<?php
use Illuminate\Support\Facades\Auth;
use App\Models\User;
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = User::where('email', 'admin.teste@saudeassai.local')->first();
echo "Before login: " . ($user ? 'FOUND ' : 'NOT FOUND ') . "\n";
Auth::attempt(['email'=>'admin.teste@saudeassai.local', 'password'=>'password']);
$user = User::where('email', 'admin.teste@saudeassai.local')->first();
echo "After login: " . ($user ? 'FOUND ' : 'NOT FOUND ') . "\n";

