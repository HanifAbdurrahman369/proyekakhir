<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StatistikPadiKecamatanSeeder extends Seeder
{
    private const SUMBER_DATA = 'Data luas tanam, luas panen, produktivitas, dan produksi padi kecamatan 2010-2025';

    public function run(): void
    {
        if (!Schema::hasTable('kecamatan') || !Schema::hasTable('statistik_padi_kecamatan')) {
            $this->command?->warn('Tabel kecamatan atau statistik_padi_kecamatan belum tersedia.');
            return;
        }

        $path = database_path('data/statistik_padi_kecamatan.csv');

        if (!is_file($path)) {
            $this->command?->warn("File data statistik padi tidak ditemukan: {$path}");
            return;
        }

        $kecamatanByName = DB::table('kecamatan')
            ->select('id', 'nama_kecamatan')
            ->get()
            ->mapWithKeys(fn ($row) => [$this->key($row->nama_kecamatan) => $row]);

        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);
        $rows = [];
        $missing = [];
        $now = now();

        while (($line = fgetcsv($handle)) !== false) {
            if (!$headers || count($line) !== count($headers)) {
                continue;
            }

            $record = array_combine($headers, $line);
            $kecamatan = $kecamatanByName->get($this->key($record['kecamatan'] ?? ''));

            if (!$kecamatan) {
                $missing[] = $record['kecamatan'] ?? '(tanpa nama)';
                continue;
            }

            $tahun = (int) $record['tahun'];

            $rows[] = [
                'kecamatan_id' => $kecamatan->id,
                'tahun' => $tahun,
                'luas_tanam_ha' => $this->decimal($record['luas_tanam_ha'] ?? 0),
                'luas_panen_ha' => $this->decimal($record['luas_panen_ha'] ?? 0),
                'produktivitas_kw_ha' => $this->decimal($record['produktivitas_kw_ha'] ?? 0),
                'produktivitas_ton_ha' => $this->decimal($record['produktivitas_ton_ha'] ?? 0),
                'produksi_ton' => $this->decimal($record['produksi_ton'] ?? 0),
                'is_sementara' => $tahun === 2025 ? false : (int) ($record['is_sementara'] ?? 0) === 1,
                'sumber_data' => self::SUMBER_DATA,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        fclose($handle);

        if (!empty($rows)) {
            DB::table('statistik_padi_kecamatan')->upsert(
                $rows,
                ['kecamatan_id', 'tahun'],
                [
                    'luas_tanam_ha',
                    'luas_panen_ha',
                    'produktivitas_kw_ha',
                    'produktivitas_ton_ha',
                    'produksi_ton',
                    'is_sementara',
                    'sumber_data',
                    'updated_at',
                ]
            );
        }

        $this->syncKecamatanSummary();

        if (!empty($missing)) {
            $this->command?->warn('Nama kecamatan tidak ditemukan di master: ' . implode(', ', array_unique($missing)));
        }

        $this->command?->info(count($rows) . ' baris statistik padi kecamatan berhasil diimpor.');
    }

    private function syncKecamatanSummary(): void
    {
        $latestYears = DB::table('statistik_padi_kecamatan')
            ->select('kecamatan_id', DB::raw('MAX(tahun) as tahun'))
            ->groupBy('kecamatan_id');

        $latestRows = DB::table('statistik_padi_kecamatan as s')
            ->joinSub($latestYears, 'latest', function ($join) {
                $join->on('latest.kecamatan_id', '=', 's.kecamatan_id')
                    ->on('latest.tahun', '=', 's.tahun');
            })
            ->select(
                's.kecamatan_id',
                's.tahun',
                's.luas_tanam_ha',
                's.luas_panen_ha',
                's.produktivitas_ton_ha',
                's.produksi_ton',
                's.sumber_data'
            )
            ->get();

        foreach ($latestRows as $row) {
            $updates = [];

            if (Schema::hasColumn('kecamatan', 'produktivitas')) {
                $updates['produktivitas'] = $row->produktivitas_ton_ha;
            }

            if (Schema::hasColumn('kecamatan', 'produksi')) {
                $updates['produksi'] = $row->produksi_ton;
            }

            if (Schema::hasColumn('kecamatan', 'luas_tanam_ha')) {
                $updates['luas_tanam_ha'] = $row->luas_tanam_ha;
            }

            if (Schema::hasColumn('kecamatan', 'luas_panen_ha')) {
                $updates['luas_panen_ha'] = $row->luas_panen_ha;
            }

            if (Schema::hasColumn('kecamatan', 'tahun_data_padi')) {
                $updates['tahun_data_padi'] = $row->tahun;
            }

            if (Schema::hasColumn('kecamatan', 'sumber_data_padi')) {
                $updates['sumber_data_padi'] = $row->sumber_data;
            }

            if (!empty($updates)) {
                DB::table('kecamatan')->where('id', $row->kecamatan_id)->update($updates);
            }
        }
    }

    private function key(?string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $value)));
    }

    private function decimal(mixed $value): float
    {
        return (float) str_replace(',', '.', (string) $value);
    }
}
