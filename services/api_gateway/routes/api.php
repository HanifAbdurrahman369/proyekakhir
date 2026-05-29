<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

$serviceMap = [
    'auth' => env('AUTH_SERVICE_URL', 'http://127.0.0.1:8001'),
    'user' => env('USER_SERVICE_URL', 'http://127.0.0.1:8002'),
    'farming' => env('FARMING_SERVICE_URL', 'http://127.0.0.1:8005'),
    'gis' => env('GIS_SERVICE_URL', 'http://127.0.0.1:8000'),
];

function proxyRequest(Request $request, string $serviceUrl, string $path)
{
    $url = rtrim($serviceUrl, '/') . '/api/' . ltrim($path, '/');

    $headers = collect($request->headers->all())
        ->except(['host', 'content-length'])
        ->mapWithKeys(fn ($value, $key) => [$key => implode(',', $value)])
        ->toArray();

    // Pastikan Authorization header tetap diteruskan ke service target.
    if ($authHeader = $request->header('Authorization')) {
        $headers['Authorization'] = $authHeader;
    }

    if ($authHeader = $request->header('authorization')) {
        $headers['Authorization'] = $authHeader;
    }

    $options = [
        'headers' => $headers,
        'query' => $request->query(),
    ];

    if (!in_array($request->method(), ['GET', 'HEAD'], true)) {
        $options['json'] = $request->all();
    }

    $response = Http::withOptions(['verify' => false])->send($request->method(), $url, $options);

    return response($response->body(), $response->status())
        ->withHeaders($response->headers());
}

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/login', fn (Request $request) => proxyRequest($request, $serviceMap['auth'], 'login'));
Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/profile', fn (Request $request) => proxyRequest($request, $serviceMap['auth'], 'profile'));

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/register', fn (Request $request) => proxyRequest($request, $serviceMap['user'], 'register'));
Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/find-user', fn (Request $request) => proxyRequest($request, $serviceMap['user'], 'find-user'));
Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/forgot-password', fn (Request $request) => proxyRequest($request, $serviceMap['user'], 'forgot-password'));
Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/forget-password', fn (Request $request) => proxyRequest($request, $serviceMap['user'], 'forget-password'));
Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/reset-password', fn (Request $request) => proxyRequest($request, $serviceMap['user'], 'reset-password'));

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/statistik', fn (Request $request) => proxyRequest($request, $serviceMap['gis'], 'statistik'));
Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/map-lahan', fn (Request $request) => proxyRequest($request, $serviceMap['gis'], 'map-lahan'));
Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/batas-wilayah', fn (Request $request) => proxyRequest($request, $serviceMap['gis'], 'batas-wilayah'));

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/riwayat-panen', fn (Request $request) => proxyRequest($request, $serviceMap['farming'], 'riwayat-panen'));

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/activities/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('activities/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/bibit/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('bibit/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/lahan/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('lahan/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/users/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('users/' . $any, '/');
    return proxyRequest($request, $serviceMap['user'], $path);
})->where('any', '.*');

Route::any('/{service}/{any}', function (Request $request, $service, $any) use ($serviceMap) {
    if (!isset($serviceMap[$service])) {
        abort(404, 'Service not registered in API gateway');
    }

    return proxyRequest($request, $serviceMap[$service], $any);
})->where('any', '.*');
Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/spasial-lahan/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('spasial-lahan/' . $any, '/');
    return proxyRequest($request, $serviceMap['gis'], $path);
})->where('any', '.*');