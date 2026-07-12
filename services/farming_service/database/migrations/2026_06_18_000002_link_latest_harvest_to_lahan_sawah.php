<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('lahan_sawah', 'riwayat_panen_terakhir_id')) {
            Schema::table('lahan_sawah', function (Blueprint $table) {
                $table->unsignedInteger('riwayat_panen_terakhir_id')
                    ->nullable()
                    ->after('hasil_panen_siklus_id');
                $table->foreign('riwayat_panen_terakhir_id', 'fk_lahan_riwayat_panen_terakhir')
                    ->references('id')
                    ->on('riwayat_panen')
                    ->nullOnDelete();
            });
        }

        $lahanIds = DB::table('riwayat_panen')
            ->where('status_verifikasi', 'DITERIMA')
            ->whereDate('tanggal_panen', '<=', now()->toDateString())
            ->distinct()
            ->pluck('lahan_id');

        foreach ($lahanIds as $lahanId) {
            $panenTerakhir = DB::table('riwayat_panen')
                ->where('lahan_id', $lahanId)
                ->where('status_verifikasi', 'DITERIMA')
                ->whereDate('tanggal_panen', '<=', now()->toDateString())
                ->orderByDesc('tanggal_panen')
                ->orderByDesc('id')
                ->first();

            if (!$panenTerakhir) {
                continue;
            }

            DB::table('lahan_sawah')->where('id', $lahanId)->update([
                'hasil_panen_ton' => $panenTerakhir->hasil_panen_ton,
                'produktivitas_ton_ha' => $panenTerakhir->produktivitas_ton_ha,
                'hasil_panen_siklus_id' => $panenTerakhir->siklus_tanam_id,
                'riwayat_panen_terakhir_id' => $panenTerakhir->id,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('lahan_sawah', 'riwayat_panen_terakhir_id')) {
            return;
        }

        Schema::table('lahan_sawah', function (Blueprint $table) {
            $table->dropForeign('fk_lahan_riwayat_panen_terakhir');
            $table->dropColumn('riwayat_panen_terakhir_id');
        });
    }
};
