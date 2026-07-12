<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->where('id', 1)->update(['nama_role' => 'kelompok_tani']);
        DB::table('roles')->where('id', 2)->update(['nama_role' => 'petugas']);
        DB::table('roles')->where('id', 3)->update(['nama_role' => 'pejabat']);
        DB::table('roles')->where('id', 4)->update(['nama_role' => 'admin']);
        DB::table('roles')->updateOrInsert(['id' => 5], ['nama_role' => 'brigade_pangan']);

        Schema::table('kelompok', function (Blueprint $table) {
            $table->string('kode_anggota', 30)->nullable()->after('id');
            $table->string('nik', 20)->nullable()->after('kode_anggota');
            $table->string('jenis_kelompok', 30)->nullable()->after('nik');
            $table->string('nama_kelompok', 150)->nullable()->after('nama');
            $table->string('status_keanggotaan', 20)->default('AKTIF')->after('alamat');
            $table->unsignedBigInteger('kelompok_tani_induk_id')->nullable()->after('status_keanggotaan');
        });

        $anggotaLama = DB::table('kelompok')->orderBy('id')->get();
        foreach ($anggotaLama as $anggota) {
            $jenis = $anggota->brigade_pangan === 'iya' ? 'brigade_pangan' : 'kelompok_tani';
            $prefix = $jenis === 'brigade_pangan' ? 'BP' : 'KT';

            DB::table('kelompok')->where('id', $anggota->id)->update([
                'kode_anggota' => sprintf('%s-%04d', $prefix, $anggota->id),
                'jenis_kelompok' => $jenis,
                'nama_kelompok' => $jenis === 'brigade_pangan'
                    ? 'Brigade Pangan Barito Kuala'
                    : 'Kelompok Tani Barito Kuala',
                'status_keanggotaan' => 'AKTIF',
            ]);
        }

        $kelompokNurul = $this->ensureKelompokTaniForUser(1, 'Nurul Hikmah');
        $this->ensureKelompokTaniForUser(5, 'Hanif');
        $this->ensureKelompokTaniForUser(6, 'Budi Santoso');
        $this->ensureKelompokTaniForUser(15, 'budi santoso');

        $kelompokBudi = (int) (DB::table('kelompok')
            ->whereRaw('LOWER(TRIM(nama)) = ?', ['budi santoso'])
            ->where('jenis_kelompok', 'kelompok_tani')
            ->value('id') ?? $kelompokNurul);

        $this->ensureBrigadeForUser(7, 'Parjo', $kelompokNurul);
        $this->ensureBrigadeForUser(13, 'Budi Santoso', $kelompokBudi);

        $indukDefault = (int) DB::table('kelompok')
            ->where('jenis_kelompok', 'kelompok_tani')
            ->orderBy('id')
            ->value('id');

        DB::table('kelompok')
            ->where('jenis_kelompok', 'brigade_pangan')
            ->whereNull('kelompok_tani_induk_id')
            ->update(['kelompok_tani_induk_id' => $indukDefault ?: null]);

        Schema::table('kelompok', function (Blueprint $table) {
            $table->dropIndex('kelompok_brigade_pangan_kelompok_tani_index');
            $table->dropColumn(['brigade_pangan', 'kelompok_tani']);
            $table->unique('kode_anggota', 'kelompok_kode_anggota_unique');
            $table->unique('nik', 'kelompok_nik_unique');
            $table->index(['jenis_kelompok', 'status_keanggotaan'], 'kelompok_jenis_status_index');
            $table->index('kelompok_tani_induk_id', 'kelompok_induk_index');
            $table->foreign('kelompok_tani_induk_id', 'kelompok_induk_foreign')
                ->references('id')
                ->on('kelompok')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kelompok', function (Blueprint $table) {
            $table->dropForeign('kelompok_induk_foreign');
            $table->dropIndex('kelompok_induk_index');
            $table->dropIndex('kelompok_jenis_status_index');
            $table->dropUnique('kelompok_kode_anggota_unique');
            $table->dropUnique('kelompok_nik_unique');
            $table->enum('brigade_pangan', ['iya', 'tidak'])->default('tidak');
            $table->enum('kelompok_tani', ['iya', 'tidak'])->default('tidak');
        });

        DB::table('kelompok')->where('jenis_kelompok', 'kelompok_tani')->update(['kelompok_tani' => 'iya']);
        DB::table('kelompok')->where('jenis_kelompok', 'brigade_pangan')->update(['brigade_pangan' => 'iya']);

        Schema::table('kelompok', function (Blueprint $table) {
            $table->dropColumn([
                'kode_anggota',
                'nik',
                'jenis_kelompok',
                'nama_kelompok',
                'status_keanggotaan',
                'kelompok_tani_induk_id',
            ]);
            $table->index(['brigade_pangan', 'kelompok_tani']);
        });

        DB::table('users')->where('role_id', 5)->update(['role_id' => 1]);
        DB::table('roles')->where('id', 5)->delete();
        DB::table('roles')->where('id', 1)->update(['nama_role' => 'petani']);
        DB::table('roles')->where('id', 4)->update(['nama_role' => 'super_admin']);
    }

    private function ensureKelompokTaniForUser(int $userId, string $nama): int
    {
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return (int) (DB::table('kelompok')->where('jenis_kelompok', 'kelompok_tani')->value('id') ?? 0);
        }

        $kelompokId = DB::table('kelompok')
            ->whereRaw('LOWER(TRIM(nama)) = ?', [mb_strtolower(trim($nama))])
            ->where('jenis_kelompok', 'kelompok_tani')
            ->value('id');

        if (!$kelompokId) {
            $kelompokId = DB::table('kelompok')->insertGetId([
                'kode_anggota' => 'KT-LEG-' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT),
                'nik' => null,
                'jenis_kelompok' => 'kelompok_tani',
                'brigade_pangan' => 'tidak',
                'kelompok_tani' => 'iya',
                'nama' => $user->nama_lengkap,
                'nama_kelompok' => 'Kelompok Tani Barito Kuala',
                'nomor_hp' => $user->no_hp,
                'alamat' => $user->alamat,
                'status_keanggotaan' => 'AKTIF',
                'kelompok_tani_induk_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('users')->where('id', $userId)->update([
            'role_id' => 1,
            'kelompok_id' => $kelompokId,
        ]);

        return (int) $kelompokId;
    }

    private function ensureBrigadeForUser(int $userId, string $nama, int $indukId): void
    {
        $user = DB::table('users')->where('id', $userId)->first();
        if (!$user) {
            return;
        }

        $kode = 'BP-LEG-' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT);
        $kelompokId = DB::table('kelompok')->where('kode_anggota', $kode)->value('id');

        if (!$kelompokId) {
            $kelompokId = DB::table('kelompok')->insertGetId([
                'kode_anggota' => $kode,
                'nik' => null,
                'jenis_kelompok' => 'brigade_pangan',
                'brigade_pangan' => 'iya',
                'kelompok_tani' => 'tidak',
                'nama' => $nama,
                'nama_kelompok' => 'Brigade Pangan Barito Kuala',
                'nomor_hp' => $user->no_hp,
                'alamat' => $user->alamat,
                'status_keanggotaan' => 'AKTIF',
                'kelompok_tani_induk_id' => $indukId ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('users')->where('id', $userId)->update([
            'role_id' => 5,
            'kelompok_id' => $kelompokId,
        ]);
    }
};
