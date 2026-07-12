<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->migrateKomunitas();

        Schema::table('lahan_sawah', function (Blueprint $table) {
            $table->dropForeign('fk_lahan_hasil_panen_siklus');
            $table->dropForeign('fk_lahan_riwayat_panen_terakhir');
        });

        Schema::create('tanam_padi', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('lahan_id');
            $table->integer('bibit_id');
            $table->bigInteger('petani_id');
            $table->date('tanggal_tanam');
            $table->unsignedInteger('estimasi_hari')->nullable();
            $table->date('estimasi_tanggal_panen')->nullable();
            $table->enum('status_aktif', ['AKTIF', 'NONAKTIF'])->default('AKTIF');
            $table->enum('status_verifikasi', ['PENDING', 'DITERIMA', 'DITOLAK'])->default('PENDING');
            $table->json('riwayat_pemupukan')->nullable();
            $table->bigInteger('diverifikasi_oleh')->nullable();
            $table->timestamp('diverifikasi_at')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamps();

            $table->foreign('lahan_id')->references('id')->on('lahan_sawah')->cascadeOnDelete();
            $table->foreign('bibit_id')->references('id')->on('jenis_bibit')->restrictOnDelete();
            $table->foreign('petani_id')->references('id')->on('users')->restrictOnDelete();
            $table->index(['petani_id', 'status_aktif']);
            $table->index(['lahan_id', 'tanggal_tanam']);
        });

        Schema::create('panen_padi', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tanam_padi_id')->unique();
            $table->integer('lahan_id');
            $table->integer('bibit_id');
            $table->bigInteger('pemilik_id');
            $table->bigInteger('petani_id');
            $table->bigInteger('diverifikasi_oleh')->nullable();
            $table->string('nama_lahan', 150);
            $table->string('nama_bibit', 100);
            $table->string('varietas', 100)->nullable();
            $table->date('tanggal_tanam');
            $table->date('tanggal_panen');
            $table->decimal('hasil_panen_ton', 12, 2);
            $table->decimal('luas_lahan_ha', 12, 4)->default(0);
            $table->decimal('produktivitas_ton_ha', 12, 2)->default(0);
            $table->enum('status_verifikasi', ['PENDING', 'DITERIMA', 'DITOLAK'])->default('PENDING');
            $table->text('catatan_verifikasi')->nullable();
            $table->timestamp('diverifikasi_at')->nullable();
            $table->timestamps();

            $table->foreign('tanam_padi_id')->references('id')->on('tanam_padi')->cascadeOnDelete();
            $table->foreign('lahan_id')->references('id')->on('lahan_sawah')->cascadeOnDelete();
            $table->foreign('bibit_id')->references('id')->on('jenis_bibit')->restrictOnDelete();
            $table->foreign('pemilik_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('petani_id')->references('id')->on('users')->restrictOnDelete();
            $table->index(['pemilik_id', 'tanggal_panen']);
            $table->index(['petani_id', 'tanggal_panen']);
            $table->index(['status_verifikasi', 'tanggal_panen']);
        });

        Schema::table('lahan_sawah', function (Blueprint $table) {
            $table->dropColumn([
                'user_id',
                'pemilik_lahan',
                'hasil_panen_siklus_id',
                'riwayat_panen_terakhir_id',
            ]);

            $table->bigInteger('pemilik_id')->after('id');
            $table->bigInteger('petani_id')->after('pemilik_id');
            $table->unsignedInteger('panen_terakhir_id')->nullable()->after('hasil_panen_ton');
            $table->foreign('pemilik_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('petani_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('panen_terakhir_id')->references('id')->on('panen_padi')->nullOnDelete();
            $table->index(['pemilik_id', 'status_verifikasi']);
            $table->index(['petani_id', 'status_verifikasi']);
        });

        Schema::dropIfExists('riwayat_panen');
        Schema::dropIfExists('lapor_panen');
        Schema::dropIfExists('siklus_pupuk');
        Schema::dropIfExists('riwayat_pemupukan');
        Schema::dropIfExists('siklus_tanam');
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Migrasi konsolidasi pertanian tidak dapat dibatalkan otomatis karena tabel lama telah digabungkan.'
        );
    }

    private function migrateKomunitas(): void
    {
        $anggota = DB::table('kelompok')->orderBy('id')->get();

        Schema::create('komunitas', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 20)->nullable()->unique();
            $table->string('jenis_komunitas', 30);
            $table->string('nama', 150);
            $table->string('nama_komunitas', 150)->nullable();
            $table->string('nomor_hp', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->string('status_keanggotaan', 20)->default('AKTIF');
            $table->unsignedBigInteger('komunitas_induk_id')->nullable();
            $table->timestamps();

            $table->index(['jenis_komunitas', 'status_keanggotaan']);
        });

        $idMap = [];
        foreach ($anggota as $item) {
            $idMap[(int) $item->id] = DB::table('komunitas')->insertGetId([
                'nik' => $item->nik,
                'jenis_komunitas' => $item->jenis_kelompok,
                'nama' => $item->nama,
                'nama_komunitas' => $item->nama_kelompok,
                'nomor_hp' => $item->nomor_hp,
                'alamat' => $item->alamat,
                'status_keanggotaan' => $item->status_keanggotaan,
                'komunitas_induk_id' => null,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ]);
        }

        foreach ($anggota as $item) {
            if (!$item->kelompok_tani_induk_id) {
                continue;
            }

            DB::table('komunitas')->where('id', $idMap[(int) $item->id])->update([
                'komunitas_induk_id' => $idMap[(int) $item->kelompok_tani_induk_id] ?? null,
            ]);
        }

        $indukDefault = DB::table('komunitas')
            ->where('jenis_komunitas', 'kelompok_tani')
            ->orderBy('id')
            ->value('id');
        DB::table('komunitas')
            ->where('jenis_komunitas', 'brigade_pangan')
            ->whereNull('komunitas_induk_id')
            ->update(['komunitas_induk_id' => $indukDefault]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_kelompok_id_foreign');
            $table->unsignedBigInteger('komunitas_id')->nullable()->after('role_id');
        });

        foreach ($idMap as $kelompokId => $komunitasId) {
            DB::table('users')->where('kelompok_id', $kelompokId)->update([
                'komunitas_id' => $komunitasId,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('kelompok_id');
            $table->foreign('komunitas_id')->references('id')->on('komunitas')->nullOnDelete();
        });

        Schema::table('komunitas', function (Blueprint $table) {
            $table->foreign('komunitas_induk_id')->references('id')->on('komunitas')->nullOnDelete();
        });

        Schema::table('kelompok', function (Blueprint $table) {
            $table->dropForeign('kelompok_induk_foreign');
        });
        Schema::drop('kelompok');
    }
};
