<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitoring_kondisi', function (Blueprint $table) {
            $table->unsignedBigInteger('lahan_huma_id')->nullable()->change();
        });

        if (! Schema::hasColumn('monitoring_kondisi', 'lahan_id')) {
            Schema::table('monitoring_kondisi', function (Blueprint $table) {
                $table->integer('lahan_id')->nullable()->after('lahan_huma_id');
            });
        } else {
            // Membuat migrasi aman dijalankan ulang bila DDL sebelumnya terhenti
            // setelah kolom tercipta tetapi sebelum foreign key ditambahkan.
            Schema::table('monitoring_kondisi', function (Blueprint $table) {
                $table->integer('lahan_id')->nullable()->change();
            });
        }

        Schema::table('monitoring_kondisi', function (Blueprint $table) {
            $table->foreign('lahan_id', 'fk_monitoring_sawah')
                ->references('id')
                ->on('lahan_sawah')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_kondisi', function (Blueprint $table) {
            $table->dropForeign('fk_monitoring_sawah');
            $table->dropColumn('lahan_id');
            $table->unsignedBigInteger('lahan_huma_id')->nullable(false)->change();
        });
    }
};
