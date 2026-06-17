<?php
require 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJhdXRoLXNlcnZpY2UiLCJzdWIiOjMsImVtYWlsIjoicGV0dWdhc0BnbWFpbC5jb20iLCJyb2xlX2lkIjoyLCJpYXQiOjE3ODE0MDkyMTMsImV4cCI6MTc4MTQ5NTYxM30.GJuA44hkXEiTgvAuHpi-4KHgZHQy2Yf3UlhT5xIe_2w';

$secrets = [
    'your-super-secret-key-123456789abcdefghijklmnopqrstuvwxyz',
    'secret-key-sementara-untuk-lokal',
    'secret'
];

foreach ($secrets as $secret) {
    try {
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));
        echo "Valid secret: '$secret'\n";
        print_r($decoded);
        break;
    } catch (\Exception $e) {
        echo "Failed with secret '$secret': " . $e->getMessage() . "\n";
    }
}
