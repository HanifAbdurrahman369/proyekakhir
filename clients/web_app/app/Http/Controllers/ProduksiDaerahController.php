<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProduksiDaerahController extends Controller
{
    // Mengarah ke API Gateway (Port 8003)
    private $apiUrl = 'http://127.0.0.1:8003/api';

    private function api()
    {
        return Http::withToken(session('token'))->acceptJson()->withoutVerifying();
    }

    /**
     * Menampilkan halaman laporan produksi daerah
     * (Data di-fetch dari browser via /api/produksi-daerah)
     */
    public function index(Request $request)
    {
        return view('produksi-daerah');
    }

    /**
     * Fetch data produksi dari API
     */
    private function fetchProduksiData()
    {
        try {
            // Coba fetch dari endpoint produksi-daerah jika ada
            $response = $this->api()->get($this->apiUrl . '/produksi-daerah');
            
            if ($response->successful()) {
                return $response->json('data') ?? [];
            }

            // Fallback: ambil dari statistik dan filter untuk komoditas
            $statsResponse = $this->api()->get($this->apiUrl . '/statistik');
            if ($statsResponse->successful()) {
                return $this->transformStatistikToProduksi($statsResponse->json('data') ?? []);
            }

            // Fallback: return data dummy
            return $this->getDummyProduksiData();
        } catch (\Exception $e) {
            \Log::error('Failed to fetch produksi data: ' . $e->getMessage());
            return $this->getDummyProduksiData();
        }
    }

    /**
     * Transform statistik data ke format produksi
     */
    private function transformStatistikToProduksi($data)
    {
        $tabelRekap = $data['tabel_rekap'] ?? [];
        $detail = [];

        foreach ($tabelRekap as $item) {
            // Parse rincian per tipe rawa sebagai komoditas
            $komoditas = ['Padi Sawah', 'Padi Gogo', 'Palawija', 'Hortikultura'];
            $totalLuas = (float)($item['total_luas'] ?? 0);
            $totalPanen = (float)($item['total_panen'] ?? 0);
            
            // Jika ada kolom luas_a, luas_b, luas_c, luas_d - gunakan sebagai basis
            foreach ($komoditas as $idx => $komod) {
                $tipe = chr(97 + $idx); // a, b, c, d
                $luasKey = 'luas_' . $tipe;
                $luas = (float)($item[$luasKey] ?? 0);
                
                if ($luas > 0) {
                    $prod = $totalPanen > 0 ? ($totalPanen * ($luas / $totalLuas)) : 0;
                    $produktivitas = $luas > 0 ? ($prod / $luas) : 0;
                    
                    $detail[] = [
                        'daerah' => $item['nama_kecamatan'] ?? 'Unknown',
                        'komoditas' => $komod,
                        'luas_tanam' => (int)$luas,
                        'luas_panen' => (int)$luas,
                        'produksi' => (int)$prod,
                        'produktivitas' => round($produktivitas, 2),
                    ];
                }
            }
        }

        // Calculate totals
        $totalProduksi = array_sum(array_column($detail, 'produksi'));
        $totalLuasPanen = array_sum(array_column($detail, 'luas_panen'));
        $totalLuasTanam = array_sum(array_column($detail, 'luas_tanam'));

        // Chart data
        $chartKomoditas = [];
        $chartDaerah = [];
        foreach ($detail as $item) {
            $komod = $item['komoditas'];
            $daer = $item['daerah'];
            
            if (!isset($chartKomoditas[$komod])) {
                $chartKomoditas[$komod] = [];
                $chartKomoditas[$komod]['nama_komoditas'] = $komod;
                $chartKomoditas[$komod]['total_produksi'] = 0;
            }
            $chartKomoditas[$komod]['total_produksi'] += $item['produksi'];

            if (!isset($chartDaerah[$daer])) {
                $chartDaerah[$daer] = [];
                $chartDaerah[$daer]['nama_daerah'] = $daer;
                $chartDaerah[$daer]['total_produksi'] = 0;
                $chartDaerah[$daer]['produktivitas'] = 0;
            }
            $chartDaerah[$daer]['total_produksi'] += $item['produksi'];
            $chartDaerah[$daer]['produktivitas'] = $item['produktivitas'];
        }

        return [
            'tabel_detail' => $detail,
            'summary' => [
                'total_daerah' => count(array_unique(array_column($detail, 'daerah'))),
                'total_komoditas' => count(array_unique(array_column($detail, 'komoditas'))),
                'total_luas_panen' => $totalLuasPanen,
                'total_produksi' => $totalProduksi,
            ],
            'chart_produksi_komoditas' => array_values($chartKomoditas),
            'chart_produksi_daerah' => array_values($chartDaerah),
            'chart_produktivitas_daerah' => array_values($chartDaerah),
            'chart_luas_tanam_daerah' => array_values($chartDaerah),
        ];
    }

    /**
     * Data dummy untuk fallback
     */
    private function getDummyProduksiData()
    {
        $detail = [
            ['daerah' => 'Subang', 'komoditas' => 'Padi Sawah', 'luas_tanam' => 8500, 'luas_panen' => 8200, 'produksi' => 42500, 'produktivitas' => 5.18],
            ['daerah' => 'Karawang', 'komoditas' => 'Padi Sawah', 'luas_tanam' => 9200, 'luas_panen' => 9000, 'produksi' => 47200, 'produktivitas' => 5.24],
            ['daerah' => 'Indramayu', 'komoditas' => 'Padi Sawah', 'luas_tanam' => 7800, 'luas_panen' => 7600, 'produksi' => 39500, 'produktivitas' => 5.20],
            ['daerah' => 'Cirebon', 'komoditas' => 'Padi Sawah', 'luas_tanam' => 6500, 'luas_panen' => 6300, 'produksi' => 32500, 'produktivitas' => 5.16],
            ['daerah' => 'Subang', 'komoditas' => 'Jagung', 'luas_tanam' => 3200, 'luas_panen' => 3100, 'produksi' => 9800, 'produktivitas' => 3.16],
            ['daerah' => 'Karawang', 'komoditas' => 'Kedelai', 'luas_tanam' => 2500, 'luas_panen' => 2400, 'produksi' => 4200, 'produktivitas' => 1.75],
        ];

        $totalProduksi = array_sum(array_column($detail, 'produksi'));
        $totalLuasPanen = array_sum(array_column($detail, 'luas_panen'));

        return [
            'tabel_detail' => $detail,
            'summary' => [
                'total_daerah' => count(array_unique(array_column($detail, 'daerah'))),
                'total_komoditas' => count(array_unique(array_column($detail, 'komoditas'))),
                'total_luas_panen' => $totalLuasPanen,
                'total_produksi' => $totalProduksi,
            ],
            'chart_produksi_komoditas' => $this->buildChartData($detail, 'komoditas'),
            'chart_produksi_daerah' => $this->buildChartData($detail, 'daerah'),
            'chart_produktivitas_daerah' => $this->buildChartData($detail, 'daerah'),
            'chart_luas_tanam_daerah' => $this->buildChartData($detail, 'daerah'),
        ];
    }

    /**
     * Build chart data by grouping
     */
    private function buildChartData($data, $groupBy)
    {
        $grouped = [];
        foreach ($data as $item) {
            $key = $item[$groupBy];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'nama_' . $groupBy => $key,
                    'total_produksi' => 0,
                    'total_luas' => 0,
                    'produktivitas' => 0,
                ];
            }
            $grouped[$key]['total_produksi'] += $item['produksi'];
            $grouped[$key]['total_luas'] += $item['luas_tanam'];
            $grouped[$key]['produktivitas'] = $item['produktivitas'];
        }
        return array_values($grouped);
    }

    /**
     * Extract unique values dari array
     */
    private function extractUniqueValues($data, $key)
    {
        return array_unique(array_column($data, $key));
    }
}

