<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tanam_padi')
            ->whereNull('riwayat_pemupukan')
            ->update(['riwayat_pemupukan' => json_encode([])]);

        DB::statement('ALTER TABLE tanam_padi MODIFY riwayat_pemupukan JSON NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tanam_padi MODIFY riwayat_pemupukan JSON NULL');
    }
};
