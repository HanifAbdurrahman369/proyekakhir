<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotifikasiController extends Controller
{
    /**
     * Mengambil daftar notifikasi untuk Petugas (Role 2)
     */
    public function getNotifikasiPetugas()
    {
        $notifikasi = DB::table('notifikasi')
            ->where('role_id_penerima', 2)
            ->where('is_read', 0) // Hanya ambil yang belum dibaca
            ->orderBy('created_at', 'desc')
            ->limit(10) // Batasi 10 terbaru agar ringan
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifikasi,
            'unread_count' => $notifikasi->count()
        ], 200);
    }

    /**
     * Menandai notifikasi telah dibaca
     */
    public function markAsRead($id)
    {
        DB::table('notifikasi')->where('id', $id)->update([
            'is_read' => 1,
            'updated_at' => now()
        ]);

        return response()->json(['success' => true, 'message' => 'Notifikasi ditandai dibaca'], 200);
    }
}