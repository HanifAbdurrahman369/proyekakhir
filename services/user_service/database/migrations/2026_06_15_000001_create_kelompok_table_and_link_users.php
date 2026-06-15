<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('kelompok')) {
            Schema::create('kelompok', function (Blueprint $table) {
                $table->id();
                $table->enum('brigade_pangan', ['iya', 'tidak'])->default('tidak');
                $table->enum('kelompok_tani', ['iya', 'tidak'])->default('tidak');
                $table->string('nama', 150);
                $table->string('nomor_hp', 20)->nullable();
                $table->text('alamat')->nullable();
                $table->timestamps();

                $table->index('nama');
                $table->index(['brigade_pangan', 'kelompok_tani']);
            });
        }

        if (!Schema::hasColumn('users', 'kelompok_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('kelompok_id')->nullable()->after('role_id');
                $table->index('kelompok_id');
            });
        }

        $now = now();
        $data = [
            [
                'brigade_pangan' => 'tidak',
                'kelompok_tani' => 'iya',
                'nama' => 'Budi Santoso',
                'nomor_hp' => '081234567801',
                'alamat' => 'Desa Ulu Benteng, Kecamatan Marabahan',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'brigade_pangan' => 'iya',
                'kelompok_tani' => 'tidak',
                'nama' => 'Siti Aminah',
                'nomor_hp' => '081234567802',
                'alamat' => 'Desa Belawang, Kecamatan Belawang',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'brigade_pangan' => 'tidak',
                'kelompok_tani' => 'iya',
                'nama' => 'Rahman Hidayat',
                'nomor_hp' => '081234567803',
                'alamat' => 'Desa Anjir Muara Kota, Kecamatan Anjir Muara',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'brigade_pangan' => 'iya',
                'kelompok_tani' => 'tidak',
                'nama' => 'Nur Aisyah',
                'nomor_hp' => '081234567804',
                'alamat' => 'Desa Tamban Baru Tengah, Kecamatan Tamban',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'brigade_pangan' => 'tidak',
                'kelompok_tani' => 'iya',
                'nama' => 'Ahmad Fauzi',
                'nomor_hp' => '081234567805',
                'alamat' => 'Desa Rantau Badauh, Kecamatan Rantau Badauh',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($data as $row) {
            DB::table('kelompok')->updateOrInsert(
                ['nama' => $row['nama']],
                $row
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'kelompok_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['kelompok_id']);
                $table->dropColumn('kelompok_id');
            });
        }

        Schema::dropIfExists('kelompok');
    }
};
