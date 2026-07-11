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
        Schema::create('rekomendasi_huma', function (Blueprint $table) {
            $table->id();
            $table->integer('monitoring_kondisi_id');
            $table->foreign('monitoring_kondisi_id')->references('id')->on('monitoring_kondisi')->onDelete('cascade');
            $table->unsignedBigInteger('rekomendasi_id_huma');
            $table->dateTime('tanggal_rekomendasi');
            $table->decimal('current_ph', 8, 2)->nullable();
            $table->decimal('current_water', 8, 2)->nullable();
            $table->decimal('current_n', 10, 4)->nullable();
            $table->decimal('current_p', 10, 4)->nullable();
            $table->decimal('current_k', 10, 4)->nullable();
            $table->string('water_status')->nullable();
            $table->string('status_tindakan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekomendasi_huma');
    }
};
