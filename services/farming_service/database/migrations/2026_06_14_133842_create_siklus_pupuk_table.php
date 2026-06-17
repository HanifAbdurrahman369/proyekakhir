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
        Schema::create('siklus_pupuk', function (Blueprint $table) {

            // id menjadi INT
            $table->increments('id');

            // FK ke siklus_tanam
            $table->integer('siklus_tanam_id');

            // FK ke jenis_pupuk
            $table->integer('pupuk_id');

            $table->date('tanggal_pemupukan');

            $table->decimal('takaran', 10, 2);

            $table->timestamps();

            $table->foreign('siklus_tanam_id')
                ->references('id')
                ->on('siklus_tanam')
                ->onDelete('cascade');

            $table->foreign('pupuk_id')
                ->references('id')
                ->on('jenis_pupuk')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siklus_pupuk');
    }
};