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
        Schema::table('monitoring_kondisi', function (Blueprint $table) {
            $table->decimal('n_level', 10, 4)->nullable()->after('kekeruhan_air');
            $table->decimal('p_level', 10, 4)->nullable()->after('n_level');
            $table->decimal('k_level', 10, 4)->nullable()->after('p_level');
            $table->boolean('is_shared')->default(false)->after('k_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitoring_kondisi', function (Blueprint $table) {
            $table->dropColumn(['n_level', 'p_level', 'k_level', 'is_shared']);
        });
    }
};
