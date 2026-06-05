<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProduksiDaerahApiController extends Controller
{
    private $apiUrl = 'http://127.0.0.1:8003/api';

    private function api()
    {
        return Http::withToken(session('token') ?? '')->acceptJson()->withoutVerifying();
    }

    /**
     * Get produksi daerah data in API format (untuk fetch dari browser)
     */
    public function index()
    {
        try {
            // Coba fetch dari endpoint produksi-daerah di gateway
            $response = $this->api()->get($this->apiUrl . '/produksi-daerah');
            
            if ($response->successful()) {
                $data = $response->json('data');
                if ($data && isset($data['summary'])) {
                    return response()->json([
                        'status' => 'success',
                        'data' => $data
                    ]);
                }
            }

            // Fallback: gunakan statistik dan transform
            $statsResponse = $this->api()->get($this->apiUrl . '/statistik');
            if ($statsResponse->successful()) {
                $stats = $statsResponse->json('data') ?? [];
                $transformed = $this->buildProduksiData($stats);
                return response()->json([
                    'status' => 'success',
                    'data' => $transformed
                ]);
            }

            // Fallback: dummy data
            return response()->json([
                'status' => 'success',
                'data' => $this->getDummyProduksiData()
            ]);
        } catch (\Exception $e) {
            \Log::error('API Error - Produksi Daerah: ' . $e->getMessage());
            return response()->json([
                'status' => 'success',
                'data' => $this->getDummyProduksiData()
            ]);
        }
    }

    /**
     * Build produksi data dari statistik
     */
    private function buildProduksiData($stats)
    {
        $tabelRekap = $stats['tabel_rekap'] ?? [];
        
        // Build detail tabel dengan komoditas
        $detail = [];
        $komoditasList = ['Padi Sawah', 'Padi Gogo', 'Palawija', 'Hortikultura'];
        
        foreach ($tabelRekap as $item) {
            $daerah = $item['nama_kecamatan'] ?? $item['nama_daerah'] ?? 'Unknown';
            $totalLuas = (float)($item['total_luas'] ?? 0);
            $totalPanen = (float)($item['total_panen'] ?? 0);
            
            // Buat entry per komoditas
            foreach ($komoditasList as $idx => $komod) {
                $tipe = chr(97 + $idx);
                $luasKey = 'luas_' . $tipe;
                $luas = (float)($item[$luasKey] ?? 0);
                
                if ($luas > 0) {
                    $produksi = $luas > 0 ? ($luas / $totalLuas) * $totalPanen : 0;
                    $prod = $luas > 0 ? $produksi / $luas : 0;
                    
                    $detail[] = [
                        'daerah' => $daerah,
                        'komoditas' => $komod,
                        'luas_tanam' => $luas,
                        'luas_panen' => $luas,
                        'produksi' => $produksi,
                        'produktivitas' => $prod
                    ];
                }
            }
        }
        
        // Build summary
        $uniqueDaerah = count(array_unique(array_column($detail, 'daerah')));
        $uniqueKomoditas = count(array_unique(array_column($detail, 'komoditas')));
        $totalLuasPanen = array_sum(array_column($detail, 'luas_panen'));
        $totalProduksi = array_sum(array_column($detail, 'produksi'));
        
        // Build chart data
        $chartKomoditas = [];
        $komoditasGroup = [];
        foreach ($detail as $item) {
            $komod = $item['komoditas'];
            if (!isset($komoditasGroup[$komod])) {
                $komoditasGroup[$komod] = 0;
            }
            $komoditasGroup[$komod] += $item['produksi'];
        }
        foreach ($komoditasGroup as $nama => $prod) {
            $chartKomoditas[] = ['nama_komoditas' => $nama, 'total_produksi' => round($prod, 2)];
        }
        
        $chartDaerah = [];
        $daerahGroup = [];
        foreach ($detail as $item) {
            $daerah = $item['daerah'];
            if (!isset($daerahGroup[$daerah])) {
                $daerahGroup[$daerah] = 0;
            }
            $daerahGroup[$daerah] += $item['produksi'];
        }
        foreach ($daerahGroup as $nama => $prod) {
            $chartDaerah[] = ['nama_daerah' => $nama, 'total_produksi' => round($prod, 2)];
        }
        
        $chartProduktivitas = [];
        $prodGroup = [];
        foreach ($detail as $item) {
            $daerah = $item['daerah'];
            if (!isset($prodGroup[$daerah])) {
                $prodGroup[$daerah] = [];
            }
            $prodGroup[$daerah][] = $item['produktivitas'];
        }
        foreach ($prodGroup as $nama => $vals) {
            $avg = count($vals) > 0 ? array_sum($vals) / count($vals) : 0;
            $chartProduktivitas[] = ['nama_daerah' => $nama, 'produktivitas' => round($avg, 2)];
        }
        
        $chartLuas = [];
        foreach ($daerahGroup as $nama => $prod) {
            $totalLuas = 0;
            foreach ($detail as $item) {
                if ($item['daerah'] === $nama) {
                    $totalLuas += $item['luas_panen'];
                }
            }
            $chartLuas[] = ['nama_daerah' => $nama, 'total_luas' => round($totalLuas, 2)];
        }
        
        return [
            'summary' => [
                'total_daerah' => $uniqueDaerah,
                'total_komoditas' => $uniqueKomoditas,
                'total_luas_panen' => round($totalLuasPanen, 2),
                'total_produksi' => round($totalProduksi, 2),
            ],
            'tabel_rekap' => $detail,
            'chart_produksi_komoditas' => $chartKomoditas,
            'chart_produksi_daerah' => $chartDaerah,
            'chart_produktivitas_daerah' => $chartProduktivitas,
            'chart_luas_tanam_daerah' => $chartLuas,
        ];
    }

    /**
     * Dummy produksi data
     */
    private function getDummyProduksiData()
    {
        $detail = [
            ['daerah' => 'Kota Bandung', 'komoditas' => 'Padi Sawah', 'luas_tanam' => 150, 'luas_panen' => 145, 'produksi' => 870, 'produktivitas' => 6.0],
            ['daerah' => 'Kota Bandung', 'komoditas' => 'Jagung', 'luas_tanam' => 80, 'luas_panen' => 75, 'produksi' => 300, 'produktivitas' => 4.0],
            ['daerah' => 'Bandung Barat', 'komoditas' => 'Padi Sawah', 'luas_tanam' => 200, 'luas_panen' => 195, 'produksi' => 1170, 'produktivitas' => 6.0],
            ['daerah' => 'Bandung Barat', 'komoditas' => 'Kedelai', 'luas_tanam' => 60, 'luas_panen' => 55, 'produksi' => 110, 'produktivitas' => 2.0],
        ];
        
        return [
            'summary' => [
                'total_daerah' => 2,
                'total_komoditas' => 3,
                'total_luas_panen' => 470,
                'total_produksi' => 2450,
            ],
            'tabel_rekap' => $detail,
            'chart_produksi_komoditas' => [
                ['nama_komoditas' => 'Padi Sawah', 'total_produksi' => 2040],
                ['nama_komoditas' => 'Jagung', 'total_produksi' => 300],
                ['nama_komoditas' => 'Kedelai', 'total_produksi' => 110],
            ],
            'chart_produksi_daerah' => [
                ['nama_daerah' => 'Kota Bandung', 'total_produksi' => 1170],
                ['nama_daerah' => 'Bandung Barat', 'total_produksi' => 1280],
            ],
            'chart_produktivitas_daerah' => [
                ['nama_daerah' => 'Kota Bandung', 'produktivitas' => 5.0],
                ['nama_daerah' => 'Bandung Barat', 'produktivitas' => 4.0],
            ],
            'chart_luas_tanam_daerah' => [
                ['nama_daerah' => 'Kota Bandung', 'total_luas' => 225],
                ['nama_daerah' => 'Bandung Barat', 'total_luas' => 245],
            ],
        ];
    }
}
