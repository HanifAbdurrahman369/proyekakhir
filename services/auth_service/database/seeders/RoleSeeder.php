<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            1 => 'kelompok_tani',
            2 => 'petugas',
            3 => 'pejabat',
            4 => 'admin',
            5 => 'brigade_pangan',
        ] as $id => $namaRole) {
            DB::table('roles')->updateOrInsert(['id' => $id], ['nama_role' => $namaRole]);
        }
    }
}
