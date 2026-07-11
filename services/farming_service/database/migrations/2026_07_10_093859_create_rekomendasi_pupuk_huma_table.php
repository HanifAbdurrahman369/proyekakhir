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
        Schema::create('rekomendasi_pupuk_huma', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekomendasi_huma_id')->constrained('rekomendasi_huma')->onDelete('cascade');
            $table->string('nama_pupuk');
            $table->decimal('dosis', 10, 2);
            $table->string('satuan');
            $table->string('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekomendasi_pupuk_huma');
    }
};
