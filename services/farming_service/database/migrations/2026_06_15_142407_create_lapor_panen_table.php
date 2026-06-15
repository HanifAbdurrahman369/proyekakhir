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
        Schema::create('lapor_panen', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('siklus_tanam_id');
            $table->date('tanggal_panen');
            $table->decimal('hasil_panen', 12, 2);
            $table->integer('estimasi_panen')->nullable();
            $table->string('status_verifikasi')->default('PENDING');
            $table->string('catatan_verifikasi')->nullable();
            $table->integer('created_by');
            $table->timestamps();

            $table->foreign('siklus_tanam_id')
                ->references('id')
                ->on('siklus_tanam')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lapor_panen');
    }
};
