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
        $pendingCounts = $this->pendingCounts();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
            'pending_count' => $pendingCounts['total_pending'],
            'pending_counts' => $pendingCounts,
            'data' => $notifikasi,
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function index(Request $request)
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
        $pendingCounts = $this->pendingCounts();

        return response()->json([
            'success' => true,
            'unread_count' => $unreadCount,
            'pending_count' => $pendingCounts['total_pending'],
            'pending_counts' => $pendingCounts,
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
                'target_url' => ($notifikasi->target_url ?? null) ?: '/verifikasi-data-petani',
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

    private function pendingCounts(): array
    {
        try {
            $pendingLahan = DB::table('lahan_sawah')
                ->where('status_verifikasi', 'PENDING')
                ->count();

            $pendingPanen = DB::table('panen_padi')
                ->where('status_verifikasi', 'PENDING')
                ->count();

            return [
                'pending_lahan' => $pendingLahan,
                'pending_panen' => $pendingPanen,
                'total_pending' => $pendingLahan + $pendingPanen,
            ];
        } catch (\Throwable $e) {
            return [
                'pending_lahan' => 0,
                'pending_panen' => 0,
                'total_pending' => 0,
            ];
        }
    }
}
