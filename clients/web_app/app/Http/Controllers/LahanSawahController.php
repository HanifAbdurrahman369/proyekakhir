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

    public function create()
    {
        $kecamatan = Http::get(
            $this->gatewayUrl() . '/api/kecamatan'
        )->json()['data'] ?? [];

        $kelurahan = Http::get(
            $this->gatewayUrl() . '/api/kelurahan'
        )->json()['data'] ?? [];

        return view(
            'partials.sidebar.tambah-lahan',
            compact('kecamatan', 'kelurahan')
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
            ->post($this->gatewayUrl() . '/api/lahan', [
                'nama_lahan' => $request->nama_lahan,
                'kecamatan_id' => $request->kecamatan_id,
                'kelurahan_id' => $request->kelurahan_id,
                'alamat_detail' => $request->alamat_detail,
            ]);

        if ($response->successful()) {
            return back()->with('success', 'Lahan berhasil diajukan');
        }

        return back()->with('error', 'Gagal mengirim data');
    }

}