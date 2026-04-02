<?php
use Illuminate\Support\Facades\Auth;
use App\Models\User;
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Auth::attempt(['email'=>'admin.teste@saudeassai.local', 'password'=>'password']);
echo "L1 " . (User::where('email', 'admin.teste@saudeassai.local')->first()->password) . "\n";
Auth::guard('web')->logout();
echo "Logout " . (User::where('email', 'admin.teste@saudeassai.local')->first()->password) . "\n";
Auth::attempt(['email'=>'admin.teste@saudeassai.local', 'password'=>'password']);
echo "L2 " . (User::where('email', 'admin.teste@saudeassai.local')->first()->password) . "\n";
