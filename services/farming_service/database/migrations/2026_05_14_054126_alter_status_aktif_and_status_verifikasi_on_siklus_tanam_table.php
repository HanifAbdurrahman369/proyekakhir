<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siklus_tanam', function (Blueprint $table) {

            // ubah status_aktif dari INT -> ENUM
            $table->enum('status_aktif', ['AKTIF', 'NONAKTIF'])
                  ->default('AKTIF')
                  ->change();

            // pastikan status_verifikasi juga benar (jika belum sesuai)
            $table->enum('status_verifikasi', ['PENDING', 'DITERIMA', 'DITOLAK'])
                  ->default('PENDING')
                  ->change();
        });
    }

    public function down(): void
    {
        Schema::table('siklus_tanam', function (Blueprint $table) {

            // rollback ke tipe lama (jika diperlukan)
            $table->integer('status_aktif')->change();
            $table->string('status_verifikasi')->change();
        });
    }
};