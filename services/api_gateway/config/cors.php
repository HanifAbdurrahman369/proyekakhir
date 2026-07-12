<?php

return [
    'paths' => ['api/*', 'login', 'register', 'forgot-password', 'reset-password', 'sanctum/csrf-cookie', '*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['https://sigpala.my.id', 'https://www.sigpala.my.id', 'https://agrilytics-batola.poliban.ac.id', 'http://127.0.0.1:8000'],
    'allowed_origins_patterns' => [
        '/^http:\/\/127\.0\.0\.1:\d+$/',
        '/^http:\/\/localhost:\d+$/',
        '/^http:\/\/192\.168\.\d+\.\d+:\d+$/',
        '/^http:\/\/10\.\d+\.\d+\.\d+:\d+$/',
        '/^http:\/\/172\.(1[6-9]|2\d|3[0-1])\.\d+\.\d+:\d+$/',
    ],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
