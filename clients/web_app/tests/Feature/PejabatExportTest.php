<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class PejabatExportTest extends TestCase
{
    private function spreadsheetText(TestResponse $response): string
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'sipetani-xlsx-');
        file_put_contents($temporaryFile, $response->streamedContent());

        try {
            $rows = IOFactory::load($temporaryFile)->getActiveSheet()->toArray();
            return collect($rows)->flatten()->filter(fn ($value) => $value !== null && $value !== '')->implode('|');
        } finally {
            @unlink($temporaryFile);
        }
    }

    public function test_export_routes_are_restricted_to_pejabat(): void
    {
        $this->withSession(['role_id' => 1, 'token' => 'farmer-token'])
            ->get('/pejabat/produksi-kecamatan/excel?kecamatan=1')
            ->assertForbidden();
    }

    public function test_historical_excel_uses_current_api_field_names(): void
    {
        Http::fake([
            '*/api/statistik/kecamatan/1*' => Http::response([
                'success' => true,
                'data' => [
                    'kecamatan' => ['id' => 1, 'nama_kecamatan' => 'Alalak'],
                    'summary' => [
                        'total_luas_tanam_ha' => 125.5,
                        'total_produksi_ton' => 640.25,
                        'rata_produktivitas_ton_ha' => 5.102,
                        'jumlah_tahun' => 1,
                    ],
                    'rows' => [[
                        'tahun' => 2025,
                        'luas_tanam_ha' => 125.5,
                        'luas_panen_ha' => 120,
                        'produktivitas_ton_ha' => 5.102,
                        'produksi_ton' => 640.25,
                        'status_data' => 'Tetap',
                        'sumber_data' => 'BPS',
                    ]],
                ],
            ]),
        ]);

        $response = $this->withSession(['role_id' => 3, 'token' => 'official-token'])
            ->get('/pejabat/produksi-kecamatan/excel?kecamatan=1&tahun=2025');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $sheetText = $this->spreadsheetText($response);
        $this->assertStringContainsString('2025', $sheetText);
        $this->assertStringContainsString('640.25', $sheetText);
        $this->assertStringContainsString('BPS', $sheetText);
    }

    public function test_verified_land_excel_uses_land_endpoint_and_flattens_relations(): void
    {
        Http::fake([
            '*/api/lahan/accepted*' => Http::response([
                'success' => true,
                'data' => [[
                    'nama_lahan' => 'Sawah Maju',
                    'luas_lahan_hektar' => 2.75,
                    'pemilik_lahan' => 'Siti Aminah',
                    'kecamatan_lahan' => ['nama_kecamatan' => 'Alalak'],
                    'kelurahan_lahan' => ['nama_kelurahan' => 'Berangas'],
                    'tipe_lahan' => ['nama_tipe' => 'Pasang Surut'],
                ]],
            ]),
        ]);

        $response = $this->withSession(['role_id' => 3, 'token' => 'official-token'])
            ->get('/pejabat/lahan-sawah/excel');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $sheetText = $this->spreadsheetText($response);
        $this->assertStringContainsString('Sawah Maju', $sheetText);
        $this->assertStringContainsString('Siti Aminah', $sheetText);
        $this->assertStringContainsString('Pasang Surut', $sheetText);

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/api/lahan/accepted'));
    }

    public function test_all_pdf_exports_render_non_empty_documents(): void
    {
        Http::fake([
            '*/api/produksi-pejabat*' => Http::response(['success' => true, 'data' => ['produksi_pejabat' => 640.25]]),
            '*/api/total-lahan*' => Http::response(['success' => true, 'data' => ['total_lahan' => 125.5]]),
            '*/api/produksi-kecamatan*' => Http::response(['success' => true, 'data' => [[
                'nama_kecamatan' => 'Alalak', 'produksi_pejabat' => 640.25,
            ]]]),
            '*/api/top-kecamatan*' => Http::response(['success' => true, 'data' => [[
                'nama_kecamatan' => 'Alalak', 'produksi_pejabat' => 640.25,
            ]]]),
            '*/api/lahan-kecamatan*' => Http::response(['success' => true, 'data' => [[
                'nama_kecamatan' => 'Alalak', 'total_lahan' => 125.5,
            ]]]),
            '*/api/lahan/accepted*' => Http::response(['success' => true, 'data' => [[
                'nama_lahan' => 'Sawah Maju',
                'luas_lahan_hektar' => 2.75,
                'pemilik_lahan' => 'Siti Aminah',
                'nama_kecamatan' => 'Alalak',
                'nama_kelurahan' => 'Berangas',
                'nama_tipe_lahan' => 'Pasang Surut',
            ]]]),
            '*/api/produksi-kelurahan*' => Http::response(['success' => true, 'data' => [[
                'nama_kecamatan' => 'Alalak',
                'nama_kelurahan' => 'Berangas',
                'tahun_lbs' => 2025,
                'jumlah_lahan' => 1,
                'total_luas' => 2.75,
                'total_panen' => 14,
                'rincian_tipe_lahan' => [['nama_tipe' => 'Pasang Surut', 'total_luas' => 2.75]],
            ]]]),
            '*/api/statistik/kecamatan/1*' => Http::response(['success' => true, 'data' => [
                'kecamatan' => ['id' => 1, 'nama_kecamatan' => 'Alalak'],
                'summary' => ['total_luas_tanam_ha' => 2.75, 'total_produksi_ton' => 14, 'jumlah_tahun' => 1],
                'rows' => [[
                    'tahun' => 2025, 'luas_tanam_ha' => 2.75, 'luas_panen_ha' => 2.5,
                    'produktivitas_ton_ha' => 5.6, 'produksi_ton' => 14,
                    'status_data' => 'Tetap', 'sumber_data' => 'BPS',
                ]],
            ]]),
        ]);

        foreach ([
            '/pejabat/cetak-laporan',
            '/pejabat/lahan-kecamatan/pdf',
            '/pejabat/lahan-sawah/pdf',
            '/pejabat/produksi-kecamatan/pdf?kecamatan=1&tahun=2025',
            '/pejabat/produksi-kelurahan/pdf',
        ] as $url) {
            $response = $this->withSession(['role_id' => 3, 'token' => 'official-token'])->get($url);
            $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertGreaterThan(500, strlen($response->getContent()), $url . ' menghasilkan PDF kosong.');
        }
    }

    public function test_production_village_excel_contains_backend_data(): void
    {
        Http::fake([
            '*/api/produksi-kelurahan*' => Http::response(['success' => true, 'data' => [[
                'nama_kecamatan' => 'Alalak',
                'nama_kelurahan' => 'Berangas',
                'tahun_lbs' => 2025,
                'jumlah_lahan' => 1,
                'total_luas' => 2.75,
                'total_panen' => 14,
                'rincian_tipe_lahan' => [['nama_tipe' => 'Pasang Surut', 'total_luas' => 2.75]],
            ]]]),
        ]);

        $response = $this->withSession(['role_id' => 3, 'token' => 'official-token'])
            ->get('/pejabat/produksi-kelurahan/excel');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $sheetText = $this->spreadsheetText($response);
        $this->assertStringContainsString('Alalak', $sheetText);
        $this->assertStringContainsString('Berangas', $sheetText);
        $this->assertStringContainsString('14', $sheetText);
    }
}
