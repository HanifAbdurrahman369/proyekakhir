<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LahanSimulasiSeeder extends Seeder
{
    public function run(): void
    {
        // 1. NONAKTIFKAN CEK FOREIGN KEY SEMENTARA (Agar aman saat insert)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 2. KOSONGKAN TABEL SEBELUM DIISI (Agar tidak ada data ganda jika dijalankan 2x)
        DB::table('hasil_panen')->truncate();
        DB::table('siklus_tanam')->truncate();
        DB::table('lahan_sawah')->truncate();
        DB::table('kelurahan')->truncate();
        DB::table('kecamatan')->truncate();
        DB::table('kabupaten')->truncate();
        DB::table('provinsi')->truncate();
        DB::table('jenis_bibit')->truncate();
        DB::table('kategori_lahan')->truncate();
        DB::table('tipe_lahan')->truncate();
        DB::table('users')->truncate();
        DB::table('roles')->truncate();

        // 3. INSERT DATA INDUK (Wajib ada agar lahan sawah bisa di-insert)
        DB::table('roles')->insert(['id' => 1, 'nama_role' => 'kelompok_tani']);
        DB::table('users')->insert([
            'id' => 1, 'role_id' => 1, 'role' => 'kelompok_tani',
            'nama_lengkap' => 'Petani Simulasi', 'email' => 'petani@simulasi.com', 
            'password' => bcrypt('password')
        ]);
        
        DB::table('provinsi')->insert(['id' => 1, 'nama_provinsi' => 'Kalimantan Selatan']);
        DB::table('kabupaten')->insert(['id' => 1, 'provinsi_id' => 1, 'nama_kabupaten' => 'Barito Kuala']);
        DB::table('kecamatan')->insert(['id' => 1, 'kabupaten_id' => 1, 'nama_kecamatan' => 'Marabahan']);
        DB::table('kelurahan')->insert(['id' => 1, 'kecamatan_id' => 1, 'nama_kelurahan' => 'Ulu Benteng']);
        
        DB::table('kategori_lahan')->insert(['id' => 1, 'nama_kategori' => 'Lahan Sawah Rawa', 'deskripsi' => 'Rawa Pasang Surut']);
        DB::table('tipe_lahan')->insert(['id' => 1, 'nama_tipe' => 'Lahan Sawah', 'deskripsi' => 'Lahan sawah rawa untuk simulasi']);
        DB::table('jenis_bibit')->insert([
            ['id' => 1, 'nama_bibit' => 'Siam Mayang', 'varietas' => 'Lokal', 'masa_tanam_hari' => 270],
            ['id' => 2, 'nama_bibit' => 'Inpara 3', 'varietas' => 'Unggul', 'masa_tanam_hari' => 120],
        ]);

        // ========================================================================
        // 4. INSERT DATA LAHAN SAWAH (POLYGON KOTAK FIKTIF DI BARITO KUALA)
        // ========================================================================
        
        // --- Sawah Kotak 1 ---
        DB::table('lahan_sawah')->insert([
            'id' => 1,
            'user_id' => 1,
            'kecamatan_id' => 1,
            'kelurahan_id' => '1',
            'nama_lahan' => 'Sawah Kotak Area A',
            'pemilik_lahan' => 'Bapak Budi',
            'tipe_lahan_id' => 1,
            'luas_lahan_hektar' => 2.5,
            // Titik tengah koordinat (sekitar Marabahan, Batola)
            'latitude' => -2.980000, 
            'longitude' => 114.590000,
            // Format MySQL Polygon: Titik 1, Titik 2, Titik 3, Titik 4, Titik 1 (Kembali ke awal)
            'polygon_area' => DB::raw("ST_GeomFromText('POLYGON((114.58 -2.97, 114.60 -2.97, 114.60 -2.99, 114.58 -2.99, 114.58 -2.97))')"),
        ]);

        // --- Sawah Kotak 2 ---
        DB::table('lahan_sawah')->insert([
            'id' => 2,
            'user_id' => 1,
            'kecamatan_id' => 1,
            'kelurahan_id' => '1',
            'nama_lahan' => 'Sawah Kotak Area B',
            'pemilik_lahan' => 'Ibu Siti',
            'tipe_lahan_id' => 1,
            'luas_lahan_hektar' => 1.8,
            // Titik tengah koordinat
            'latitude' => -2.950000, 
            'longitude' => 114.620000,
            'polygon_area' => DB::raw("ST_GeomFromText('POLYGON((114.61 -2.94, 114.63 -2.94, 114.63 -2.96, 114.61 -2.96, 114.61 -2.94))')"),
        ]);

        // ========================================================================
        // 5. INSERT DATA STATISTIK PRODUKTIVITAS (UNTUK CHART/TABEL DI FRONTEND)
        // ========================================================================
        
        // Siklus tanam untuk sawah 1 dan 2
        DB::table('siklus_tanam')->insert([
            ['id' => 1, 'lahan_id' => 1, 'bibit_id' => 1, 'tanggal_tanam' => '2025-01-10', 'status_aktif' => 0],
            ['id' => 2, 'lahan_id' => 2, 'bibit_id' => 1, 'tanggal_tanam' => '2025-01-15', 'status_aktif' => 0],
        ]);

        // Hasil panen untuk sawah 1 dan 2 (Fiktif)
        DB::table('hasil_panen')->insert([
            ['id' => 1, 'siklus_tanam_id' => 1, 'tanggal_panen' => '2025-05-15', 'total_produksi_ton' => 12.5, 'produktivitas_ton_ha' => 5.0],
            ['id' => 2, 'siklus_tanam_id' => 2, 'tanggal_panen' => '2025-05-20', 'total_produksi_ton' => 8.2, 'produktivitas_ton_ha' => 4.5],
        ]);

        // 6. AKTIFKAN KEMBALI CEK FOREIGN KEY
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
