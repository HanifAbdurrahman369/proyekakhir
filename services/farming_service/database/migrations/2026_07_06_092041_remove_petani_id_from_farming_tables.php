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
            $table->dropForeign(['petani_id']);
            $table->dropColumn('petani_id');
        });
        Schema::table('tanam_padi', function (Blueprint $table) {
            $table->dropForeign(['petani_id']);
            $table->dropColumn('petani_id');
        });
        Schema::table('panen_padi', function (Blueprint $table) {
            $table->dropForeign(['petani_id']);
            $table->dropColumn('petani_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lahan_sawah', function (Blueprint $table) {
            $table->foreignId('petani_id')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::table('tanam_padi', function (Blueprint $table) {
            $table->foreignId('petani_id')->nullable()->constrained('users')->nullOnDelete();
        });
        Schema::table('panen_padi', function (Blueprint $table) {
            $table->foreignId('petani_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }
};
