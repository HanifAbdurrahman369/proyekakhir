<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lahan_sawah', function (Blueprint $table) {

            $table->enum('status_verifikasi', [
                'PENDING',
                'DITERIMA',
                'DITOLAK'
            ])->default('PENDING');

        });
    }

    public function down(): void
    {
        Schema::table('lahan_sawah', function (Blueprint $table) {

            $table->dropColumn('status_verifikasi');

        });
    }
};
