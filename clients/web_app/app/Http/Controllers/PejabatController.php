<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;


class PejabatController extends Controller
{
 protected string $gatewayUrl;

    public function __construct()
    {
        $this->gatewayUrl = env('GATEWAY_URL');
    }

    public function index(Request $request)
    {
        $token = session('token');

        $produksiPejabat = 0;
        $totalLahan = 0;
        $produksiBulanan = [];
        $topKecamatan = [];
        $produksiKelurahanData = [];
        $totalProduksiKelurahan = 0;

        try {

            // Total produksi
            $produksi = Http::withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/produksi-pejabat');

            if ($produksi->successful()) {
                $produksiPejabat = $produksi->json('data.produksi_pejabat');
            }

            // Total lahan
            $lahan = Http::withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/total-lahan');

            if ($lahan->successful()) {
                $totalLahan = $lahan->json('data.total_lahan');
            }

            // Produksi bulanan
            $bulanan = Http::withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/produksi-bulanan');

            if ($bulanan->successful()) {

                $produksiBulanan = array_fill(1, 12, 0);

                $data = $bulanan->json('data') ?? [];

                foreach ($data as $bulan => $total) {
                    $produksiBulanan[$bulan] = $total;
                }
            }

            // Top kecamatan
            $top = Http::withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/top-kecamatan');

            if ($top->successful()) {
                $topKecamatan = $top->json('data');
            }

            // Produksi per Kelurahan
            $kelurahan = Http::withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/produksi-kelurahan');

            if ($kelurahan->successful()) {
                $produksiKelurahanData = $kelurahan->json('data') ?? [];
                $totalProduksiKelurahan = collect($produksiKelurahanData)->sum('produksi_pejabat');
            }

        } catch (\Exception $e) {
            report($e);
        }

        return view('dashboard.pejabat', compact(
            'produksiPejabat',
            'totalLahan',
            'topKecamatan',
            'produksiBulanan',
            'produksiKelurahanData',
            'totalProduksiKelurahan'
        ));
    }

    /**
     * Detail produksi per kecamatan
     */
    public function produksiKecamatan(Request $request)
    {
        $token = session('token');

        $data = [];

        try {

            $response = Http::withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/produksi-kecamatan');

            if ($response->successful()) {
                $data = $response->json('data');
            }

        } catch (\Exception $e) {
            report($e);
        }

        return view('partials.sidebar.pejabat.produksi-kecamatan', compact('data'));
    }

    public function lahanKecamatan()
    {
        $token = session('token');

        $data = [];

        try {

            $response = Http::withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/lahan-kecamatan');

            if ($response->successful()) {
                $data = $response->json('data');
            }

        } catch (\Exception $e) {
            report($e);
        }

        return view(
            'partials.sidebar.pejabat.lahan-kecamatan',
            compact('data')
        );
    }

    public function exportProduksiPDF(Request $request)
    {
        $token = session('token');
        $data = [];

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/produksi-kecamatan');

            if ($response->successful()) {
                $data = $response->json('data') ?? [];
            }
        } catch (\Exception $e) {
            report($e);
        }

        $pdf = Pdf::loadView('partials.sidebar.pejabat.produksi-kecamatan-pdf', compact('data'));
        return $pdf->download('rekap-produksi-kecamatan.pdf');
    }

    public function exportLahanPDF(Request $request)
    {
        $token = session('token');
        $data = [];

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/lahan-kecamatan');

            if ($response->successful()) {
                $data = $response->json('data') ?? [];
            }
        } catch (\Exception $e) {
            report($e);
        }

        $pdf = Pdf::loadView('partials.sidebar.pejabat.lahan-kecamatan-pdf', compact('data'));
        return $pdf->download('rekap-luas-lahan-kecamatan.pdf');
    }

    public function exportDashboardPDF(Request $request)
    {
        $token = session('token');

        $produksiPejabat = 0;
        $totalLahan = 0;
        $produksiBulanan = array_fill(1, 12, 0);
        $topKecamatan = [];

        try {
            // Total produksi
            $produksi = Http::withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/produksi-pejabat');

            if ($produksi->successful()) {
                $produksiPejabat = $produksi->json('data.produksi_pejabat');
            }

            // Total lahan
            $lahan = Http::withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/total-lahan');

            if ($lahan->successful()) {
                $totalLahan = $lahan->json('data.total_lahan');
            }

            // Produksi bulanan
            $bulanan = Http::withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/produksi-bulanan');

            if ($bulanan->successful()) {
                $data = $bulanan->json('data') ?? [];
                foreach ($data as $bulan => $total) {
                    $produksiBulanan[$bulan] = $total;
                }
            }

            // Top kecamatan
            $top = Http::withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/top-kecamatan');

            if ($top->successful()) {
                $topKecamatan = $top->json('data') ?? [];
            }

        } catch (\Exception $e) {
            report($e);
        }

        $pdf = Pdf::loadView('dashboard.pejabat-pdf', compact(
            'produksiPejabat',
            'totalLahan',
            'topKecamatan',
            'produksiBulanan'
        ));
        return $pdf->download('laporan-statistik-eksekutif.pdf');
    }
}
