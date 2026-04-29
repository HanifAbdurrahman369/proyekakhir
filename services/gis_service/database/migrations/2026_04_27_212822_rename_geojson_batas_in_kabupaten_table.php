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
    Schema::table('kabupaten', function (Blueprint $table) {
        // Mengubah nama kolom dari geojson_batas menjadi polygon_baritokuala
        $table->renameColumn('geojson_batas', 'polygon_baritokuala');
    });
}

public function down(): void
{
    Schema::table('kabupaten', function (Blueprint $table) {
        $table->renameColumn('polygon_baritokuala', 'geojson_batas');
    });
}
};
