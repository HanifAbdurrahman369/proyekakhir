<?php

namespace App\Http\Controllers;

use App\Models\MonitoringKondisi;
use App\Models\LahanSawah;
use Illuminate\Http\Request;

class MonitoringKondisiController extends Controller
{
    public function index(Request $request)
    {
        $query = MonitoringKondisi::with('lahan')
            ->orderByDesc('id');

        if ($request->filled('lahan_id')) {
            $query->where('lahan_id', $request->lahan_id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data monitoring berhasil diambil',
            'data' => $query->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'lahan_id' => 'required|integer',
            'tanggal_cek' => 'required|date',
            'ph_air' => 'nullable|numeric',
            'tinggi_muka_air' => 'nullable|numeric',
            'status_air' => 'nullable|in:Surut,Pasang,Banjir,Normal',
            'kekeruhan_air' => 'nullable|string|max:100',
            'catatan_petugas' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $lahan = LahanSawah::where('id', $request->lahan_id)
            ->where('status_verifikasi', 'DITERIMA')
            ->first();

        if (!$lahan) {
            return response()->json([
                'success' => false,
                'message' => 'Monitoring hanya bisa dibuat untuk lahan yang sudah diterima.'
            ], 403);
        }

        $user = $request->attributes->get('auth');

        $data = MonitoringKondisi::create([
            'lahan_id' => $request->lahan_id,
            'tanggal_cek' => $request->tanggal_cek,
            'ph_air' => $request->ph_air,
            'tinggi_muka_air' => $request->tinggi_muka_air,
            'status_air' => $request->status_air ?? 'Normal',
            'kekeruhan_air' => $request->kekeruhan_air,
            'catatan_petugas' => $request->catatan_petugas,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'created_by' => $user->sub ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data monitoring kondisi berhasil disimpan',
            'data' => $data
        ], 201);
    }

    public function show($id)
    {
        $data = MonitoringKondisi::with('lahan')->find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data monitoring tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}