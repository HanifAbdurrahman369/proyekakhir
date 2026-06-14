<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\SiklusTanam;
use App\Models\LahanSawah;  


class SiklusTanamController extends Controller
{
    protected function gatewayUrl(): string
    {
        return env('GATEWAY_URL', 'http://127.0.0.1:8003');
    }

    /**
     * FORM INPUT AKTIVITAS TANAM
     */
    public function create()
    {
        $token = session('token');

        if (!$token) {
            return redirect('/login')
                ->with('error', 'Session login habis, silakan login kembali.');
        }

        $lahanResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/lahan/dropdown');

        $bibitResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/bibit');

        $pupukResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/jenis-pupuk');

        $siklusResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/my-siklus-tanam');

        $lahan = [];
        $bibit = [];
        $pupuk = [];
        $siklusTanam = [];

        if ($lahanResponse->successful()) {
            $lahan = $lahanResponse->json()['data'] ?? [];
        }

        if ($bibitResponse->successful()) {
            $bibit = $bibitResponse->json()['data'] ?? [];
        }

        if ($pupukResponse->successful()) {
            $pupuk = $pupukResponse->json()['data'] ?? [];
        }

        if ($siklusResponse->successful()) {
            $siklusTanam = $siklusResponse->json()['data'] ?? [];
        }

        return view('partials.sidebar.input-petani', compact(
            'lahan',
            'bibit',
            'pupuk',
            'siklusTanam'
        ));
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'lahan_id' => 'required',
            'bibit_id' => 'required',
            'tanggal_tanam' => 'required',
            'estimasi_panen' => 'required',
            'tanggal_panen' => 'required',
            'hasil_panen' => 'required',
        ]);

       $token = session('token'); 
        // atau Auth guard custom jika Anda simpan di user model

        if (!$token) {
            return redirect()
                ->back()
                ->with('error', 'Token tidak ditemukan. Silakan login ulang.');
        }

        /**
         * KIRIM KE MICROSERVICE DENGAN BEARER TOKEN
         */
        $response = Http::withToken($token)
            ->post($this->gatewayUrl() . '/api/activities', [
                'lahan_id' => $request->lahan_id,
                'bibit_id' => $request->bibit_id,
                'tanggal_tanam' => $request->tanggal_tanam,
                'estimasi_panen' => $request->estimasi_panen,
                'tanggal_panen' => $request->tanggal_panen,
                'hasil_panen' => $request->hasil_panen,
            ]);


        if ($response->successful()) {

            return redirect()
                ->back()
                ->with('success', 'Aktivitas tanam berhasil disimpan');
        }

        return redirect()
            ->back()
            ->with('error', 'Gagal menyimpan data aktivitas tanam');
    }

    public function riwayatPanen(Request $request)
    {
        $token = session('token');

        if (!$token) {
            return redirect('/login')->with(
                'error',
                'Silakan login terlebih dahulu'
            );
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/riwayat-panen', [
                'per_page' => 3,
                'riwayat_page' => $request->query('page', 1)
            ]);

        $riwayat = [];
        if ($response->successful()) {
            $riwayat = $response->json()['data'] ?? [];
        } else {
            session()->flash('error', 'Gagal mengambil data riwayat panen.');
        }

        $pupukResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/siklus-pupuk', [
                'per_page' => 3,
                'pupuk_page' => $request->query('pupuk_page', 1)
            ]);

        $riwayatPupuk = [];
        if ($pupukResponse->successful()) {
            $riwayatPupuk = $pupukResponse->json()['data'] ?? [];
        }

        return view('partials.sidebar.riwayat-panen', compact('riwayat', 'riwayatPupuk'));
    }

    public function edit($id)
    {
        $token = session('token');

        if (!$token) {
            return redirect('/login')
                ->with('error', 'Session login habis, silakan login kembali.');
        }

        $lahanResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/lahan/dropdown');

        $bibitResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/bibit');

        $detailResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/activities/' . $id);

        if (!$detailResponse->successful()) {
            return redirect()->route('riwayat.panen')
                ->with('error', 'Data laporan panen tidak ditemukan.');
        }

        $editPanen = $detailResponse->json()['data'] ?? [];

        if (($editPanen['status_verifikasi'] ?? '') !== 'DITOLAK') {
            return redirect()->route('riwayat.panen')
                ->with('error', 'Hanya data pengajuan yang ditolak yang dapat diperbaiki.');
        }

        $lahan = [];
        $bibit = [];

        if ($lahanResponse->successful()) {
            $lahan = $lahanResponse->json()['data'] ?? [];
        }

        if ($bibitResponse->successful()) {
            $bibit = $bibitResponse->json()['data'] ?? [];
        }

        // Format dates to Y-m-d for date picker compatibility
        if (!empty($editPanen['tanggal_tanam'])) {
            $editPanen['tanggal_tanam'] = \Carbon\Carbon::parse($editPanen['tanggal_tanam'])->format('Y-m-d');
        }
        if (!empty($editPanen['tanggal_panen'])) {
            $editPanen['tanggal_panen'] = \Carbon\Carbon::parse($editPanen['tanggal_panen'])->format('Y-m-d');
        }

        return view('partials.sidebar.input-petani', compact(
            'lahan',
            'bibit',
            'editPanen'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'lahan_id' => 'required',
            'bibit_id' => 'required',
            'tanggal_tanam' => 'required',
            'estimasi_panen' => 'required',
            'tanggal_panen' => 'required',
            'hasil_panen' => 'required',
        ]);

        $token = session('token');

        if (!$token) {
            return redirect()
                ->back()
                ->with('error', 'Token tidak ditemukan. Silakan login ulang.');
        }

        $response = Http::withToken($token)
            ->put($this->gatewayUrl() . '/api/activities/' . $id, [
                'lahan_id' => $request->lahan_id,
                'bibit_id' => $request->bibit_id,
                'tanggal_tanam' => $request->tanggal_tanam,
                'estimasi_panen' => $request->estimasi_panen,
                'tanggal_panen' => $request->tanggal_panen,
                'hasil_panen' => $request->hasil_panen,
            ]);

        if ($response->successful()) {
            return redirect()
                ->route('riwayat.panen')
                ->with('success', 'Data aktivitas tanam berhasil diperbaiki dan diajukan ulang.');
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', $response->json('message') ?? 'Gagal memperbarui data aktivitas tanam');
    }

    public function storePemupukan(Request $request)
    {
        $request->validate([
            'siklus_tanam_id' => 'required|integer',
            'pupuk_id' => 'required|integer',
            'tanggal_pemupukan' => 'required|date',
            'takaran' => 'required|numeric|min:0.01',
        ]);

        $token = session('token');

        if (!$token) {
            return redirect()
                ->back()
                ->with('error', 'Token tidak ditemukan. Silakan login ulang.');
        }

        $response = Http::withToken($token)
            ->post($this->gatewayUrl() . '/api/siklus-pupuk', [
                'siklus_tanam_id' => $request->siklus_tanam_id,
                'pupuk_id' => $request->pupuk_id,
                'tanggal_pemupukan' => $request->tanggal_pemupukan,
                'takaran' => $request->takaran,
            ]);

        if ($response->successful()) {
            return redirect()
                ->back()
                ->with('success', 'Catatan pemupukan berhasil disimpan.');
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', $response->json('message') ?? 'Gagal menyimpan catatan pemupukan.');
    }
}