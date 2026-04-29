<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BatasWilayahSeeder extends Seeder
{
    public function run(): void
    {
        $file = 'barito_kuala_kabupaten_clean.geojson';
        
        if (Storage::exists($file)) {
            $jsonContent = Storage::get($file);
            
            // Menggunakan nama kolom yang baru: polygon_baritokuala
            DB::table('kabupaten')
                ->where('nama_kabupaten', 'LIKE', '%Barito Kuala%')
                ->update([
                    'polygon_baritokuala' => $jsonContent
                ]);
                
            $this->command->info('Data GeoJSON berhasil disimpan ke kolom polygon_baritokuala!');
        } else {
            $this->command->error('File tidak ditemukan! Pastikan file ada di storage/app/');
        }
    }
}