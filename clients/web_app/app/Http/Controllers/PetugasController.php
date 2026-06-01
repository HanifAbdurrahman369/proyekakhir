<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PetugasController extends Controller
{
    private function gatewayUrl() {
        return env('GATEWAY_URL', 'http://127.0.0.1:8003') . '/api';
    }

    private function getToken() {
        return session('token') ?? session('jwt_token');
    }

    // HALAMAN 1: DASHBOARD
    public function index()
    {
        return view('dashboard.petugas', ['page' => 'dashboard']);
    }

    // HALAMAN 2: MAPPING WILAYAH
    public function petaLahan()
    {
        $token = $this->getToken();
        $referensi = ['petani' => [], 'kecamatan' => [], 'kelurahan' => [], 'tipe_lahan' => []];
        $koleksiLahan = ['type' => 'FeatureCollection', 'features' => []];

        if ($token) {
            $resRef = Http::withToken($token)->get($this->gatewayUrl() . '/spasial-lahan/referensi');
            if ($resRef->successful()) $referensi = $resRef->json('data');

            $resLahan = Http::withToken($token)->get($this->gatewayUrl() . '/spasial-lahan');
            if ($resLahan->successful()) $koleksiLahan = $resLahan->json();
        }

        return view('dashboard.petugas', [
            'page' => 'peta',
            'referensi' => $referensi,
            'koleksiLahan' => $koleksiLahan
        ]);
    }

    // HALAMAN 3: VERIFIKASI DATA
    public function verifikasiPanen()
    {
        $token = $this->getToken();
        $antrean = [];

        if ($token) {
            $response = Http::withToken($token)->get($this->gatewayUrl() . '/activities/pending');
            if ($response->successful()) $antrean = $response->json('data') ?? [];
        }

        return view('dashboard.petugas', ['page' => 'verifikasi', 'antrean' => $antrean]);
    }

    // AKSI 1: SIMPAN PETA (Form Submit Tradisional)
    public function storeSpasial(Request $request)
    {
        $token = $this->getToken();
        $response = Http::withToken($token)->post($this->gatewayUrl() . '/spasial-lahan', $request->all());

        if ($response->successful()) {
            return redirect('/peta-lahan')->with('success', 'Data poligon lahan sawah berhasil dipetakan.');
        }
        return back()->with('error', $response->json('message') ?? 'Gagal menyimpan data spasial.')->withInput();
    }

    // AKSI 2: SETUJUI / TOLAK DATA (Form Submit Tradisional)
    public function aksiVerifikasi($id, $aksi)
    {
        $token = $this->getToken();
        $response = Http::withToken($token)->post($this->gatewayUrl() . "/activities/{$id}/{$aksi}");

        if ($response->successful()) {
            $msg = $aksi == 'approve' ? 'Data berhasil disetujui' : 'Data berhasil ditolak';
            return redirect('/verifikasi-panen')->with('success', $msg);
        }
        return back()->with('error', 'Gagal memproses validasi data.');
    }
}