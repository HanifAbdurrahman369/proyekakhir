<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\HumaIntegrationService;

class LahanTermonitorController extends Controller
{
    protected $humaService;

    public function __construct(HumaIntegrationService $humaService)
    {
        $this->humaService = $humaService;
    }

    public function preview()
    {
        try {
            $data = $this->humaService->getPreview();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil preview: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sync(Request $request)
    {
        try {
            $result = $this->humaService->syncData();
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal sinkronisasi data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        $lands = $this->humaService->getLahanTermonitor();
        return response()->json([
            'success' => true,
            'data' => $lands
        ]);
    }

    public function monitoring()
    {
        $sensors = $this->humaService->getMonitoringTermonitor();
        
        // Format response if needed
        $formatted = $sensors->map(function ($sensor) {
            $catatan = json_decode($sensor->catatan_petugas, true);
            return [
                'id' => $sensor->id,
                'nama_lahan' => $sensor->lahan->nama_lahan ?? '-',
                'device_id' => $catatan['huma_device_id'] ?? '-',
                'ph_tanah' => $catatan['ph_tanah'] ?? $sensor->ph_air,
                'n' => $catatan['n_level'] ?? '-',
                'p' => $catatan['p_level'] ?? '-',
                'k' => $catatan['k_level'] ?? '-',
                'waktu_rekam' => $sensor->tanggal_cek,
                'status_sinkron' => 'Tersinkronisasi'
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formatted
        ]);
    }
}
