<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('lahan_sawah', 'tipe_rawa')) {
            Schema::table('lahan_sawah', function (Blueprint $table) {
                $table->dropColumn('tipe_rawa');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('lahan_sawah', 'tipe_rawa')) {
            Schema::table('lahan_sawah', function (Blueprint $table) {
                $table->string('tipe_rawa', 100)->nullable()->after('pemilik_lahan');
            });
        }
    }
};
