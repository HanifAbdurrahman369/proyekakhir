<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', '*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['https://sigpala.my.id', 'https://www.sigpala.my.id', 'https://agrilytics-batola.poliban.ac.id', 'http://agrilytics-batola.poliban.ac.id', 'http://127.0.0.1:8000', 'http://127.0.0.1:*', 'http://localhost:*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
