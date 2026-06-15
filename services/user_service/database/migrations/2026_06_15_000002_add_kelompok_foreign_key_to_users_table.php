<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $constraintName = 'users_kelompok_id_foreign';

    public function up(): void
    {
        if (
            !Schema::hasTable('users') ||
            !Schema::hasTable('kelompok') ||
            !Schema::hasColumn('users', 'kelompok_id') ||
            $this->foreignKeyExists()
        ) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('kelompok_id', $this->constraintName)
                ->references('id')
                ->on('kelompok')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (
            !Schema::hasTable('users') ||
            !Schema::hasColumn('users', 'kelompok_id') ||
            !$this->foreignKeyExists()
        ) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign($this->constraintName);
        });
    }

    private function foreignKeyExists(): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return false;
        }

        return !empty(DB::select(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ?
               AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ?
               AND CONSTRAINT_TYPE = ?',
            [
                DB::connection()->getDatabaseName(),
                'users',
                $this->constraintName,
                'FOREIGN KEY',
            ]
        ));
    }
};
