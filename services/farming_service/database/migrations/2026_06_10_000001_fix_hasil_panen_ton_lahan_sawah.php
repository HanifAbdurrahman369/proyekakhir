<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE lahan_sawah DROP FOREIGN KEY fk_lahan_hasil_panen_ton_siklus');
        } catch (\Throwable $e) {
            // Abaikan jika foreign key tidak ada
        }

        DB::statement("ALTER TABLE lahan_sawah MODIFY hasil_panen_ton DECIMAL(12,2) DEFAULT 0");
        DB::statement("ALTER TABLE lahan_sawah MODIFY produktivitas_ton_ha DECIMAL(10,2) DEFAULT 0");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE lahan_sawah MODIFY hasil_panen_ton INT DEFAULT NULL");
    }
};