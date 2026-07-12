<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NotifikasiProxyController extends Controller
{
    private function farmingBaseUrl()
    {
        return rtrim(env('FARMING_SERVICE_URL', 'http://127.0.0.1:8004/api'), '/');
    }

    private function headers(Request $request)
    {
        $headers = [
            'Accept' => 'application/json',
        ];

        if ($request->bearerToken()) {
            $headers['Authorization'] = 'Bearer ' . $request->bearerToken();
        }

        return $headers;
    }

    public function index(Request $request)
    {
        $response = Http::withHeaders($this->headers($request))
            ->get($this->farmingBaseUrl() . '/notifikasi');

        return response($response->body(), $response->status())
            ->header('Content-Type', $response->header('Content-Type', 'application/json'));
    }

    public function show(Request $request, $id)
    {
        $response = Http::withHeaders($this->headers($request))
            ->get($this->farmingBaseUrl() . '/notifikasi/' . $id);

        return response($response->body(), $response->status())
            ->header('Content-Type', $response->header('Content-Type', 'application/json'));
    }

    public function read(Request $request, $id)
    {
        $response = Http::withHeaders($this->headers($request))
            ->post($this->farmingBaseUrl() . '/notifikasi/' . $id . '/read');

        return response($response->body(), $response->status())
            ->header('Content-Type', $response->header('Content-Type', 'application/json'));
    }
}