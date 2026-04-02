<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$req = Illuminate\Http\Request::create('/login', 'POST', ['email' => 'admin.teste@saudeassai.local', 'password' => 'password']);
$res = $kernel->handle($req);
echo "Status 1: " . $res->getStatusCode() . "\n";
echo "Content: " . $res->getContent() . "\n";

$req2 = Illuminate\Http\Request::create('/login', 'POST', ['email' => 'admin.teste@saudeassai.local', 'password' => 'password']);
$res2 = $kernel->handle($req2);
echo "Status 2: " . $res2->getStatusCode() . "\n";
