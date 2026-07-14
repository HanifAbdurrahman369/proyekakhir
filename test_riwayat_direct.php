<?php
require 'services/farming_service/vendor/autoload.php';
$app = require_once 'services/farming_service/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/api/riwayat-panen', 'GET');
$request->attributes->set('auth', (object) ['sub' => 5, 'role_id' => 5]);

$controller = new \App\Http\Controllers\RiwayatPanenController();
$response = $controller->index($request);
echo json_encode($response->getData(true));
