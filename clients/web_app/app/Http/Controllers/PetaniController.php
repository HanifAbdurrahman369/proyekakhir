<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class PetaniController extends Controller
{
    private $gatewayUrl = 'http://127.0.0.1:8003';

    private function getBearerToken()
    {
        return session('token') ?? session('jwt_token') ?? '';
    }

    public function index(Request $request)
    {
        $token = session('token');

    $response = Http::withToken($token)
        ->acceptJson()
        ->get($this->gatewayUrl . '/api/lahan', [
            'page' => $request->page ?? 1,
        ]);

    $produksiResponse = Http::withToken($token)
        ->acceptJson()
        ->get($this->gatewayUrl . '/api/total-produksi');

    $riwayatResponse = Http::withToken($token)
        ->acceptJson()
        ->get($this->gatewayUrl . '/api/riwayat-panen', [
            'page' => $request->riwayat_page ?? 1,
            'per_page' => 3,
        ]);

    $lahan = [];
    $totalProduksi = 0;
    $riwayat = [];

    if ($response->successful()) {
        $lahan = $response->json()['data'];
    }

    if ($produksiResponse->successful()) {
        $totalProduksi = $produksiResponse->json()['data']['total_produksi'] ?? 0;
    }

    if ($riwayatResponse->successful()) {
        $riwayat = $riwayatResponse->json()['data'] ?? [];
    }

            return view(
                'dashboard.petani',
                compact('lahan', 'totalProduksi', 'riwayat')
            );
        }
}