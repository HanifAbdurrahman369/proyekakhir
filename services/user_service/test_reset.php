<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::first();
if (!$user) {
    echo "No user found in DB.\n";
    exit;
}

echo "Testing for user: " . $user->email . "\n";

// Generate token
$token = Password::broker()->createToken($user);
echo "Plain Token generated: " . $token . "\n";

// Get token from DB
$dbToken = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');
echo "Hashed Token in DB: " . $dbToken . "\n";

// Verify token using PasswordBroker
$exists = Password::broker()->tokenExists($user, $token);
echo "Password::tokenExists reports: " . ($exists ? "VALID" : "INVALID") . "\n";
