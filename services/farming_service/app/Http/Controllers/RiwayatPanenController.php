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
            ->join('tanam_padi as tp', 'rp.tanam_padi_id', '=', 'tp.id')
            ->join('lahan_sawah as ls', 'tp.lahan_id', '=', 'ls.id')
            ->join('jenis_bibit as jb', 'tp.bibit_id', '=', 'jb.id')
            ->leftJoin('users as pemilik', 'pemilik.id', '=', 'rp.pemilik_id')
            ->select([
                'rp.id',
                'rp.tanam_padi_id',
                'rp.pemilik_id',
                'rp.tanggal_panen',
                'rp.hasil_panen_ton',
                'rp.status_verifikasi',
                'rp.catatan_verifikasi',
                'pemilik.nama_lengkap as nama_pemilik',
                'tp.lahan_id',
                'tp.bibit_id',
                'tp.tanggal_tanam',
                'tp.luas_tanam_hektar',
                'ls.nama_lahan',
                'ls.luas_lahan_ha as luas_lahan_hektar',
                'jb.nama_bibit',
                'jb.varietas'
            ])
            ->whereDate('rp.tanggal_panen', '<=', now()->toDateString());

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
            'luas_lahan_hektar' => (float) $item->luas_lahan_hektar,
            'luas_tanam_hektar' => (float) ($item->luas_tanam_hektar ?? $item->luas_lahan_hektar),
            'produktivitas_ton_ha' => (float) (($item->luas_tanam_hektar ?? $item->luas_lahan_hektar) > 0 ? round($item->hasil_panen_ton / ($item->luas_tanam_hektar ?? $item->luas_lahan_hektar), 2) : 0),
            'status_aktif' => 'NONAKTIF',
            'status_verifikasi' => $item->status_verifikasi,
            'catatan_verifikasi' => $item->catatan_verifikasi ?? '',
            'nama_pemilik' => $item->nama_pemilik,
            'nama_penggarap' => null,
            'lahan' => [
                'id' => (int) $item->lahan_id,
                'nama_lahan' => $item->nama_lahan,
                'luas_lahan_hektar' => (float) $item->luas_lahan_hektar,
                'luas_tanam_hektar' => (float) ($item->luas_tanam_hektar ?? $item->luas_lahan_hektar),
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
