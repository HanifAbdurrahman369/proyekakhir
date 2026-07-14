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

        $query = DB::table('panen_padi as rp')
            ->leftJoin('users as pemilik', 'pemilik.id', '=', 'rp.pemilik_id')
            ->leftJoin('tanam_padi as tp', 'tp.id', '=', 'rp.tanam_padi_id')
            ->leftJoin('lahan_sawah as ls', 'ls.id', '=', 'tp.lahan_id')
            ->leftJoin('jenis_bibit as jb', 'jb.id', '=', 'tp.bibit_id')
            ->select([
                'rp.*',
                'pemilik.nama_lengkap as nama_pemilik',
                'tp.tanggal_tanam',
                'tp.lahan_id',
                'tp.bibit_id',
                'tp.luas_tanam_hektar',
                'ls.nama_lahan',
                'ls.luas_lahan_ha',
                'jb.nama_bibit',
                'jb.varietas'
            ]);

        if (in_array($roleId, [1, 5], true)) {
            $query->where('rp.pemilik_id', $userId);
        } else {
            // ...
        }

        $perPage = min(50, max(1, (int) $request->get('per_page', 10)));
        $data = $query
            ->orderByDesc('rp.tanggal_panen')
            ->orderByDesc('rp.id')
            ->paginate($perPage, ['*'], 'riwayat_page');

        $data->getCollection()->transform(fn ($item) => [
            'id' => (int) $item->id,
            'siklus_tanam_id' => (int) $item->tanam_padi_id,
            'tanam_padi_id' => (int) $item->tanam_padi_id,
            'lahan_id' => (int) $item->lahan_id,
            'bibit_id' => (int) $item->bibit_id,
            'tanggal_tanam' => $item->tanggal_tanam,
            'tanggal_panen' => $item->tanggal_panen,
            'hasil_panen' => (float) $item->hasil_panen_ton,
            'hasil_panen_ton' => (float) $item->hasil_panen_ton,
            'luas_lahan_hektar' => (float) $item->luas_lahan_ha,
            'luas_tanam_hektar' => (float) ($item->luas_tanam_hektar ?? $item->luas_lahan_ha),
            'produktivitas_ton_ha' => (float) $item->produktivitas_ton_ha,
            'status_aktif' => 'NONAKTIF',
            'status_verifikasi' => $item->status_verifikasi,
            'catatan_verifikasi' => $item->catatan_verifikasi ?? '',
            'nama_pemilik' => $item->nama_pemilik,
            'nama_penggarap' => null,
            'lahan' => [
                'id' => (int) $item->lahan_id,
                'nama_lahan' => $item->nama_lahan,
                'luas_lahan_hektar' => (float) $item->luas_lahan_ha,
                'luas_tanam_hektar' => (float) ($item->luas_tanam_hektar ?? $item->luas_lahan_ha),
            ],
            'bibit' => [
                'id' => (int) $item->bibit_id,
                'nama_bibit' => $item->nama_bibit,
                'varietas' => $item->varietas,
            ],
        ]);

        return response()->json(['success' => true, 'data' => $data]);
    }
}
