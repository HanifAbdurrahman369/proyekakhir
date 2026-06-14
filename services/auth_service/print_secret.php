<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "JWT_SECRET: " . env('JWT_SECRET') . "\n";
echo "Config jwt.secret: " . config('jwt.secret') . "\n";
