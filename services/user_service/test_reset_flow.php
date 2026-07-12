<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'nrlhikmah554@gmail.com';
$user = \App\Models\User::where('email', $email)->first();
if (!$user) {
    echo "No user found with email $email\n";
    exit;
}

// 1. Generate token
$token = Password::broker()->createToken($user);
echo "Generated token: $token\n";

// 2. Validate token
$credentials = [
    'email' => $email,
    'token' => $token,
    'password' => 'newpassword123',
    'password_confirmation' => 'newpassword123'
];

$status = Password::broker()->reset($credentials, function ($user, $password) {
    $user->password = $password;
    $user->save();
});

echo "Status of reset: " . $status . "\n";
echo "Expected Password::PASSWORD_RESET: " . Password::PASSWORD_RESET . "\n";
