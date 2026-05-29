<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PetugasController extends Controller
{
    private $gatewayUrl = 'http://127.0.0.1:8003/api';

    /**
     * Mengambil token JWT petugas dari session web_app
     */
    private function getBearerToken()
    {
        return session('token') ?? session('jwt_token') ?? '';
    }

    /**
     * Halaman Utama Dashboard Petugas
     */
    public function index()
    {
        $token = $this->getBearerToken();

        // Inisialisasi struktur default agar aman dari error "Undefined variable"
        $referensi = [
            'petani' => [],
            'kecamatan' => [],
            'kelurahan' => [],
            'tipe_lahan' => []
        ];
        $koleksiLahan = [
            'type' => 'FeatureCollection',
            'features' => []
        ];

        // Jalankan pemanggilan API jika token tersedia
        if (!empty($token)) {
            try {
                // 1. Ambil data dropdown referensi wilayah & petani
                $resReferensi = Http::withToken($token)->get($this->gatewayUrl . '/spasial-lahan/referensi');
                if ($resReferensi->successful()) {
                    $referensi = $resReferensi->json('data') ?? $referensi;
                }

                // 2. Ambil data koleksi spasial poligon lahan sawah
                $resLahan = Http::withToken($token)->get($this->gatewayUrl . '/spasial-lahan');
                if ($resLahan->successful()) {
                    $koleksiLahan = $resLahan->json() ?? $koleksiLahan;
                }
            } catch (\Exception $e) {
                // Pengecualian jika service gateway/gis mati, data tetap aman berbentuk array kosong
            }
        }

        $isMasterMode = false; // Memastikan pemisahan navigasi internal

        return view('dashboard.petugas', compact('referensi', 'koleksiLahan', 'isMasterMode'));
    }

    /**
     * Simpan Poligon Lahan Baru
     */
    public function storeSpasial(Request $request)
    {
        $token = $this->getBearerToken();
        $response = Http::withToken($token)->post($this->gatewayUrl . '/spasial-lahan', $request->all());

        if ($response->successful()) {
            return redirect('/dashboard-petugas')->with('success', 'Data poligon lahan sawah baru berhasil dipetakan.');
        }

        $errorMsg = $response->json('message') ?? 'Gagal menyimpan data spasial lahan.';
        return back()->with('error', $errorMsg)->withInput();
    }

    /**
     * Perbarui Atribut & Geometri Lahan
     */
    public function updateSpasial(Request $request, $id)
    {
        $token = $this->getBearerToken();
        $response = Http::withToken($token)->put($this->gatewayUrl . "/spasial-lahan/$id", $request->all());

        if ($response->successful()) {
            return redirect('/petugas/dashboard')->with('success', 'Perubahan spasial lahan berhasil diperbarui.');
        }

        $errorMsg = $response->json('message') ?? 'Gagal memperbarui data spasial.';
        return back()->with('error', $errorMsg)->withInput();
    }

    /**
     * Hapus Poligon Lahan
     */
    public function destroySpasial($id)
    {
        $token = $this->getBearerToken();
        $response = Http::withToken($token)->delete($this->gatewayUrl . "/spasial-lahan/$id");

        if ($response->successful()) {
            return redirect('/petugas/dashboard')->with('success', 'Data spasial lahan berhasil dihapus dari peta publik.');
        }

        return back()->with('error', 'Gagal menghapus data spasial lahan.');
    }
}