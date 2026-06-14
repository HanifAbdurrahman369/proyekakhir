<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Gateway Routes - Port 8003
|--------------------------------------------------------------------------
| Semua request dari web_app wajib masuk melalui API Gateway terlebih dahulu.
| Target service menggunakan 127.0.0.1 agar tidak terkena bug resolusi IPv6
| dari localhost.
|--------------------------------------------------------------------------
*/

$serviceMap = [
    'auth'    => 'http://127.0.0.1:8001',
    'user'    => 'http://127.0.0.1:8002',
    'master'  => 'http://127.0.0.1:8004',
    'farming' => 'http://127.0.0.1:8005',
    'gis'     => 'http://127.0.0.1:8000',
];

function proxyRequest(Request $request, string $serviceUrl, string $path)
{
    $url = rtrim($serviceUrl, '/') . '/api/' . ltrim($path, '/');

    $headers = collect($request->headers->all())
        ->except(['host', 'content-length'])
        ->mapWithKeys(fn ($value, $key) => [$key => implode(',', $value)])
        ->toArray();

    if ($authHeader = $request->header('Authorization')) {
        $headers['Authorization'] = $authHeader;
    }

    if ($authHeader = $request->header('authorization')) {
        $headers['Authorization'] = $authHeader;
    }

    $options = [
        'headers' => $headers,
        'query'   => $request->query(),
        'timeout' => 5,
    ];

    if (!in_array($request->method(), ['GET', 'HEAD'])) {
        $options['body'] = $request->getContent();
    }

    try {
        $client = new \GuzzleHttp\Client();
        $response = $client->request($request->method(), $url, $options);

        return response($response->getBody()->getContents(), $response->getStatusCode())
            ->withHeaders($response->getHeaders());

    } catch (\GuzzleHttp\Exception\RequestException $e) {
        if ($e->hasResponse()) {
            $response = $e->getResponse();

            return response($response->getBody()->getContents(), $response->getStatusCode())
                ->withHeaders($response->getHeaders());
        }

        return response()->json([
            'success' => false,
            'message' => 'GATEWAY_ERROR: Koneksi terputus ke ' . $url,
        ], 502);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'GATEWAY_FATAL: ' . $e->getMessage(),
        ], 500);
    }
}

/*
|--------------------------------------------------------------------------
| 1. AUTH COMPATIBILITY ROUTES
|--------------------------------------------------------------------------
| web_app AuthController saat ini memanggil:
| /api/login
| /api/register
| /api/forgot-password
| /api/forget-password
|
| Maka route ini wajib ada di API Gateway agar request login tidak 404.
|--------------------------------------------------------------------------
*/

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/login', function (Request $request) use ($serviceMap) {
    return proxyRequest($request, $serviceMap['auth'], 'login');
});

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/register', function (Request $request) use ($serviceMap) {
    return proxyRequest($request, $serviceMap['auth'], 'register');
});

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/forgot-password', function (Request $request) use ($serviceMap) {
    return proxyRequest($request, $serviceMap['auth'], 'forgot-password');
});

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/forget-password', function (Request $request) use ($serviceMap) {
    return proxyRequest($request, $serviceMap['auth'], 'forget-password');
});

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/reset-password', function (Request $request) use ($serviceMap) {
    return proxyRequest($request, $serviceMap['auth'], 'reset-password');
});

/*
|--------------------------------------------------------------------------
| 2. AUTH PREFIX ROUTES
|--------------------------------------------------------------------------
| Mendukung format baru:
| /api/auth/login
| /api/auth/register
|--------------------------------------------------------------------------
*/

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/auth/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    return proxyRequest($request, $serviceMap['auth'], $any);
})->where('any', '.*');

/*
|--------------------------------------------------------------------------
| 3. ROLE PETUGAS - Manajemen Data Spasial
|--------------------------------------------------------------------------
*/

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/spasial-lahan/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('spasial-lahan/' . $any, '/');
    return proxyRequest($request, $serviceMap['gis'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/notifikasi/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('notifikasi/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/monitoring/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('monitoring/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

/*
|--------------------------------------------------------------------------
| 4. PETUGAS - Verifikasi Hasil Panen
|--------------------------------------------------------------------------
| Route eksplisit agar /api/panen/pending tidak jatuh ke dynamic service route.
|--------------------------------------------------------------------------
*/

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/panen/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('panen/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

/*
|--------------------------------------------------------------------------
| 5. RUTE LAMA - Dipertahankan
|--------------------------------------------------------------------------
*/

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/produksi-pejabat/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('produksi-pejabat/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/total-lahan/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('total-lahan/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/produksi-kecamatan/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('produksi-kecamatan/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/lahan-kecamatan/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('lahan-kecamatan/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/produksi-bulanan/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('produksi-bulanan/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/top-kecamatan/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('top-kecamatan/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/riwayat-panen/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('riwayat-panen/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/activities/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('activities/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/total-produksi/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('total-produksi/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/bibit/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('bibit/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/jenis-pupuk/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('jenis-pupuk/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/my-siklus-tanam/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('my-siklus-tanam/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/siklus-pupuk/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('siklus-pupuk/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/lahan/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('lahan/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/lahan/dropdown/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('lahan/dropdown/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/kecamatan/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('kecamatan/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/kelurahan/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('kelurahan/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/tipe-lahan/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('tipe-lahan/' . $any, '/');
    return proxyRequest($request, $serviceMap['farming'], $path);
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/users/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    $path = trim('users/' . $any, '/');
    return proxyRequest($request, $serviceMap['user'], $path);
})->where('any', '.*');


Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/map-lahan/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    return proxyRequest($request, $serviceMap['gis'], trim('map-lahan/' . $any, '/'));
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/statistik/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    return proxyRequest($request, $serviceMap['gis'], trim('statistik/' . $any, '/'));
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/batas-wilayah/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    return proxyRequest($request, $serviceMap['gis'], trim('batas-wilayah/' . $any, '/'));
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/batas-kecamatan/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    return proxyRequest($request, $serviceMap['gis'], trim('batas-kecamatan/' . $any, '/'));
})->where('any', '.*');

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '/master/{any?}', function (Request $request, $any = '') use ($serviceMap) {
    return proxyRequest($request, $serviceMap['master'], $any);
})->where('any', '.*');

/*
|--------------------------------------------------------------------------
| 5. DYNAMIC SERVICE ROUTE
|--------------------------------------------------------------------------
| Contoh:
| /api/auth/login
| /api/gis/spasial-lahan
| /api/farming/activities
|--------------------------------------------------------------------------
*/

Route::any('/{service}/{any}', function (Request $request, $service, $any) use ($serviceMap) {
    if (!isset($serviceMap[$service])) {
        return response()->json([
            'success' => false,
            'message' => 'Service not registered in API Gateway: ' . $service,
        ], 404);
    }

    return proxyRequest($request, $serviceMap[$service], $any);
})->where('any', '.*');
