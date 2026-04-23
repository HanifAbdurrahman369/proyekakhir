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
        DB::table('roles')->insert([
            [
                'nama_role' => 'petani',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_role' => 'petugas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_role' => 'penjabat',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
