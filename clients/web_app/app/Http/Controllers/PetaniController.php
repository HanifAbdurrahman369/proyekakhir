<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class PetaniController extends Controller
{
    private function gatewayUrl(): string
    {
        return rtrim(env('GATEWAY_URL', env('API_GATEWAY_URL', 'http://127.0.0.1:8003')), '/');
    }

    private function getBearerToken()
    {
        return session('token') ?? session('jwt_token') ?? '';
    }

    private function api()
    {
        return Http::withToken($this->getBearerToken())
            ->acceptJson()
            ->withoutVerifying()
            ->timeout(15)
            ->connectTimeout(5);
    }

    private function getJson(string $endpoint, array $query = []): array
    {
        if (!$this->getBearerToken()) {
            return [];
        }

        try {
            $response = $this->api()->get($this->gatewayUrl() . '/api/' . ltrim($endpoint, '/'), $query);

            if (!$response->successful()) {
                return [];
            }

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    public function index(Request $request)
    {
        $roleId = (int) session('role_id');

        $lahan = ['data' => [], 'total' => 0, 'current_page' => 1, 'last_page' => 1];
        $totalProduksi = 0;
        $riwayat = [];
        $siklusTanam = [];

        if (in_array($roleId, [1, 5], true)) {
            $lahanPayload = $this->getJson('/lahan', [
                'page' => $request->page ?? 1,
            ]);
            $lahan = $lahanPayload['data'] ?? $lahan;
            $totalLahan = $lahan['total'] ?? count($lahan['data'] ?? []);
            session(['total_lahan' => $totalLahan]);
        }

        $produksiPayload = $this->getJson('/total-produksi');
        $totalProduksi = $produksiPayload['data']['total_produksi'] ?? 0;

        $riwayatPayload = $this->getJson('/riwayat-panen', [
            'riwayat_page' => $request->riwayat_page ?? 1,
            'per_page' => 3,
        ]);
        $riwayat = $riwayatPayload['data'] ?? [];

        $siklusPayload = $this->getJson('/my-siklus-tanam');
        $siklusTanam = $siklusPayload['data'] ?? [];

        $roleName = $roleId === 5 ? 'Brigade Pangan' : 'Kelompok Tani';

        return view(
            'dashboard.petani',
            compact('lahan', 'totalProduksi', 'riwayat', 'siklusTanam', 'roleId', 'roleName')
        );
    }

}
