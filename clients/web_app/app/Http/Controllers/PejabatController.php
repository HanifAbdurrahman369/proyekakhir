<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class PejabatController extends Controller
{
 protected string $gatewayUrl;

    public function __construct()
    {
        $this->gatewayUrl = rtrim(env('GATEWAY_URL', env('API_GATEWAY_URL', 'http://127.0.0.1:8003')), '/');
    }

    private function apiData(Request $request, string $endpoint, array $query = []): array
    {
        $token = session('token');
        if (!$token) {
            abort(401, 'Sesi login telah berakhir. Silakan login kembali.');
        }

        try {
            $response = Http::withHeaders(['Connection' => 'close'])
                ->withoutVerifying()
                ->withToken($token)
                ->acceptJson()
                ->timeout(30)
                ->connectTimeout(5)
                ->get($this->gatewayUrl . '/api/' . ltrim($endpoint, '/'), $query);
        } catch (\Throwable $e) {
            report($e);
            abort(502, 'Backend laporan belum dapat dihubungi. Silakan coba kembali.');
        }

        if ($response->status() === 401 || $response->status() === 403) {
            abort(403, 'Akses ditolak atau sesi login sudah tidak valid.');
        }

        if (!$response->successful()) {
            abort(502, $response->json('message') ?? 'Backend gagal menyiapkan data laporan.');
        }

        $data = $response->json('data');
        if (!is_array($data)) {
            abort(502, 'Format data laporan dari backend tidak valid.');
        }

        return $data;
    }

    private function historicalData(Request $request): array
    {
        $validated = $request->validate([
            'kecamatan' => ['required', 'integer', 'min:1'],
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
        ]);

        $payload = $this->apiData(
            $request,
            '/statistik/kecamatan/' . $validated['kecamatan'],
            array_filter(['tahun' => $validated['tahun'] ?? null])
        );

        return [
            'data' => is_array($payload['rows'] ?? null) ? $payload['rows'] : [],
            'kecamatan' => is_array($payload['kecamatan'] ?? null) ? $payload['kecamatan'] : [],
            'summary' => is_array($payload['summary'] ?? null) ? $payload['summary'] : [],
            'tahun' => $validated['tahun'] ?? null,
        ];
    }

    private function acceptedLahanData(Request $request): array
    {
        return collect($this->apiData($request, '/lahan/accepted'))
            ->map(fn ($item) => [
                'nama_lahan' => $item['nama_lahan'] ?? '-',
                'nama_kecamatan' => $item['nama_kecamatan'] ?? data_get($item, 'kecamatan_lahan.nama_kecamatan', '-'),
                'nama_kelurahan' => $item['nama_kelurahan'] ?? data_get($item, 'kelurahan_lahan.nama_kelurahan', '-'),
                'pemilik_nama' => $item['pemilik_lahan'] ?? data_get($item, 'pemilik.nama_lengkap', '-'),
                'tipe_lahan' => $item['nama_tipe_lahan'] ?? data_get($item, 'tipe_lahan.nama_tipe', '-'),
                'luas' => (float) ($item['luas_lahan_hektar'] ?? 0),
            ])
            ->sortBy(['nama_kecamatan', 'nama_kelurahan', 'nama_lahan'])
            ->values()
            ->all();
    }

    private function safeFilenamePart(?string $value, string $fallback): string
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?: '';
        return trim($value, '-') ?: $fallback;
    }

    private function excelDownload(
        string $title,
        array $headers,
        array $rows,
        string $filename,
        array $metadata = []
    ) {
        return response()->streamDownload(function () use ($title, $headers, $rows, $metadata) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Laporan');

            $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
            $sheet->setCellValue('A1', $title);
            $sheet->mergeCells("A1:{$lastColumn}1");
            $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => '064E3B']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            $rowNumber = 3;
            foreach ($metadata as $label => $value) {
                $sheet->setCellValue("A{$rowNumber}", $label);
                $sheet->setCellValue("B{$rowNumber}", $value);
                $sheet->getStyle("A{$rowNumber}")->getFont()->setBold(true);
                $rowNumber++;
            }

            $headerRow = $rowNumber;
            $sheet->fromArray($headers, null, "A{$headerRow}");
            if ($rows !== []) {
                // Strict null comparison wajib agar angka nol tetap ditulis,
                // terutama pada hasil panen dan produktivitas bernilai 0.
                $sheet->fromArray($rows, null, 'A' . ($headerRow + 1), true);
            }

            $lastRow = max($headerRow, $headerRow + count($rows));
            $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '047857']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ]);
            $sheet->getRowDimension($headerRow)->setRowHeight(32);

            if ($lastRow > $headerRow) {
                $sheet->getStyle("A{$headerRow}:{$lastColumn}{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1FAE5']]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                ]);
            }

            foreach (range(1, count($headers)) as $columnIndex) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
            }
            $sheet->freezePane('A' . ($headerRow + 1));
            $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$lastRow}");
            $sheet->getPageSetup()->setFitToWidth(1)->setFitToHeight(0);
            $sheet->getPageMargins()->setTop(0.5)->setRight(0.35)->setBottom(0.5)->setLeft(0.35);

            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
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
        ['data' => $data, 'kecamatan' => $kecamatan, 'summary' => $summary, 'tahun' => $tahun]
            = $this->historicalData($request);

        $pdf = Pdf::loadView('partials.sidebar.pejabat.produksi-kecamatan-historis-pdf', compact('data', 'kecamatan', 'summary', 'tahun'))
            ->setPaper('a4', 'landscape');
        $filename = 'historis-produksi-' . $this->safeFilenamePart($kecamatan['nama_kecamatan'] ?? null, 'kecamatan') . ($tahun ? '-' . $tahun : '') . '.pdf';
        
        return $pdf->download($filename);
    }

    public function exportLahanPDF(Request $request)
    {
        $data = $this->apiData($request, '/lahan-kecamatan');

        $pdf = Pdf::loadView('partials.sidebar.pejabat.lahan-kecamatan-pdf', compact('data'));
        return $pdf->download('rekap-luas-lahan-kecamatan.pdf');
    }

    public function exportDashboardPDF(Request $request)
    {
        $produksiPejabat = (float) data_get($this->apiData($request, '/produksi-pejabat'), 'produksi_pejabat', 0);
        $totalLahan = (float) data_get($this->apiData($request, '/total-lahan'), 'total_lahan', 0);
        $produksiKecamatan = $this->apiData($request, '/produksi-kecamatan');
        $topKecamatan = $this->apiData($request, '/top-kecamatan');

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
        $data = $this->acceptedLahanData($request);

        $pdf = Pdf::loadView('partials.sidebar.pejabat.lahan-sawah-pdf', compact('data'));
        return $pdf->download('daftar-lahan-sawah.pdf');
    }

    public function exportLahanSawahExcel(Request $request)
    {
        $data = $this->acceptedLahanData($request);
        $rows = collect($data)->values()->map(fn ($item, $index) => [
            $index + 1,
            $item['nama_lahan'],
            $item['nama_kecamatan'],
            $item['nama_kelurahan'],
            $item['pemilik_nama'],
            $item['tipe_lahan'],
            (float) $item['luas'],
        ])->all();

        return $this->excelDownload(
            'LAPORAN DAFTAR LAHAN SAWAH TERVERIFIKASI - KABUPATEN BARITO KUALA',
            ['No', 'Nama Lahan Sawah', 'Kecamatan', 'Kelurahan/Desa', 'Pemilik', 'Tipe Lahan', 'Luas Lahan (Ha)'],
            $rows,
            'daftar-lahan-sawah.xlsx',
            ['Tanggal Cetak' => now()->translatedFormat('d F Y H:i')]
        );
    }

    public function exportProduksiExcel(Request $request)
    {
        ['data' => $data, 'kecamatan' => $kecamatan, 'summary' => $summary, 'tahun' => $tahun]
            = $this->historicalData($request);

        $filename = 'historis-produksi-' . $this->safeFilenamePart($kecamatan['nama_kecamatan'] ?? null, 'kecamatan') . ($tahun ? '-' . $tahun : '') . '.xlsx';
        $rows = collect($data)->values()->map(fn ($row, $index) => [
            $index + 1,
            (int) ($row['tahun'] ?? 0),
            (float) ($row['luas_tanam_ha'] ?? 0),
            (float) ($row['luas_panen_ha'] ?? 0),
            (float) ($row['produktivitas_ton_ha'] ?? 0),
            (float) ($row['produksi_ton'] ?? 0),
            $row['status_data'] ?? '-',
            $row['sumber_data'] ?? '-',
        ])->all();

        return $this->excelDownload(
            'LAPORAN HISTORIS PRODUKSI KECAMATAN',
            ['No', 'Tahun', 'Luas Tanam (Ha)', 'Luas Panen (Ha)', 'Produktivitas (Ton/Ha)', 'Produksi (Ton)', 'Status', 'Sumber'],
            $rows,
            $filename,
            [
                'Kecamatan' => $kecamatan['nama_kecamatan'] ?? '-',
                'Tahun' => $tahun ?: 'Semua (2010-2025)',
                'Total Luas Tanam (Ha)' => (float) ($summary['total_luas_tanam_ha'] ?? 0),
                'Total Produksi (Ton)' => (float) ($summary['total_produksi_ton'] ?? 0),
                'Rata-rata Produktivitas (Ton/Ha)' => (float) ($summary['rata_produktivitas_ton_ha'] ?? 0),
                'Jumlah Tahun' => (int) ($summary['jumlah_tahun'] ?? 0),
            ]
        );
    }

    public function exportProduksiKelurahanPDF(Request $request)
    {
        $kecamatan = $request->query('kecamatan');
        $rekap = $this->apiData($request, '/produksi-kelurahan');
        $data = collect($rekap)
            ->when($kecamatan, fn ($rows) => $rows->filter(
                fn ($item) => mb_strtolower(trim((string) ($item['nama_kecamatan'] ?? '')))
                    === mb_strtolower(trim((string) $kecamatan))
            ))
            ->sortBy([['nama_kecamatan', 'asc'], ['nama_kelurahan', 'asc']])
            ->values()
            ->all();

        $pdf = Pdf::loadView('partials.sidebar.pejabat.produksi-kelurahan-pdf', compact('data', 'kecamatan'))
            ->setPaper('a4', 'landscape');
        return $pdf->download('rekap-produksi-kelurahan-' . $this->safeFilenamePart($kecamatan, 'semua') . '.pdf');
    }

    public function exportProduksiKelurahanExcel(Request $request)
    {
        $kecamatan = $request->query('kecamatan');
        $rekap = $this->apiData($request, '/produksi-kelurahan');
        $data = collect($rekap)
            ->when($kecamatan, fn ($rows) => $rows->filter(
                fn ($item) => mb_strtolower(trim((string) ($item['nama_kecamatan'] ?? '')))
                    === mb_strtolower(trim((string) $kecamatan))
            ))
            ->sortBy([['nama_kecamatan', 'asc'], ['nama_kelurahan', 'asc']])
            ->values()
            ->all();

        $rows = collect($data)->values()->map(function ($item, $index) {
            $totalLuas = (float) ($item['total_luas'] ?? 0);
            $totalPanen = (float) ($item['total_panen'] ?? 0);
            $rincian = collect(is_array($item['rincian_tipe_lahan'] ?? null) ? $item['rincian_tipe_lahan'] : [])
                ->filter(fn ($tipe) => (float) ($tipe['total_luas'] ?? 0) > 0)
                ->map(fn ($tipe) => ($tipe['nama_tipe'] ?? 'Belum Ditentukan') . ': ' . number_format((float) $tipe['total_luas'], 2) . ' Ha')
                ->implode(', ');

            return [
                $index + 1,
                $item['nama_kecamatan'] ?? '-',
                $item['nama_kelurahan'] ?? '-',
                (int) ($item['tahun_lbs'] ?? 0),
                (int) ($item['jumlah_lahan'] ?? 0),
                $totalLuas,
                $rincian ?: '-',
                $totalPanen,
                $totalLuas > 0 ? round($totalPanen / $totalLuas, 2) : 0,
            ];
        })->all();

        return $this->excelDownload(
            'LAPORAN REKAP HASIL PANEN DESA/KELURAHAN - KABUPATEN BARITO KUALA',
            ['No', 'Kecamatan', 'Kelurahan/Desa', 'Tahun LBS', 'Jumlah Lahan', 'Total Luas (Ha)', 'Rincian Per Tipe (Ha)', 'Hasil Panen (Ton)', 'Produktivitas (Ton/Ha)'],
            $rows,
            'rekap-produksi-kelurahan-' . $this->safeFilenamePart($kecamatan, 'semua') . '.xlsx',
            [
                'Kecamatan' => $kecamatan ?: 'Semua Kecamatan',
                'Tanggal Cetak' => now()->translatedFormat('d F Y H:i'),
            ]
        );
    }
}
