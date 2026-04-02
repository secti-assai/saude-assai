<?php
use Illuminate\Support\Facades\Auth;
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Auth::attempt(['email'=>'admin.teste@saudeassai.local', 'password'=>'password']);
echo Auth::check() ? "L1 " : "F1 ";
Auth::guard('web')->logout();
Auth::attempt(['email'=>'admin.teste@saudeassai.local', 'password'=>'password']);
echo Auth::check() ? "L2 " : "F2 ";
