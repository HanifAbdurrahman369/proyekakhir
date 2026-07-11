<?php
require "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where("role_id", 2)->first();
if (!$user) { echo "No petugas found\n"; exit; }
Auth::login($user);

$controller = app(\App\Http\Controllers\PetugasController::class);
$request = request();

echo "Testing index()...\n";
try {
    $view = $controller->index($request);
    echo "Index success! Type: " . get_class($view) . "\n";
    
    // access view data to trigger rendering exceptions
    $data = $view->getData();
    echo "Data keys: " . implode(", ", array_keys($data)) . "\n";
} catch (\Exception $e) {
    echo "Index Error: " . $e->getMessage() . "\n" . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\nTesting lahanTermonitor()...\n";
try {
    $view = $controller->lahanTermonitor($request);
    echo "lahanTermonitor success!\n";
    $data = $view->getData();
} catch (\Exception $e) {
    echo "lahanTermonitor Error: " . $e->getMessage() . "\n" . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\nTesting verifikasiDataPetani()...\n";
try {
    $view = $controller->verifikasiDataPetani($request);
    echo "verifikasiDataPetani success!\n";
    $data = $view->getData();
} catch (\Exception $e) {
    echo "verifikasiDataPetani Error: " . $e->getMessage() . "\n" . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\nTesting manajemenDataSpasial()...\n";
try {
    $view = $controller->manajemenDataSpasial($request);
    echo "manajemenDataSpasial success!\n";
    $data = $view->getData();
} catch (\Exception $e) {
    echo "manajemenDataSpasial Error: " . $e->getMessage() . "\n" . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\nTesting manajemenKomunitas()...\n";
try {
    $view = $controller->manajemenKomunitas($request);
    echo "manajemenKomunitas success!\n";
    $data = $view->getData();
} catch (\Exception $e) {
    echo "manajemenKomunitas Error: " . $e->getMessage() . "\n" . $e->getFile() . ":" . $e->getLine() . "\n";
}

