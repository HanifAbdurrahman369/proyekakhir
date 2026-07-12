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
        $this->gatewayUrl = rtrim(env('GATEWAY_URL', env('API_GATEWAY_URL', 'http://127.0.0.1:8003')), '/');
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
            $produksi = Http::withHeaders(['Connection' => 'close'])->withoutVerifying()->withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/produksi-pejabat');

            if ($produksi->successful()) {
                $produksiPejabat = $produksi->json('data.produksi_pejabat');
            }

            // Total lahan
            $lahan = Http::withHeaders(['Connection' => 'close'])->withoutVerifying()->withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/total-lahan');

            if ($lahan->successful()) {
                $totalLahan = $lahan->json('data.total_lahan');
            }

            // Produksi per kecamatan
            $produksiKecamatan = [];
            $kecamatanRes = Http::withHeaders(['Connection' => 'close'])->withoutVerifying()->withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/produksi-kecamatan');

            if ($kecamatanRes->successful()) {
                $produksiKecamatan = $kecamatanRes->json('data') ?? [];
            }

            // Top kecamatan
            $top = Http::withHeaders(['Connection' => 'close'])->withoutVerifying()->withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/top-kecamatan');

            if ($top->successful()) {
                $topKecamatan = $top->json('data');
            }

            // Produksi per Kelurahan
            $kelurahan = Http::withHeaders(['Connection' => 'close'])->withoutVerifying()->withToken($token)
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
            'produksiKecamatan',
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

        $kecamatans = [];

        try {
            $response = Http::withHeaders(['Connection' => 'close'])->withoutVerifying()->withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/kecamatan');

            if ($response->successful()) {
                $kecamatans = $response->json('data') ?? [];
            }
        } catch (\Exception $e) {
            report($e);
        }

        return view('partials.sidebar.pejabat.produksi-kecamatan', compact('kecamatans'));
    }

    public function lahanKecamatan()
    {
        $token = session('token');

        $data = [];

        try {

            $response = Http::withHeaders(['Connection' => 'close'])->withoutVerifying()->withToken($token)
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
        $token = $request->query('token') ?? session('token');
        $kecamatanId = $request->query('kecamatan');
        $tahun = $request->query('tahun');

        if (!$token) {
            abort(401, 'Unauthorized: Token tidak ditemukan');
        }
        if (!$kecamatanId) {
            abort(400, 'Bad Request: Kecamatan wajib dipilih');
        }

        $data = [];
        $kecamatan = [];
        $summary = [];

        try {
            $url = $this->gatewayUrl . '/api/statistik/kecamatan/' . urlencode($kecamatanId);
            if ($tahun) {
                $url .= '?tahun=' . urlencode($tahun);
            }

            $response = Http::withoutVerifying()
                ->withToken($token)
                ->acceptJson()
                ->get($url);

            if ($response->failed()) {
                abort(403, 'Akses ditolak atau token tidak valid');
            }

            $resData = $response->json('data') ?? [];
            $data = $resData['rows'] ?? [];
            $kecamatan = $resData['kecamatan'] ?? [];
            $summary = $resData['summary'] ?? [];
        } catch (\Exception $e) {
            report($e);
            abort(500, 'Terjadi kesalahan server internal');
        }

        $pdf = Pdf::loadView('partials.sidebar.pejabat.produksi-kecamatan-historis-pdf', compact('data', 'kecamatan', 'summary', 'tahun'))
            ->setPaper('a4', 'landscape');
        $filename = 'historis-produksi-' . strtolower(str_replace(' ', '-', $kecamatan['nama_kecamatan'] ?? 'kecamatan')) . ($tahun ? '-' . $tahun : '') . '.pdf';
        
        return $pdf->download($filename);
    }

    public function exportLahanPDF(Request $request)
    {
        $token = $request->query('token') ?? session('token');

        if (!$token) {
            abort(401, 'Unauthorized: Token tidak ditemukan');
        }

        $data = [];

        try {
            $response = Http::withoutVerifying()
                ->withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/lahan-kecamatan');

            if ($response->failed()) {
                abort(403, 'Akses ditolak atau token tidak valid');
            }

            $data = $response->json('data') ?? [];
        } catch (\Exception $e) {
            report($e);
            abort(500, 'Terjadi kesalahan server internal');
        }

        $pdf = Pdf::loadView('partials.sidebar.pejabat.lahan-kecamatan-pdf', compact('data'));
        return $pdf->download('rekap-luas-lahan-kecamatan.pdf');
    }

    public function exportDashboardPDF(Request $request)
    {
        $token = $request->query('token') ?? session('token');

        if (!$token) {
            abort(401, 'Unauthorized: Token tidak ditemukan');
        }

        $produksiPejabat = 0;
        $totalLahan = 0;
        $produksiBulanan = array_fill(1, 12, 0);
        $topKecamatan = [];

        try {
            // Total produksi (Sekaligus sebagai otorisasi/verifikasi token)
            $produksi = Http::withoutVerifying()
                ->withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/produksi-pejabat');

            if ($produksi->failed()) {
                abort(403, 'Akses ditolak atau token tidak valid');
            }

            $produksiPejabat = $produksi->json('data.produksi_pejabat');

            // Total lahan
            $lahan = Http::withoutVerifying()
                ->withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/total-lahan');

            if ($lahan->successful()) {
                $totalLahan = $lahan->json('data.total_lahan');
            }

            // Produksi per kecamatan
            $produksiKecamatan = [];
            $kecamatanRes = Http::withoutVerifying()
                ->withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/produksi-kecamatan');

            if ($kecamatanRes->successful()) {
                $produksiKecamatan = $kecamatanRes->json('data') ?? [];
            }

            // Top kecamatan
            $top = Http::withoutVerifying()
                ->withToken($token)
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
            'produksiKecamatan'
        ));
        return $pdf->download('laporan-statistik-eksekutif.pdf');
    }

    public function exportLahanSawahPDF(Request $request)
    {
        $token = $request->query('token') ?? session('token');
        $data = [];

        try {
            $response = Http::withoutVerifying()
                ->withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/produksi-kelurahan');

            if ($response->successful()) {
                $data = $response->json('data') ?? [];
            }
        } catch (\Exception $e) {
            report($e);
        }

        $pdf = Pdf::loadView('partials.sidebar.pejabat.lahan-sawah-pdf', compact('data'));
        return $pdf->download('daftar-lahan-sawah.pdf');
    }

    public function exportLahanSawahExcel(Request $request)
    {
        $token = $request->query('token') ?? session('token');
        $data = [];

        try {
            $response = Http::withoutVerifying()
                ->withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/produksi-kelurahan');

            if ($response->successful()) {
                $data = $response->json('data') ?? [];
            }
        } catch (\Exception $e) {
            report($e);
        }

        return response()->view('partials.sidebar.pejabat.lahan-sawah-excel', compact('data'))
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="daftar-lahan-sawah.xls"');
    }

    public function exportProduksiExcel(Request $request)
    {
        $token = $request->query('token') ?? session('token');
        $kecamatanId = $request->query('kecamatan');
        $tahun = $request->query('tahun');

        if (!$token) {
            abort(401, 'Unauthorized: Token tidak ditemukan');
        }
        if (!$kecamatanId) {
            abort(400, 'Bad Request: Kecamatan wajib dipilih');
        }

        $data = [];
        $kecamatan = [];
        $summary = [];

        try {
            $url = $this->gatewayUrl . '/api/statistik/kecamatan/' . urlencode($kecamatanId);
            if ($tahun) {
                $url .= '?tahun=' . urlencode($tahun);
            }

            $response = Http::withoutVerifying()
                ->withToken($token)
                ->acceptJson()
                ->get($url);

            if ($response->failed()) {
                abort(403, 'Akses ditolak atau token tidak valid');
            }

            $resData = $response->json('data') ?? [];
            $data = $resData['rows'] ?? [];
            $kecamatan = $resData['kecamatan'] ?? [];
            $summary = $resData['summary'] ?? [];
        } catch (\Exception $e) {
            report($e);
            abort(500, 'Terjadi kesalahan server internal');
        }

        $filename = 'historis-produksi-' . strtolower(str_replace(' ', '-', $kecamatan['nama_kecamatan'] ?? 'kecamatan')) . ($tahun ? '-' . $tahun : '') . '.xls';

        return response()->view('partials.sidebar.pejabat.produksi-kecamatan-historis-excel', compact('data', 'kecamatan', 'summary', 'tahun'))
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function exportProduksiKelurahanPDF(Request $request)
    {
        $token = $request->query('token') ?? session('token');
        $kecamatan = $request->query('kecamatan');
        $data = [];

        try {
            $response = Http::withoutVerifying()
                ->withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/produksi-kelurahan');

            if ($response->successful()) {
                $rekap = $response->json('data') ?? [];
                if ($kecamatan) {
                    $data = collect($rekap)->filter(function ($item) use ($kecamatan) {
                        return strtolower($item['nama_kecamatan']) === strtolower($kecamatan);
                    })->sortBy('nama_kelurahan')->values()->all();
                } else {
                    $data = collect($rekap)->sortBy('nama_kecamatan')->values()->all();
                }
            }
        } catch (\Exception $e) {
            report($e);
        }

        $pdf = Pdf::loadView('partials.sidebar.pejabat.produksi-kelurahan-pdf', compact('data', 'kecamatan'))
            ->setPaper('a4', 'landscape');
        return $pdf->download('rekap-produksi-kelurahan-' . ($kecamatan ?: 'semua') . '.pdf');
    }

    public function exportProduksiKelurahanExcel(Request $request)
    {
        $token = $request->query('token') ?? session('token');
        $kecamatan = $request->query('kecamatan');
        $data = [];

        try {
            $response = Http::withoutVerifying()
                ->withToken($token)
                ->acceptJson()
                ->get($this->gatewayUrl . '/api/produksi-kelurahan');

            if ($response->successful()) {
                $rekap = $response->json('data') ?? [];
                if ($kecamatan) {
                    $data = collect($rekap)->filter(function ($item) use ($kecamatan) {
                        return strtolower($item['nama_kecamatan']) === strtolower($kecamatan);
                    })->sortBy('nama_kelurahan')->values()->all();
                } else {
                    $data = collect($rekap)->sortBy('nama_kecamatan')->values()->all();
                }
            }
        } catch (\Exception $e) {
            report($e);
        }

        return response()->view('partials.sidebar.pejabat.produksi-kelurahan-excel', compact('data', 'kecamatan'))
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="rekap-produksi-kelurahan-' . ($kecamatan ?: 'semua') . '.xls"');
    }
}
