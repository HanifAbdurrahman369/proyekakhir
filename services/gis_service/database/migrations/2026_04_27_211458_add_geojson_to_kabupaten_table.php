<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::table('kabupaten', function (Blueprint $table) {
        // Menambahkan kolom longText untuk menampung karakter GeoJSON yang sangat panjang
        $table->longText('geojson_batas')->nullable()->after('nama_kabupaten');
    });
}

public function down(): void
{
    Schema::table('kabupaten', function (Blueprint $table) {
        $table->dropColumn('geojson_batas');
    });
}
};
