<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RiwayatPanenController extends Controller
{
    public function index(Request $request)
    {
        $auth = $request->attributes->get('auth');
        $userId = (int) ($auth->sub ?? $auth->id ?? 0);
        $roleId = (int) ($auth->role_id ?? $auth->role ?? 0);

        if (!$userId || !in_array($roleId, [1, 5], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Riwayat panen hanya tersedia untuk Kelompok Tani dan Brigade Pangan.',
            ], 403);
        }

        $query = DB::table('tanam_padi as tp')
            ->leftJoin('panen_padi as rp', 'rp.tanam_padi_id', '=', 'tp.id')
            ->leftJoin('lahan_sawah as ls', 'ls.id', '=', 'tp.lahan_id')
            ->leftJoin('users as pemilik', 'pemilik.id', '=', 'ls.pemilik_id')
            ->leftJoin('jenis_bibit as jb', 'jb.id', '=', 'tp.bibit_id')
            ->select([
                'tp.id as tanam_padi_id',
                'tp.lahan_id',
                'tp.bibit_id',
                'tp.tanggal_tanam',
                'tp.luas_tanam_hektar',
                'rp.id as panen_padi_id',
                'rp.tanggal_panen',
                'rp.hasil_panen_ton',
                'rp.status_verifikasi',
                'rp.catatan_verifikasi',
                'pemilik.nama_lengkap as nama_pemilik',
                'ls.nama_lahan',
                'ls.luas_lahan_hektar',
                'jb.nama_bibit',
                'jb.varietas'
            ]);

        if (in_array($roleId, [1, 5], true)) {
            $query->where('ls.pemilik_id', $userId);
        } else {
            // ...
        }

        $perPage = min(50, max(1, (int) $request->get('per_page', 10)));
        $data = $query
            ->orderByDesc(DB::raw('COALESCE(rp.tanggal_panen, tp.tanggal_tanam)'))
            ->orderByDesc('tp.id')
            ->paginate($perPage, ['*'], 'riwayat_page');

        $data->getCollection()->transform(function ($item) {
            $luasTanam = (float) ($item->luas_tanam_hektar ?? $item->luas_lahan_hektar);
            $hasilPanen = (float) $item->hasil_panen_ton;
            return [
                'id' => (int) ($item->panen_padi_id ?? $item->tanam_padi_id),
                'siklus_tanam_id' => (int) $item->tanam_padi_id,
                'tanam_padi_id' => (int) $item->tanam_padi_id,
                'lahan_id' => (int) $item->lahan_id,
                'bibit_id' => (int) $item->bibit_id,
                'tanggal_tanam' => $item->tanggal_tanam,
                'tanggal_panen' => $item->tanggal_panen,
                'hasil_panen' => $hasilPanen,
                'hasil_panen_ton' => $hasilPanen,
                'luas_lahan_hektar' => (float) $item->luas_lahan_hektar,
                'luas_tanam_hektar' => $luasTanam,
                'produktivitas_ton_ha' => $luasTanam > 0 ? round($hasilPanen / $luasTanam, 2) : 0,
                'status_aktif' => 'NONAKTIF',
                'status_verifikasi' => $item->panen_padi_id ? $item->status_verifikasi : 'BELUM_PANEN',
                'catatan_verifikasi' => $item->catatan_verifikasi ?? '',
                'nama_pemilik' => $item->nama_pemilik,
                'nama_penggarap' => null,
                'lahan' => [
                    'id' => (int) $item->lahan_id,
                    'nama_lahan' => $item->nama_lahan,
                    'luas_lahan_hektar' => (float) $item->luas_lahan_hektar,
                    'luas_tanam_hektar' => $luasTanam,
                ],
                'bibit' => [
                    'id' => (int) $item->bibit_id,
                    'nama_bibit' => $item->nama_bibit,
                    'varietas' => $item->varietas,
                ],
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }
}
