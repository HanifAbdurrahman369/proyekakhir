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
        Schema::table('lahan_huma', function (Blueprint $table) {
            $table->string('device_id')->nullable()->after('id');
            $table->string('external_id')->nullable()->after('device_id');
            $table->string('nama_pemilik')->nullable()->after('nama_lahan');
            $table->string('district_name')->nullable()->after('alamat_detail');
            $table->string('tipe_tanah')->nullable()->after('district_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lahan_huma', function (Blueprint $table) {
            $table->dropColumn(['device_id', 'external_id', 'nama_pemilik', 'district_name', 'tipe_tanah']);
        });
    }
};
