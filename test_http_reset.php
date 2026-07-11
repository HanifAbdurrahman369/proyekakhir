<?php

// 1. Send forgot password request to API Gateway
$email = 'nrlhikmah554@gmail.com';
echo "Sending forgot password request for $email...\n";

$ch = curl_init('http://127.0.0.1:8003/api/forgot-password');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => $email]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
$resp = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Forgot Password Response ($status): $resp\n\n";

if ($status !== 200) {
    echo "Failed to initiate password reset.\n";
    exit;
}

// Wait a bit for log to write
sleep(2);

// 2. Read user_service laravel.log to get the plain token
$logPath = 'services/user_service/storage/logs/laravel.log';
if (!file_exists($logPath)) {
    echo "Log file not found at $logPath\n";
    exit;
}

$logContent = file_get_contents($logPath);
// Find the last reset-password URL in the log
preg_match_all('/reset-password\/([a-f0-9]{64})/', $logContent, $matches);
if (empty($matches[1])) {
    echo "No reset token found in log.\n";
    exit;
}

$plainToken = end($matches[1]);
echo "Extracted Plain Token from log: $plainToken\n\n";

// 3. Send reset password request to API Gateway
echo "Sending reset password request to API Gateway...\n";
$resetData = [
    'email' => $email,
    'token' => $plainToken,
    'password' => 'newpassword123',
    'password_confirmation' => 'newpassword123'
];

$ch = curl_init('http://127.0.0.1:8003/api/forget-password');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($resetData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
$resp = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Reset Password Response ($status): $resp\n";
