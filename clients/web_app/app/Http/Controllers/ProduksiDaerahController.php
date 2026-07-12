<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProduksiDaerahController extends Controller
{
    private function gatewayUrl(): string
    {
        return rtrim(env('GATEWAY_URL', env('API_GATEWAY_URL', 'http://127.0.0.1:8003')), '/');
    }

    private function api()
    {
        return Http::withToken(session('token'))
            ->acceptJson()
            ->withoutVerifying()
            ->timeout(15)
            ->connectTimeout(5);
    }

    /**
     * Menampilkan halaman laporan produksi daerah
     * (Data di-fetch dari browser via /api/produksi-daerah)
     */
    public function index(Request $request)
    {
        return view('produksi-daerah', ['showTable' => true]);
    }

    /**
     * Endpoint kompatibilitas untuk view laporan lama.
     */
    public function data()
    {
        try {
            $response = $this->api()->get($this->gatewayUrl() . '/api/statistik');
            $statistik = $response->successful() ? ($response->json('data') ?? []) : [];
        } catch (\Throwable $e) {
            report($e);
            $statistik = [];
        }

        $summary = $statistik['summary'] ?? [];
        $rekap = collect($statistik['tabel_rekap'] ?? []);
        $panenKecamatan = collect($statistik['chart_panen_kecamatan'] ?? []);
        $luasKecamatan = collect($statistik['chart_luas_kecamatan'] ?? []);

        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => [
                'summary' => [
                    'total_daerah' => (int) ($summary['total_kecamatan'] ?? 0),
                    'total_komoditas' => ((float) ($summary['total_panen_ton'] ?? 0)) > 0 ? 1 : 0,
                    'total_luas_panen' => (float) ($summary['total_luas_tanam_ha'] ?? $summary['total_luas_ha'] ?? 0),
                    'total_produksi' => (float) ($summary['total_panen_ton'] ?? 0),
                ],
                'chart_produksi_komoditas' => [
                    [
                        'nama_komoditas' => 'Padi',
                        'total_produksi' => (float) ($summary['total_panen_ton'] ?? 0),
                    ],
                ],
                'chart_produksi_daerah' => $panenKecamatan->map(fn ($item) => [
                    'nama_daerah' => $item['nama_kecamatan'] ?? '-',
                    'total_produksi' => (float) ($item['total_panen'] ?? 0),
                ])->values()->all(),
                'chart_produktivitas_daerah' => $rekap->map(function ($item) {
                    $luasTanam = (float) ($item['total_luas_tanam'] ?? $item['total_luas'] ?? 0);
                    $panen = (float) ($item['total_panen'] ?? 0);

                    return [
                        'nama_daerah' => $item['nama_kecamatan'] ?? '-',
                        'produktivitas' => $luasTanam > 0 ? round($panen / $luasTanam, 2) : 0,
                    ];
                })->values()->all(),
                'chart_luas_tanam_daerah' => $luasKecamatan->map(fn ($item) => [
                    'nama_daerah' => $item['nama_kecamatan'] ?? '-',
                    'total_luas' => (float) ($item['total_luas_tanam'] ?? $item['total_luas'] ?? 0),
                ])->values()->all(),
                'tabel_rekap' => $rekap->map(function ($item) {
                    $luasTanam = (float) ($item['total_luas_tanam'] ?? $item['total_luas'] ?? 0);
                    $panen = (float) ($item['total_panen'] ?? 0);

                    return [
                        'daerah' => $item['nama_kecamatan'] ?? '-',
                        'komoditas' => 'Padi',
                        'luas_tanam' => $luasTanam,
                        'luas_panen' => $luasTanam,
                        'produksi' => $panen,
                        'produktivitas' => $luasTanam > 0 ? round($panen / $luasTanam, 2) : 0,
                    ];
                })->values()->all(),
            ],
        ]);
    }
}
