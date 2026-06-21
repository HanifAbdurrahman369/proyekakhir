<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tanam_padi', function (Blueprint $table) {
            $table->foreign('diverifikasi_oleh')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::table('panen_padi', function (Blueprint $table) {
            $table->foreign('diverifikasi_oleh')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tanam_padi', function (Blueprint $table) {
            $table->dropForeign(['diverifikasi_oleh']);
        });

        Schema::table('panen_padi', function (Blueprint $table) {
            $table->dropForeign(['diverifikasi_oleh']);
        });
    }
};
