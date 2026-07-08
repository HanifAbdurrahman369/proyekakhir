<?php
require 'services/auth_service/vendor/autoload.php';
use Firebase\JWT\JWT;

$gateway = 'http://127.0.0.1:8003/api';
$jwtSecret = 'gjXFRiXtIvluw2uRfhTnQHWf1xfRdNzORmfsnmQo1leNtuDIg09PpA9E2CzLbhlD';

function testEndpoint($method, $url, $payload = null, $token = null) {
    echo "----------------------------------------\n";
    echo "TESTING: $method $url\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); 

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json'
    ];

    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($payload) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "RESULT: FAILED (CURL ERROR: $error)\n";
        return null;
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        echo "RESULT: SUCCESS (HTTP $httpCode)\n";
        $data = json_decode($response, true);
        echo "RESPONSE KEYS: " . implode(', ', array_keys($data ?? [])) . "\n";
        return $data;
    } else {
        echo "RESULT: FAILED (HTTP $httpCode)\n";
        echo "RESPONSE: " . substr($response, 0, 200) . "...\n";
        return null;
    }
}

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=pa2', 'root', '123');
    $stmt = $pdo->query("SELECT * FROM users WHERE role_id = 4 LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Found User in DB: {$user['email']} (Role ID: {$user['role_id']})\n";
        
        $payload = [
            'iss' => 'auth-service',
            'sub' => $user['id'],
            'email' => $user['email'],
            'role_id' => (int) $user['role_id'],
            'iat' => time(),
            'exp' => time() + 3600
        ];
        
        $token = JWT::encode($payload, $jwtSecret, 'HS256');
        echo "Generated mock JWT token.\n\n";
        
        echo "--- PUBLIC ENDPOINTS ---\n";
        testEndpoint('GET', "$gateway/map-lahan");
        
        echo "\n--- AUTHENTICATED ENDPOINTS ---\n";
        testEndpoint('GET', "$gateway/users", null, $token);
        testEndpoint('GET', "$gateway/lahan", null, $token);
        testEndpoint('GET', "$gateway/produksi-kelurahan", null, $token);
        testEndpoint('GET', "$gateway/produksi-kecamatan", null, $token);
    } else {
        echo "No users found in database.\n";
    }
} catch (Exception $e) {
    echo "Error connecting to DB: " . $e->getMessage() . "\n";
}
