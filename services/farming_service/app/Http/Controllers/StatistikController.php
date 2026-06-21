<?php

namespace App\Http\Controllers;

use App\Models\LaporPanen;
use App\Models\LahanSawah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatistikController extends Controller
{
    /**
     * Validasi hanya role Pejabat
     */
    private function authorizePejabat(Request $request)
    {
        $user = $request->attributes->get('auth');

        if (!$user || (int) $user->role_id !== 3) {
            abort(
                response()->json([
                    'success' => false,
                    'message' => 'Akses hanya untuk Pejabat'
                ], 403)
            );
        }

        return $user;
    }

    /**
     * Total produksi seluruh kabupaten
     */
    public function produksiPejabat(Request $request)
    {
        $this->authorizePejabat($request);

        $produksiPejabat = LaporPanen::where('status_verifikasi', 'DITERIMA')
            ->whereDate('tanggal_panen', '<=', now()->toDateString())
            ->sum('hasil_panen_ton');

        return response()->json([
            'success' => true,
            'data' => [
                'produksi_pejabat' => $produksiPejabat
            ]
        ]);
    }

    /**
     * Total luas lahan aktif
     */
    public function totalLahan(Request $request)
    {
        $this->authorizePejabat($request);

        $totalLahan = LahanSawah::where('status_verifikasi', 'DITERIMA')
            ->sum('luas_lahan_hektar');

        return response()->json([
            'success' => true,
            'data' => [
                'total_lahan' => $totalLahan
            ]
        ]);
    }

    /**
     * Produksi per kecamatan
     */
    public function produksiPerKecamatan(Request $request)
    {
        $this->authorizePejabat($request);

        $data = DB::table('panen_padi')
            ->join('lahan_sawah', 'panen_padi.lahan_id', '=', 'lahan_sawah.id')
            ->join('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->where('panen_padi.status_verifikasi', 'DITERIMA')
            ->whereDate('panen_padi.tanggal_panen', '<=', now()->toDateString())
            ->select(
                'kecamatan.id',
                'kecamatan.nama_kecamatan',
                DB::raw('SUM(panen_padi.hasil_panen_ton) as produksi_pejabat')
            )
            ->groupBy(
                'kecamatan.id',
                'kecamatan.nama_kecamatan'
            )
            ->orderByDesc('produksi_pejabat')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Luas lahan per kecamatan
     */
    public function lahanPerKecamatan(Request $request)
    {
        $this->authorizePejabat($request);

        $data = DB::table('lahan_sawah')
            ->join(
                'kecamatan',
                'lahan_sawah.kecamatan_id',
                '=',
                'kecamatan.id'
            )
            ->where('lahan_sawah.status_verifikasi', 'DITERIMA')
            ->select(
                'kecamatan.id',
                'kecamatan.nama_kecamatan',
                DB::raw('SUM(lahan_sawah.luas_lahan_hektar) as total_lahan')
            )
            ->groupBy(
                'kecamatan.id',
                'kecamatan.nama_kecamatan'
            )
            ->orderByDesc('total_lahan')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function produksiBulanan(Request $request)
    {
        $this->authorizePejabat($request);

        $data = DB::table('panen_padi')
            ->selectRaw("
                MONTH(tanggal_panen) as bulan,
                SUM(hasil_panen_ton) as total_produksi
            ")
            ->whereNotNull('tanggal_panen')
            ->where('status_verifikasi', 'DITERIMA')
            ->whereDate('tanggal_panen', '<=', now()->toDateString())
            ->groupBy(DB::raw('MONTH(tanggal_panen)'))
            ->orderBy('bulan')
            ->get();

        $hasil = [];

        for ($i = 1; $i <= 12; $i++) {
            $hasil[$i] = 0;
        }

        foreach ($data as $item) {
            $hasil[$item->bulan] = (float) $item->total_produksi;
        }

        return response()->json([
            'success' => true,
            'data' => $hasil
        ]);
    }

    /**
     * Top 5 kecamatan produksi tertinggi
     */
    public function topKecamatan(Request $request)
    {
        $this->authorizePejabat($request);

        $data = DB::table('panen_padi')
            ->join('lahan_sawah', 'panen_padi.lahan_id', '=', 'lahan_sawah.id')
            ->join('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->where('panen_padi.status_verifikasi', 'DITERIMA')
            ->whereDate('panen_padi.tanggal_panen', '<=', now()->toDateString())
            ->select(
                'kecamatan.nama_kecamatan',
                DB::raw('SUM(panen_padi.hasil_panen_ton) as produksi_pejabat')
            )
            ->groupBy('kecamatan.nama_kecamatan')
            ->orderByDesc('produksi_pejabat')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
