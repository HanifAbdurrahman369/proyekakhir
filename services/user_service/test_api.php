<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = App\Models\User::where("role_id", 2)->first();
$token = $user->createToken("test")->plainTextToken;
echo "Token: " . $token;

