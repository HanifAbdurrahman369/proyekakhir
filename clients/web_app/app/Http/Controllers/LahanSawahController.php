<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\SiklusTanam;
use App\Models\LahanSawah;  


class LahanSawahController extends Controller
{
    protected function gatewayUrl(): string
    {
        return env('GATEWAY_URL', 'http://127.0.0.1:8003');
    }

    private function referensi(): array
    {
        $kecamatan = Http::get(
            $this->gatewayUrl() . '/api/kecamatan'
        )->json()['data'] ?? [];

        $kelurahan = Http::get(
            $this->gatewayUrl() . '/api/kelurahan'
        )->json()['data'] ?? [];

        $tipeLahan = Http::get(
            $this->gatewayUrl() . '/api/tipe-lahan'
        )->json()['data'] ?? [];

        $spasialReferensi = Http::get(
            $this->gatewayUrl() . '/api/spasial-lahan/referensi'
        )->json()['data'] ?? [];

        $petani = $spasialReferensi['petani'] ?? [];

        return compact('kecamatan', 'kelurahan', 'tipeLahan', 'petani');
    }

    public function create()
    {
        ['kecamatan' => $kecamatan, 'kelurahan' => $kelurahan, 'tipeLahan' => $tipeLahan, 'petani' => $petani] = $this->referensi();

        return view(
            'partials.sidebar.petani.tambah-lahan',
            compact('kecamatan', 'kelurahan', 'tipeLahan', 'petani')
        );
    }

    public function edit($id)
    {
        $token = session('token');

        if (!$token) {
            return redirect('/login')->with('error', 'Login dulu');
        }

        ['kecamatan' => $kecamatan, 'kelurahan' => $kelurahan, 'tipeLahan' => $tipeLahan, 'petani' => $petani] = $this->referensi();

        $response = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/lahan/' . $id);

        if (!$response->successful()) {
            return redirect('/dashboard-petani')->with('error', $response->json('message') ?? 'Data lahan tidak ditemukan');
        }

        $editLahan = $response->json('data') ?? [];

        if (($editLahan['status_verifikasi'] ?? '') !== 'DITOLAK') {
            return redirect('/dashboard-petani')->with('error', 'Hanya pengajuan ditolak yang dapat diperbaiki.');
        }

        return view(
            'partials.sidebar.petani.tambah-lahan',
            compact('editLahan', 'kecamatan', 'kelurahan', 'tipeLahan', 'petani')
        );
    }

    /**
     * FORM INPUT AKTIVITAS TANAM
     */
    public function storeLahan(Request $request)
    {
        $token = session('token');

        if (!$token) {
            return redirect('/login')->with('error', 'Login dulu');
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($this->gatewayUrl() . '/api/lahan', [
                'nama_lahan' => $request->nama_lahan,
                'kecamatan_id' => $request->kecamatan_id,
                'kelurahan_id' => $request->kelurahan_id,
                'tipe_lahan_id' => $request->tipe_lahan_id,
                'alamat_detail' => $request->alamat_detail,
                'luas_lahan_hektar' => $request->luas_lahan_hektar,
                'petani_id' => $request->petani_id,
            ]);

        if ($response->successful()) {
            session()->forget('total_lahan');
            return back()->with('success', 'Lahan berhasil diajukan');
        }

        return back()->with('error', 'Gagal mengirim data');
    }

    public function resubmitLahan(Request $request, $id)
    {
        $token = session('token');

        if (!$token) {
            return redirect('/login')->with('error', 'Login dulu');
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->put($this->gatewayUrl() . '/api/lahan/' . $id . '/resubmit', [
                'nama_lahan' => $request->nama_lahan,
                'kecamatan_id' => $request->kecamatan_id,
                'kelurahan_id' => $request->kelurahan_id,
                'tipe_lahan_id' => $request->tipe_lahan_id,
                'alamat_detail' => $request->alamat_detail,
                'luas_lahan_hektar' => $request->luas_lahan_hektar,
                'petani_id' => $request->petani_id,
            ]);

        if ($response->successful()) {
            session()->forget('total_lahan');
            return redirect('/dashboard-petani')->with('success', 'Pengajuan lahan berhasil diperbaiki dan dikirim ulang.');
        }

        return back()
            ->withInput()
            ->with('error', $response->json('message') ?? 'Gagal mengajukan ulang lahan.');
    }

    public function destroyLahan($id)
    {
        $response = Http::withToken(session('token'))
            ->acceptJson()
            ->delete($this->gatewayUrl() . '/api/lahan/' . $id);

        if ($response->successful()) {
            session()->forget('total_lahan');
        }

        return redirect('/dashboard-petani')->with(
            $response->successful() ? 'success' : 'error',
            $response->json('message') ?? ($response->successful() ? 'Pengajuan lahan dihapus.' : 'Pengajuan lahan gagal dihapus.')
        );
    }

}
