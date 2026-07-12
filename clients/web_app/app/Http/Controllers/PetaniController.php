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
        $roleId = (int) session('role_id');

        $response = null;
        if (in_array($roleId, [1, 5], true)) {
            $response = Http::withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/lahan', [
                    'page' => $request->page ?? 1,
                ]);
        }

        $produksiResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl . '/api/total-produksi');

        $riwayatResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl . '/api/riwayat-panen', [
                'riwayat_page' => $request->riwayat_page ?? 1,
                'per_page' => 3,
            ]);

        $siklusResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl . '/api/my-siklus-tanam');

        $lahan = ['data' => [], 'total' => 0, 'current_page' => 1, 'last_page' => 1];
        $totalProduksi = 0;
        $riwayat = [];
        $siklusTanam = [];

        if ($response && $response->successful()) {
            $lahan = $response->json()['data'];
            $totalLahan = $lahan['total'] ?? count($lahan['data'] ?? []);
            session(['total_lahan' => $totalLahan]);
        }

        if ($produksiResponse->successful()) {
            $totalProduksi = $produksiResponse->json()['data']['total_produksi'] ?? 0;
        }

        if ($riwayatResponse->successful()) {
            $riwayat = $riwayatResponse->json()['data'] ?? [];
        }

        if ($siklusResponse->successful()) {
            $siklusTanam = $siklusResponse->json()['data'] ?? [];
        }

        $roleName = $roleId === 5 ? 'Brigade Pangan' : 'Kelompok Tani';

        return view(
            'dashboard.petani',
            compact('lahan', 'totalProduksi', 'riwayat', 'siklusTanam', 'roleId', 'roleName')
        );
    }

}
