<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'wilayah_kecamatan_id')) {
                $table->unsignedInteger('wilayah_kecamatan_id')->nullable()->after('alamat');
                $table->index('wilayah_kecamatan_id', 'users_wilayah_kecamatan_index');
            }

            if (!Schema::hasColumn('users', 'wilayah_kelurahan_ids')) {
                $table->json('wilayah_kelurahan_ids')->nullable()->after('wilayah_kecamatan_id');
            }

            if (!Schema::hasColumn('users', 'instansi_asal')) {
                $table->string('instansi_asal', 80)->nullable()->after('wilayah_kelurahan_ids');
            }

            if (!Schema::hasColumn('users', 'nama_bpp')) {
                $table->string('nama_bpp', 120)->nullable()->after('instansi_asal');
            }
        });

        $belawangId = (int) (DB::table('kecamatan')
            ->whereRaw('LOWER(TRIM(nama_kecamatan)) = ?', ['belawang'])
            ->value('id') ?? 6);

        $ranggaSuryaId = (int) (DB::table('kelurahan')
            ->where('kecamatan_id', $belawangId)
            ->whereRaw('LOWER(TRIM(nama_kelurahan)) = ?', ['rangga surya'])
            ->value('id') ?? 79);

        DB::table('users')->updateOrInsert(
            ['email' => 'abdulhidayat@gmail.com'],
            [
                'role_id' => 2,
                'komunitas_id' => null,
                'nama_lengkap' => 'Abdul hayat',
                'password' => Hash::make('123456'),
                'no_hp' => null,
                'alamat' => 'Kecamatan Belawang, Desa Rangga Surya',
                'wilayah_kecamatan_id' => $belawangId,
                'wilayah_kelurahan_ids' => json_encode([$ranggaSuryaId]),
                'instansi_asal' => 'BPP',
                'nama_bpp' => 'BPP Belawang',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('users')->updateOrInsert(
            ['email' => 'pifitfitriyani@gmail.com'],
            [
                'role_id' => 3,
                'komunitas_id' => null,
                'nama_lengkap' => 'pifit fitriyanti',
                'password' => Hash::make('123456'),
                'no_hp' => null,
                'alamat' => null,
                'wilayah_kecamatan_id' => null,
                'wilayah_kelurahan_ids' => null,
                'instansi_asal' => null,
                'nama_bpp' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'nama_bpp')) {
                $table->dropColumn('nama_bpp');
            }

            if (Schema::hasColumn('users', 'instansi_asal')) {
                $table->dropColumn('instansi_asal');
            }

            if (Schema::hasColumn('users', 'wilayah_kelurahan_ids')) {
                $table->dropColumn('wilayah_kelurahan_ids');
            }

            if (Schema::hasColumn('users', 'wilayah_kecamatan_id')) {
                $table->dropIndex('users_wilayah_kecamatan_index');
                $table->dropColumn('wilayah_kecamatan_id');
            }
        });
    }
};
