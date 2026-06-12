<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotifikasiController extends Controller
{
    public function getNotifikasiPetugas(Request $request)
    {
        $query = DB::table('notifikasi')
            ->where('role_id_penerima', 2);

        $notifikasi = (clone $query)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $unreadCount = (clone $query)
            ->where('is_read', 0)
            ->count();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
            'data' => $notifikasi,
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function index(Request $request)
    {
        $notifikasi = DB::table('notifikasi')
            ->where('role_id_penerima', 2)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifikasi,
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function show($id)
    {
        $notifikasi = DB::table('notifikasi')->where('id', $id)->first();

        if (!$notifikasi) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi tidak ditemukan.',
            ], 404);
        }

        DB::table('notifikasi')
            ->where('id', $id)
            ->update([
                'is_read' => 1,
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $notifikasi->id,
                'judul' => $notifikasi->judul,
                'pesan' => $notifikasi->pesan,
                'ref_type' => $notifikasi->ref_type ?? null,
                'ref_id' => $notifikasi->ref_id ?? null,
                'target_url' => $notifikasi->target_url ?: '/verifikasi-data-petani',
                'is_read' => 1,
            ],
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function markAsRead($id)
    {
        DB::table('notifikasi')
            ->where('id', $id)
            ->update([
                'is_read' => 1,
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sudah dibaca.',
        ]);
    }
}
