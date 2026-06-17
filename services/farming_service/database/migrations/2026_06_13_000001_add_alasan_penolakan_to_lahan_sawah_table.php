<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('lahan_sawah', 'alasan_penolakan')) {
            Schema::table('lahan_sawah', function (Blueprint $table) {
                $table->text('alasan_penolakan')->nullable()->after('status_verifikasi');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lahan_sawah', 'alasan_penolakan')) {
            Schema::table('lahan_sawah', function (Blueprint $table) {
                $table->dropColumn('alasan_penolakan');
            });
        }
    }
};
