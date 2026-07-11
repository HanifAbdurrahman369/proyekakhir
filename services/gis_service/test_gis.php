<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$request = Illuminate\Http\Request::create('/api/spasial-lahan', 'GET', ['status' => 'ALL', 'kabupaten' => 'batola']);
$controller = new App\Http\Controllers\LahanSawahController();
$response = $controller->index($request);
echo json_encode($response->getData(true));
