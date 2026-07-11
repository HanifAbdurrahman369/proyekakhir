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
        
        $formatted = $lands->map(function ($land) {
            $catatan = json_decode($land->catatan_verifikasi, true);
            $land->pemilik_lahan = $catatan['huma_owner_name'] ?? 'Petani Huma';
            $land->nama_petani = $catatan['huma_owner_name'] ?? 'Petani Huma';
            return $land;
        });

        return response()->json([
            'success' => true,
            'data' => $formatted
        ]);
    }

    public function monitoring()
    {
        $sensors = $this->humaService->getMonitoringTermonitor();
        
        // Return raw sensor data so web app can process it
        $formatted = $sensors->map(function ($sensor) {
            return $sensor->toArray();
        });

        return response()->json([
            'success' => true,
            'data' => $formatted
        ]);
    }
}
