<?php

namespace App\Http\Controllers;

use App\Models\SiklusTanam;
use App\Models\LahanSawah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatistikController extends Controller
{
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

    public function produksiPejabat(Request $request)
    {
        $this->authorizePejabat($request);

        $latestYear = DB::table('statistik_padi_kecamatan')->max('tahun') ?? date('Y');

        $produksiPejabat = DB::table('statistik_padi_kecamatan')
            ->where('tahun', $latestYear)
            ->sum('produksi_ton');

        return response()->json([
            'success' => true,
            'data' => [
                'produksi_pejabat' => $produksiPejabat
            ]
        ]);
    }

    public function totalLahan(Request $request)
    {
        $this->authorizePejabat($request);

        $totalLahan = LahanSawah::where('status_verifikasi', 'DITERIMA')
            ->sum('luas_lahan_hektar');
        $totalLuasTanam = LahanSawah::where('status_verifikasi', 'DITERIMA')
            ->sum(DB::raw('COALESCE(luas_tanam_hektar, luas_lahan_hektar)'));

        return response()->json([
            'success' => true,
            'data' => [
                'total_lahan' => $totalLahan,
                'total_luas_tanam' => $totalLuasTanam,
            ]
        ]);
    }

    public function produksiPerKecamatan(Request $request)
    {
        $this->authorizePejabat($request);

        $latestYear = DB::table('statistik_padi_kecamatan')->max('tahun') ?? date('Y');

        $data = DB::table('kecamatan')
            ->leftJoin('statistik_padi_kecamatan', function ($join) use ($latestYear) {
                $join->on('kecamatan.id', '=', 'statistik_padi_kecamatan.kecamatan_id')
                    ->where('statistik_padi_kecamatan.tahun', '=', $latestYear);
            })
            ->select(
                'kecamatan.id',
                'kecamatan.nama_kecamatan',
                DB::raw('COALESCE(SUM(statistik_padi_kecamatan.produksi_ton), 0) as produksi_pejabat')
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

    public function produksiKelurahan(Request $request)
    {
        $this->authorizePejabat($request);

        $panenPerLahan = DB::table('panen_padi as pp')
            ->join('tanam_padi as tp', 'tp.id', '=', 'pp.tanam_padi_id')
            ->where('pp.status_verifikasi', 'DITERIMA')
            ->select('tp.lahan_id', DB::raw('SUM(pp.hasil_panen_ton) as total_panen'))
            ->groupBy('tp.lahan_id');

        $lahanData = DB::table('lahan_sawah')
            ->join('kelurahan', 'lahan_sawah.kelurahan_id', '=', 'kelurahan.id')
            ->join('kecamatan', 'kelurahan.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('tipe_lahan', 'lahan_sawah.tipe_lahan_id', '=', 'tipe_lahan.id')
            ->leftJoinSub($panenPerLahan, 'panen_diterima', function ($join) {
                $join->on('panen_diterima.lahan_id', '=', 'lahan_sawah.id');
            })
            ->where('lahan_sawah.status_verifikasi', 'DITERIMA')
            ->select(
                'kecamatan.nama_kecamatan',
                'kelurahan.nama_kelurahan',
                'lahan_sawah.tahun_lbs',
                'lahan_sawah.luas_lahan_hektar',
                DB::raw('COALESCE(panen_diterima.total_panen, 0) as hasil_panen_ton'),
                'tipe_lahan.nama_tipe'
            )
            ->get();

        $grouped = $lahanData->groupBy('nama_kelurahan')->map(function ($items, $kelurahan) {
            $first = $items->first();
            $tipeGroups = $items->groupBy('nama_tipe')->map(function($tipeItems, $tipe) {
                return [
                    'nama_tipe' => $tipe ?? 'Belum Ditentukan',
                    'total_luas' => $tipeItems->sum('luas_lahan_hektar')
                ];
            })->values();

            return [
                'nama_kecamatan' => $first->nama_kecamatan,
                'nama_kelurahan' => $kelurahan,
                'tahun_lbs' => $first->tahun_lbs,
                'jumlah_lahan' => $items->count(),
                'total_luas' => $items->sum('luas_lahan_hektar'),
                'total_panen' => $items->sum('hasil_panen_ton'),
                'produksi_pejabat' => $items->sum('hasil_panen_ton'),
                'rincian_tipe_lahan' => $tipeGroups
            ];
        })->values()->sortByDesc('produksi_pejabat')->values();

        return response()->json([
            'success' => true,
            'data' => $grouped
        ]);
    }

    public function lahanPerKecamatan(Request $request)
    {
        $this->authorizePejabat($request);

        $data = DB::table('kecamatan')
            ->leftJoin('lahan_sawah', function($join) {
                $join->on('kecamatan.id', '=', 'lahan_sawah.kecamatan_id')
                     ->where('lahan_sawah.status_verifikasi', '=', 'DITERIMA');
            })
            ->select(
                'kecamatan.id',
                'kecamatan.nama_kecamatan',
                DB::raw('COALESCE(SUM(lahan_sawah.luas_lahan_hektar), 0) as total_lahan'),
                DB::raw('COALESCE(SUM(COALESCE(lahan_sawah.luas_tanam_hektar, lahan_sawah.luas_lahan_hektar)), 0) as total_luas_tanam')
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

        $latestYear = DB::table('statistik_padi_kecamatan')->max('tahun') ?? date('Y');
        
        $totalProduksiTahunan = DB::table('statistik_padi_kecamatan')
            ->where('tahun', $latestYear)
            ->sum('produksi_ton');
            
        $rataBulanan = $totalProduksiTahunan / 12;

        $hasil = [];

        for ($i = 1; $i <= 12; $i++) {
            $hasil[$i] = (float) number_format($rataBulanan, 2, '.', '');
        }

        return response()->json([
            'success' => true,
            'data' => $hasil
        ]);
    }

    public function topKecamatan(Request $request)
    {
        $this->authorizePejabat($request);

        $latestYear = DB::table('statistik_padi_kecamatan')->max('tahun') ?? date('Y');

        $data = DB::table('kecamatan')
            ->join('statistik_padi_kecamatan', 'kecamatan.id', '=', 'statistik_padi_kecamatan.kecamatan_id')
            ->where('statistik_padi_kecamatan.tahun', $latestYear)
            ->select(
                'kecamatan.nama_kecamatan',
                DB::raw('SUM(statistik_padi_kecamatan.produksi_ton) as produksi_pejabat')
            )
            ->groupBy('kecamatan.nama_kecamatan')
            ->orderByDesc('produksi_pejabat')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
