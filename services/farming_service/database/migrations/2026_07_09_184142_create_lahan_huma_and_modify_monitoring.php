<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Delete all huma entries from monitoring_kondisi and lahan_sawah before we migrate
        // to avoid foreign key violations when renaming column.
        DB::table('monitoring_kondisi')->truncate();
        DB::table('lahan_sawah')->whereRaw("JSON_VALID(catatan_verifikasi) = 1 AND JSON_UNQUOTE(JSON_EXTRACT(catatan_verifikasi, '$.source')) = 'huma'")->delete();

        // 1. Create lahan_huma table
        Schema::create('lahan_huma', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pemilik_id')->nullable();
            $table->unsignedBigInteger('kecamatan_id')->nullable();
            $table->string('nama_lahan');
            $table->text('alamat_detail')->nullable();
            $table->decimal('luas_lahan_hektar', 10, 2)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('koordinat_tengah', 100)->nullable();
            $table->geometry('polygon_area')->nullable();
            $table->enum('status_verifikasi', ['PENDING', 'DITERIMA', 'DITOLAK'])->default('PENDING');
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->json('catatan_verifikasi')->nullable();
            $table->timestamps();
        });

        // 2. Modify monitoring_kondisi
        Schema::table('monitoring_kondisi', function (Blueprint $table) {
            $table->dropForeign('fk_monitoring_lahan');
            $table->renameColumn('lahan_id', 'lahan_huma_id');
        });
        
        // Change type in a separate closure to ensure rename is processed
        Schema::table('monitoring_kondisi', function (Blueprint $table) {
            $table->unsignedBigInteger('lahan_huma_id')->change();
        });
        
        // Add new foreign key to lahan_huma
        Schema::table('monitoring_kondisi', function (Blueprint $table) {
            $table->foreign('lahan_huma_id', 'fk_monitoring_huma')
                  ->references('id')
                  ->on('lahan_huma')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_kondisi', function (Blueprint $table) {
            $table->dropForeign('fk_monitoring_huma');
            $table->renameColumn('lahan_huma_id', 'lahan_id');
        });

        Schema::table('monitoring_kondisi', function (Blueprint $table) {
            $table->foreign('lahan_id', 'fk_monitoring_lahan')
                  ->references('id')
                  ->on('lahan_sawah')
                  ->onDelete('cascade');
        });

        Schema::dropIfExists('lahan_huma');
    }
};
