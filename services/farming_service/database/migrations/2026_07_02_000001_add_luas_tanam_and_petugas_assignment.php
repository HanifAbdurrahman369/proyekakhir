<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lahan_sawah', function (Blueprint $table) {
            if (!Schema::hasColumn('lahan_sawah', 'assigned_petugas_id')) {
                $table->unsignedBigInteger('assigned_petugas_id')->nullable()->after('petani_id');
                $table->index('assigned_petugas_id', 'lahan_assigned_petugas_index');
            }

            if (!Schema::hasColumn('lahan_sawah', 'luas_tanam_hektar')) {
                $table->decimal('luas_tanam_hektar', 12, 4)->nullable()->after('luas_lahan_hektar');
            }
        });

        Schema::table('tanam_padi', function (Blueprint $table) {
            if (!Schema::hasColumn('tanam_padi', 'luas_tanam_hektar')) {
                $table->decimal('luas_tanam_hektar', 12, 4)->default(0)->after('lahan_id');
            }
        });

        Schema::table('panen_padi', function (Blueprint $table) {
            if (!Schema::hasColumn('panen_padi', 'luas_tanam_hektar')) {
                $table->decimal('luas_tanam_hektar', 12, 4)->default(0)->after('luas_lahan_ha');
            }
        });

        $dummyLuasTanam = [
            2 => 4.80,
            3 => 0.50,
            4 => 1.50,
            5 => 0.75,
            6 => 1.40,
            7 => 0.60,
        ];

        foreach ($dummyLuasTanam as $id => $luasTanam) {
            DB::table('lahan_sawah')
                ->where('id', $id)
                ->whereNull('luas_tanam_hektar')
                ->update(['luas_tanam_hektar' => $luasTanam]);
        }

        DB::table('lahan_sawah')
            ->whereNull('luas_tanam_hektar')
            ->update(['luas_tanam_hektar' => DB::raw('luas_lahan_hektar')]);

        DB::statement(
            'UPDATE tanam_padi tp
             JOIN lahan_sawah ls ON ls.id = tp.lahan_id
             SET tp.luas_tanam_hektar = COALESCE(NULLIF(ls.luas_tanam_hektar, 0), ls.luas_lahan_hektar)
             WHERE tp.luas_tanam_hektar = 0'
        );

        DB::statement(
            'UPDATE panen_padi pp
             LEFT JOIN tanam_padi tp ON tp.id = pp.tanam_padi_id
             SET pp.luas_tanam_hektar = COALESCE(NULLIF(tp.luas_tanam_hektar, 0), pp.luas_lahan_ha)
             WHERE pp.luas_tanam_hektar = 0'
        );

        $petugas = DB::table('users')
            ->where('role_id', 2)
            ->select('id', 'wilayah_kecamatan_id', 'wilayah_kelurahan_ids')
            ->get();

        $lahan = DB::table('lahan_sawah')
            ->whereNull('assigned_petugas_id')
            ->select('id', 'kecamatan_id', 'kelurahan_id')
            ->get();

        foreach ($lahan as $row) {
            $assigned = $petugas->first(function ($user) use ($row) {
                $kelurahanIds = json_decode((string) $user->wilayah_kelurahan_ids, true);
                if (is_array($kelurahanIds) && in_array((int) $row->kelurahan_id, array_map('intval', $kelurahanIds), true)) {
                    return true;
                }

                return (int) ($user->wilayah_kecamatan_id ?? 0) > 0
                    && (int) $user->wilayah_kecamatan_id === (int) $row->kecamatan_id;
            });

            if ($assigned) {
                DB::table('lahan_sawah')
                    ->where('id', $row->id)
                    ->update(['assigned_petugas_id' => $assigned->id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('panen_padi', function (Blueprint $table) {
            if (Schema::hasColumn('panen_padi', 'luas_tanam_hektar')) {
                $table->dropColumn('luas_tanam_hektar');
            }
        });

        Schema::table('tanam_padi', function (Blueprint $table) {
            if (Schema::hasColumn('tanam_padi', 'luas_tanam_hektar')) {
                $table->dropColumn('luas_tanam_hektar');
            }
        });

        Schema::table('lahan_sawah', function (Blueprint $table) {
            if (Schema::hasColumn('lahan_sawah', 'assigned_petugas_id')) {
                $table->dropIndex('lahan_assigned_petugas_index');
                $table->dropColumn('assigned_petugas_id');
            }

            if (Schema::hasColumn('lahan_sawah', 'luas_tanam_hektar')) {
                $table->dropColumn('luas_tanam_hektar');
            }
        });
    }
};
