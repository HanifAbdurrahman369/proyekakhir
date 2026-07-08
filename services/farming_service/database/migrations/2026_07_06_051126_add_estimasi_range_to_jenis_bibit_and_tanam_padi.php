<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jenis_bibit', function (Blueprint $table) {
            if (!Schema::hasColumn('jenis_bibit', 'estimasi_hari_min')) {
                $table->unsignedInteger('estimasi_hari_min')->nullable();
            }
            if (!Schema::hasColumn('jenis_bibit', 'estimasi_hari_max')) {
                $table->unsignedInteger('estimasi_hari_max')->nullable();
            }
        });

        \Illuminate\Support\Facades\DB::table('jenis_bibit')->updateOrInsert(
            ['nama_bibit' => 'Inpara (Inbrida Padi Rawa)'],
            [
                'varietas' => 'Unggul',
                'masa_tanam_hari' => 102, // backward compatibility
                'estimasi_hari_min' => 102,
                'estimasi_hari_max' => 131,
            ]
        );

        Schema::table('tanam_padi', function (Blueprint $table) {
            if (!Schema::hasColumn('tanam_padi', 'estimasi_tanggal_panen_akhir')) {
                $table->date('estimasi_tanggal_panen_akhir')->nullable()->after('estimasi_tanggal_panen');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tanam_padi', function (Blueprint $table) {
            if (Schema::hasColumn('tanam_padi', 'estimasi_tanggal_panen_akhir')) {
                $table->dropColumn('estimasi_tanggal_panen_akhir');
            }
        });

        Schema::table('jenis_bibit', function (Blueprint $table) {
            if (Schema::hasColumn('jenis_bibit', 'estimasi_hari_min')) {
                $table->dropColumn(['estimasi_hari_min', 'estimasi_hari_max']);
            }
        });

        \Illuminate\Support\Facades\DB::table('jenis_bibit')->where('nama_bibit', 'Inpara (Inbrida Padi Rawa)')->delete();
    }
};
