<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\SiklusTanam;
use App\Models\LahanSawah;  


class SiklusTanamController extends Controller
{
    /**
     * FORM INPUT AKTIVITAS TANAM
     */
    public function create()
    {
        $token = session('token');

        if (!$token) {
                    return redirect('/login')->with(
                'error',
                'Session login habis, silakan login kembali.'
                    );
        }

        $lahanResponse = Http::acceptJson()
            ->get('http://127.0.0.1:8005/api/lahan');
        $bibitResponse = Http::acceptJson()
        ->get('http://127.0.0.1:8005/api/bibit');

        $bibit = [];

        if ($bibitResponse->successful()) {

            $bibit = $bibitResponse->json()['data'] ?? [];

        }

        $lahan = [];

        if ($lahanResponse->successful()) {

            $lahan = $lahanResponse->json()['data'] ?? [];

        }

        return view('partials.sidebar.input-petani', [

            'bibit' => $bibit,

            'lahan' => $lahan

        ]);
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
            ->post('http://127.0.0.1:8005/api/activities', [
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
}