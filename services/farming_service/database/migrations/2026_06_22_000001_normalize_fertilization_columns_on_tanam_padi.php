<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tanam_padi', function (Blueprint $table) {
            $table->integer('pupuk_id')->nullable()->after('bibit_id');
            $table->date('tanggal_pemupukan')->nullable()->after('tanggal_tanam');
            $table->decimal('takaran_pupuk_kg', 10, 2)->nullable()->after('tanggal_pemupukan');
            $table->bigInteger('pemupukan_dicatat_oleh')->nullable()->after('takaran_pupuk_kg');
            $table->timestamp('pemupukan_dicatat_at')->nullable()->after('pemupukan_dicatat_oleh');
        });

        DB::table('tanam_padi')
            ->select('id', 'petani_id', 'created_at', 'riwayat_pemupukan')
            ->orderBy('id')
            ->each(function ($tanam) {
                $riwayat = json_decode((string) $tanam->riwayat_pemupukan, true);
                $pemupukan = is_array($riwayat) ? ($riwayat[0] ?? null) : null;

                if (!$pemupukan) {
                    throw new RuntimeException(
                        'Data tanam ID ' . $tanam->id . ' tidak memiliki rincian pemupukan yang dapat dimigrasikan.'
                    );
                }

                DB::table('tanam_padi')->where('id', $tanam->id)->update([
                    'pupuk_id' => $pemupukan['pupuk_id'],
                    'tanggal_pemupukan' => $pemupukan['tanggal_pemupukan'],
                    'takaran_pupuk_kg' => $pemupukan['takaran'],
                    'pemupukan_dicatat_oleh' => $pemupukan['dicatat_oleh'] ?? $tanam->petani_id,
                    'pemupukan_dicatat_at' => $pemupukan['dicatat_at'] ?? $tanam->created_at ?? now(),
                ]);
            });

        DB::statement('ALTER TABLE tanam_padi MODIFY pupuk_id INT NOT NULL');
        DB::statement('ALTER TABLE tanam_padi MODIFY tanggal_pemupukan DATE NOT NULL');
        DB::statement('ALTER TABLE tanam_padi MODIFY takaran_pupuk_kg DECIMAL(10,2) NOT NULL');
        DB::statement('ALTER TABLE tanam_padi MODIFY pemupukan_dicatat_oleh BIGINT NOT NULL');
        DB::statement('ALTER TABLE tanam_padi MODIFY pemupukan_dicatat_at TIMESTAMP NOT NULL');

        Schema::table('tanam_padi', function (Blueprint $table) {
            $table->foreign('pupuk_id')->references('id')->on('jenis_pupuk')->restrictOnDelete();
            $table->foreign('pemupukan_dicatat_oleh')->references('id')->on('users')->restrictOnDelete();
            $table->index(['pupuk_id', 'tanggal_pemupukan']);
            $table->dropColumn('riwayat_pemupukan');
        });
    }

    public function down(): void
    {
        Schema::table('tanam_padi', function (Blueprint $table) {
            $table->json('riwayat_pemupukan')->nullable()->after('status_verifikasi');
        });

        DB::table('tanam_padi')->orderBy('id')->each(function ($tanam) {
            DB::table('tanam_padi')->where('id', $tanam->id)->update([
                'riwayat_pemupukan' => json_encode([[
                    'id' => 1,
                    'pupuk_id' => $tanam->pupuk_id,
                    'tanggal_pemupukan' => $tanam->tanggal_pemupukan,
                    'takaran' => (float) $tanam->takaran_pupuk_kg,
                    'dicatat_oleh' => $tanam->pemupukan_dicatat_oleh,
                    'dicatat_at' => $tanam->pemupukan_dicatat_at,
                ]]),
            ]);
        });

        Schema::table('tanam_padi', function (Blueprint $table) {
            $table->dropForeign(['pupuk_id']);
            $table->dropForeign(['pemupukan_dicatat_oleh']);
            $table->dropIndex(['pupuk_id', 'tanggal_pemupukan']);
            $table->dropColumn([
                'pupuk_id',
                'tanggal_pemupukan',
                'takaran_pupuk_kg',
                'pemupukan_dicatat_oleh',
                'pemupukan_dicatat_at',
            ]);
        });
    }
};
