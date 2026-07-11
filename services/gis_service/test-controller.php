<?php
$req = Illuminate\Http\Request::create('/api/spasial-lahan', 'GET');
$controller = app(\App\Http\Controllers\LahanSawahController::class);
$res = $controller->index($req);
$d = json_decode($res->getContent(), true);
echo 'Total Spasial: ' . count($d['data']) . "\n";
if (count($d['data']) > 0) {
    echo 'Sample id: ' . $d['data'][0]['id'] . "\n";
}

$controller2 = app(\App\Http\Controllers\PublicApiController::class);
$res2 = $controller2->getMapLahanTermonitor();
$d2 = json_decode($res2->getContent(), true);
echo 'Total Termonitor: ' . count($d2['features']) . "\n";
if (count($d2['features']) > 0) {
    echo 'Sample device_id: ' . $d2['features'][0]['properties']['device_id'] . "\n";
}
