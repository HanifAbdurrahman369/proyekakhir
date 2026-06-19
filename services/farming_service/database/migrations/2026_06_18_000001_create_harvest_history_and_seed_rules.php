<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('jenis_bibit')->updateOrInsert(
            ['nama_bibit' => 'Siam Mayang'],
            ['varietas' => 'Lokal', 'masa_tanam_hari' => 270]
        );
        DB::table('jenis_bibit')->updateOrInsert(
            ['nama_bibit' => 'Inpara 3'],
            ['varietas' => 'Unggul', 'masa_tanam_hari' => 120]
        );

        Schema::table('siklus_tanam', function (Blueprint $table) {
            if (!Schema::hasColumn('siklus_tanam', 'estimasi_tanggal_panen')) {
                $table->date('estimasi_tanggal_panen')->nullable()->after('estimasi_panen');
            }
            if (!Schema::hasColumn('siklus_tanam', 'peran_pelapor')) {
                $table->string('peran_pelapor', 30)->nullable()->after('created_by');
            }
        });

        if (Schema::hasTable('lapor_panen')) {
            Schema::table('lapor_panen', function (Blueprint $table) {
                if (!Schema::hasColumn('lapor_panen', 'verified_by')) {
                    $table->bigInteger('verified_by')->nullable()->after('created_by');
                }
                if (!Schema::hasColumn('lapor_panen', 'verified_at')) {
                    $table->timestamp('verified_at')->nullable()->after('verified_by');
                }
            });
        }

        if (!Schema::hasTable('riwayat_panen')) {
            Schema::create('riwayat_panen', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('lapor_panen_id')->nullable()->unique();
                $table->integer('siklus_tanam_id');
                $table->integer('lahan_id');
                $table->integer('bibit_id');
                $table->bigInteger('pemilik_user_id');
                $table->bigInteger('penggarap_user_id')->nullable();
                $table->bigInteger('diverifikasi_oleh')->nullable();
                $table->string('nama_lahan', 150);
                $table->string('nama_bibit', 100);
                $table->string('varietas', 100)->nullable();
                $table->date('tanggal_tanam');
                $table->date('tanggal_panen');
                $table->decimal('hasil_panen_ton', 12, 2);
                $table->decimal('luas_lahan_ha', 12, 4)->default(0);
                $table->decimal('produktivitas_ton_ha', 12, 2)->default(0);
                $table->string('status_verifikasi', 20)->default('DITERIMA');
                $table->timestamp('diverifikasi_at')->nullable();
                $table->timestamps();

                $table->foreign('lapor_panen_id')->references('id')->on('lapor_panen')->nullOnDelete();
                $table->foreign('siklus_tanam_id')->references('id')->on('siklus_tanam')->cascadeOnDelete();
                $table->foreign('lahan_id')->references('id')->on('lahan_sawah')->cascadeOnDelete();
                $table->foreign('bibit_id')->references('id')->on('jenis_bibit')->restrictOnDelete();
                $table->index(['pemilik_user_id', 'tanggal_panen']);
                $table->index(['penggarap_user_id', 'tanggal_panen']);
            });
        }

        DB::statement("UPDATE siklus_tanam st
            JOIN jenis_bibit jb ON jb.id = st.bibit_id
            SET st.estimasi_tanggal_panen = DATE_ADD(st.tanggal_tanam, INTERVAL jb.masa_tanam_hari DAY)
            WHERE st.estimasi_tanggal_panen IS NULL");

        DB::statement("UPDATE siklus_tanam st
            LEFT JOIN users u ON u.id = st.created_by
            SET st.peran_pelapor = CASE WHEN u.role_id = 5 THEN 'brigade_pangan' ELSE 'kelompok_tani' END
            WHERE st.peran_pelapor IS NULL");

        $panenLama = DB::table('siklus_tanam as st')
            ->join('lahan_sawah as ls', 'ls.id', '=', 'st.lahan_id')
            ->join('jenis_bibit as jb', 'jb.id', '=', 'st.bibit_id')
            ->where('st.status_verifikasi', 'DITERIMA')
            ->whereNotNull('st.tanggal_panen')
            ->whereNotNull('st.hasil_panen')
            ->select([
                'st.id as siklus_id',
                'st.lahan_id',
                'st.bibit_id',
                'st.created_by',
                'st.verified_by',
                'st.verified_at',
                'st.tanggal_tanam',
                'st.tanggal_panen',
                'st.hasil_panen',
                'ls.user_id as pemilik_user_id',
                'ls.nama_lahan',
                'ls.luas_lahan_hektar',
                'jb.nama_bibit',
                'jb.varietas',
            ])
            ->get();

        foreach ($panenLama as $panen) {
            $luas = (float) ($panen->luas_lahan_hektar ?? 0);
            $hasil = (float) $panen->hasil_panen;
            DB::table('riwayat_panen')->updateOrInsert(
                ['siklus_tanam_id' => $panen->siklus_id],
                [
                    'lapor_panen_id' => null,
                    'lahan_id' => $panen->lahan_id,
                    'bibit_id' => $panen->bibit_id,
                    'pemilik_user_id' => $panen->pemilik_user_id,
                    'penggarap_user_id' => $panen->created_by,
                    'diverifikasi_oleh' => $panen->verified_by,
                    'nama_lahan' => $panen->nama_lahan,
                    'nama_bibit' => $panen->nama_bibit,
                    'varietas' => $panen->varietas,
                    'tanggal_tanam' => $panen->tanggal_tanam,
                    'tanggal_panen' => $panen->tanggal_panen,
                    'hasil_panen_ton' => $hasil,
                    'luas_lahan_ha' => $luas,
                    'produktivitas_ton_ha' => $luas > 0 ? round($hasil / $luas, 2) : 0,
                    'status_verifikasi' => 'DITERIMA',
                    'diverifikasi_at' => $panen->verified_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_panen');

        if (Schema::hasTable('lapor_panen')) {
            Schema::table('lapor_panen', function (Blueprint $table) {
                $columns = array_values(array_filter(
                    ['verified_by', 'verified_at'],
                    fn (string $column) => Schema::hasColumn('lapor_panen', $column)
                ));
                if ($columns) {
                    $table->dropColumn($columns);
                }
            });
        }

        Schema::table('siklus_tanam', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['estimasi_tanggal_panen', 'peran_pelapor'],
                fn (string $column) => Schema::hasColumn('siklus_tanam', $column)
            ));
            if ($columns) {
                $table->dropColumn($columns);
            }
        });

        DB::table('jenis_bibit')->where('nama_bibit', 'Inpara 3')->delete();
        DB::table('jenis_bibit')->where('nama_bibit', 'Siam Mayang')->update(['masa_tanam_hari' => 120]);
    }
};
