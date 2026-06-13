<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('kecamatan', 'polygon_geojson')) {
            Schema::table('kecamatan', function (Blueprint $table) {
                $table->longText('polygon_geojson')->nullable()->after('nama_kecamatan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('kecamatan', 'polygon_geojson')) {
            Schema::table('kecamatan', function (Blueprint $table) {
                $table->dropColumn('polygon_geojson');
            });
        }
    }
};
