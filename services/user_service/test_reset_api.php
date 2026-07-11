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

echo "Testing API behavior for user: " . $user->email . "\n";

// 1. Generate token
$token = Password::broker()->createToken($user);
echo "Plain Token generated: " . $token . "\n";

// 2. Call the controller resetPassword directly (via a simulated request)
$request = Illuminate\Http\Request::create('/api/reset-password', 'POST', [
    'email' => $user->email,
    'token' => $token,
    'password' => 'newpassword123',
    'password_confirmation' => 'newpassword123'
]);

$controller = $app->make(\App\Http\Controllers\UserController::class);
try {
    $response = $controller->resetPassword($request);
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response content: " . $response->getContent() . "\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
