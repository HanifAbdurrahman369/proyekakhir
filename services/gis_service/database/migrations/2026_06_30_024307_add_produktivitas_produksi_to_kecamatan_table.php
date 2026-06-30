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
        Schema::table('kecamatan', function (Blueprint $table) {
            $table->double('produktivitas')->nullable()->after('nama_kecamatan');
            $table->double('produksi')->nullable()->after('produktivitas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kecamatan', function (Blueprint $table) {
            $table->dropColumn(['produktivitas', 'produksi']);
        });
    }
};
