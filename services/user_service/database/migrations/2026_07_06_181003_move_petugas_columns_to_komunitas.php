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
        Schema::table('komunitas', function (Blueprint $table) {
            if (!Schema::hasColumn('komunitas', 'wilayah_kecamatan_id')) {
                $table->unsignedInteger('wilayah_kecamatan_id')->nullable()->after('komunitas_induk_id');
                $table->index('wilayah_kecamatan_id', 'kom_wilayah_kecamatan_index');
            }
            if (!Schema::hasColumn('komunitas', 'wilayah_kelurahan_ids')) {
                $table->json('wilayah_kelurahan_ids')->nullable()->after('wilayah_kecamatan_id');
            }
            if (!Schema::hasColumn('komunitas', 'instansi_asal')) {
                $table->string('instansi_asal', 80)->nullable()->after('wilayah_kelurahan_ids');
            }
            if (!Schema::hasColumn('komunitas', 'nama_bpp')) {
                $table->string('nama_bpp', 120)->nullable()->after('instansi_asal');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'nama_bpp')) {
                $table->dropColumn('nama_bpp');
            }
            if (Schema::hasColumn('users', 'instansi_asal')) {
                $table->dropColumn('instansi_asal');
            }
            if (Schema::hasColumn('users', 'wilayah_kelurahan_ids')) {
                $table->dropColumn('wilayah_kelurahan_ids');
            }
            if (Schema::hasColumn('users', 'wilayah_kecamatan_id')) {
                $table->dropIndex('users_wilayah_kecamatan_index');
                $table->dropColumn('wilayah_kecamatan_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('komunitas', function (Blueprint $table) {
            //
        });
    }
};
