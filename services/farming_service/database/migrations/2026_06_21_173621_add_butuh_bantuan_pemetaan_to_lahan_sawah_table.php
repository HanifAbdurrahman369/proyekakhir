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
        Schema::table('lahan_sawah', function (Blueprint $table) {
            $table->boolean('butuh_bantuan_pemetaan')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lahan_sawah', function (Blueprint $table) {
            $table->dropColumn('butuh_bantuan_pemetaan');
        });
    }
};
