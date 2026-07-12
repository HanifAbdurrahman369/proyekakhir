<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LahanSimulasiSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn(
            'Seeder simulasi dinonaktifkan agar lahan_sawah, tanam_padi, dan panen_padi tetap dimulai dari data kosong.'
        );
    }
}
