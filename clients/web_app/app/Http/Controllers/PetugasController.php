<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PetugasController extends Controller
{
    private $gatewayUrl = 'http://127.0.0.1:8003/api';

    private function getBearerToken()
    {
        return session('token') ?? session('jwt_token') ?? '';
    }

    // ---------------------------------------------------------
    // RENDER HALAMAN BERANDA
    // ---------------------------------------------------------
    public function index()
    {
        return view('dashboard.petugas', ['page' => 'dashboard']);
    }

    // ---------------------------------------------------------
    // RENDER HALAMAN MANAJEMEN DATA SPASIAL
    // ---------------------------------------------------------
    public function manajemenDataSpasial()
    {
        $token = $this->getBearerToken();
        $referensi = ['petani' => [], 'kecamatan' => [], 'kelurahan' => [], 'tipe_lahan' => []];
        $koleksiLahan = ['type' => 'FeatureCollection', 'features' => []];

        if (!empty($token)) {
            try {
                $resRef = Http::withToken($token)->timeout(5)->get($this->gatewayUrl . '/spasial-lahan/referensi');
                if ($resRef->successful()) $referensi = $resRef->json('data') ?? $referensi;

                $resLahan = Http::withToken($token)->timeout(5)->get($this->gatewayUrl . '/spasial-lahan');
                if ($resLahan->successful()) $koleksiLahan = $resLahan->json() ?? $koleksiLahan;
            } catch (\Exception $e) {}
        }

        return view('dashboard.petugas', [
            'page' => 'manajemen-data-spasial',
            'referensi' => $referensi,
            'koleksiLahan' => $koleksiLahan
        ]);
    }

    // ---------------------------------------------------------
    // RENDER HALAMAN INPUT PARAMETER LINGKUNGAN
    // ---------------------------------------------------------
    public function inputParameterLingkungan()
    {
        return view('dashboard.petugas', ['page' => 'input-parameter-lingkungan']);
    }

    // ---------------------------------------------------------
    // RENDER HALAMAN VERIFIKASI DATA PETANI
    // ---------------------------------------------------------
    public function verifikasiDataPetani()
    {
        $token = $this->getBearerToken();
        $antrean = [];

        if (!empty($token)) {
            try {
                $response = Http::withToken($token)->timeout(5)->get($this->gatewayUrl . '/activities/pending');
                if ($response->successful()) $antrean = $response->json('data') ?? [];
            } catch (\Exception $e) {}
        }
        return view('dashboard.petugas', ['page' => 'verifikasi-data-petani', 'antrean' => $antrean]);
    }

    // ---------------------------------------------------------
    // AKSI CRUD SPASIAL (TIDAK ADA YANG DIKURANGI)
    // ---------------------------------------------------------
    public function storeSpasial(Request $request)
    {
        $token = $this->getBearerToken();
        $response = Http::withToken($token)->post($this->gatewayUrl . '/spasial-lahan', $request->all());

        if ($response->successful()) {
            return redirect('/manajemen-data-spasial')->with('success', 'Data poligon lahan sawah baru berhasil dipetakan.');
        }
        return back()->with('error', $response->json('message') ?? 'Gagal menyimpan data spasial lahan.')->withInput();
    }

    public function updateSpasial(Request $request, $id)
    {
        $token = $this->getBearerToken();
        $response = Http::withToken($token)->put($this->gatewayUrl . "/spasial-lahan/$id", $request->all());

        if ($response->successful()) {
            return redirect('/manajemen-data-spasial')->with('success', 'Perubahan spasial lahan berhasil diperbarui.');
        }
        return back()->with('error', $response->json('message') ?? 'Gagal memperbarui data spasial.')->withInput();
    }

    public function destroySpasial($id)
    {
        $token = $this->getBearerToken();
        $response = Http::withToken($token)->delete($this->gatewayUrl . "/spasial-lahan/$id");

        if ($response->successful()) {
            return redirect('/manajemen-data-spasial')->with('success', 'Data spasial lahan berhasil dihapus.');
        }
        return back()->with('error', 'Gagal menghapus data spasial lahan.');
    }
}