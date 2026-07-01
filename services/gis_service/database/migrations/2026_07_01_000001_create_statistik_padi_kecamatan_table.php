<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statistik_padi_kecamatan', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('kecamatan_id');
            $table->unsignedSmallInteger('tahun');
            $table->decimal('luas_tanam_ha', 14, 2)->default(0);
            $table->decimal('luas_panen_ha', 14, 2)->default(0);
            $table->decimal('produktivitas_kw_ha', 8, 2)->default(0);
            $table->decimal('produktivitas_ton_ha', 8, 3)->default(0);
            $table->decimal('produksi_ton', 14, 2)->default(0);
            $table->boolean('is_sementara')->default(false);
            $table->string('sumber_data')->nullable();
            $table->timestamps();

            $table->unique(['kecamatan_id', 'tahun'], 'stat_padi_kec_tahun_unique');
            $table->index('tahun');
        });

        Schema::table('kecamatan', function (Blueprint $table) {
            if (!Schema::hasColumn('kecamatan', 'luas_tanam_ha')) {
                $table->decimal('luas_tanam_ha', 14, 2)->nullable()->after('produksi');
            }

            if (!Schema::hasColumn('kecamatan', 'luas_panen_ha')) {
                $table->decimal('luas_panen_ha', 14, 2)->nullable()->after('luas_tanam_ha');
            }

            if (!Schema::hasColumn('kecamatan', 'tahun_data_padi')) {
                $table->unsignedSmallInteger('tahun_data_padi')->nullable()->after('luas_panen_ha');
            }

            if (!Schema::hasColumn('kecamatan', 'sumber_data_padi')) {
                $table->string('sumber_data_padi')->nullable()->after('tahun_data_padi');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statistik_padi_kecamatan');

        Schema::table('kecamatan', function (Blueprint $table) {
            $columns = collect([
                'luas_tanam_ha',
                'luas_panen_ha',
                'tahun_data_padi',
                'sumber_data_padi',
            ])->filter(fn ($column) => Schema::hasColumn('kecamatan', $column))->all();

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
