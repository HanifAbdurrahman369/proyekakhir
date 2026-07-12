<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PanenProxyController extends Controller
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

    public function pending(Request $request)
    {
        $response = Http::withHeaders($this->headers($request))
            ->get($this->farmingBaseUrl() . '/panen/pending');

        return response($response->body(), $response->status())
            ->header('Content-Type', $response->header('Content-Type', 'application/json'));
    }

    public function verifikasi(Request $request, $id)
    {
        $response = Http::withHeaders($this->headers($request))
            ->post($this->farmingBaseUrl() . '/panen/' . $id . '/verifikasi', [
                'aksi' => $request->input('aksi'),
                'status' => $request->input('status'),
            ]);

        return response($response->body(), $response->status())
            ->header('Content-Type', $response->header('Content-Type', 'application/json'));
    }
}