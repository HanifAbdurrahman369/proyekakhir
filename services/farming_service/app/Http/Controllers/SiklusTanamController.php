<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SiklusTanam;
use App\Models\LahanSawah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Firebase\JWT\JWT;
use Carbon\Carbon;

class SiklusTanamController extends Controller
{
    /**
     * LIST DATA
     */
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => SiklusTanam::all()
        ]);
    }

    /**
     * INPUT OLEH PETANI
     */
public function store(Request $request)
{
    $request->validate([
        'lahan_id' => 'required|integer',
        'bibit_id' => 'required|integer',
        'tanggal_tanam' => 'required|date',
        'tanggal_panen' => 'required|date',
        'hasil_panen' => 'required|numeric',
    ]);

    // 🔥 AMBIL USER DARI FIREBASE JWT MIDDLEWARE
    $user = $request->attributes->get('auth');

    if (!$user || !isset($user->sub)) {
        return response()->json([
            'success' => false,
            'message' => 'User tidak ditemukan dari token JWT'
        ], 401);
    }

    $data = SiklusTanam::create([
        'lahan_id' => $request->lahan_id,
        'bibit_id' => $request->bibit_id,
        'tanggal_tanam' => $request->tanggal_tanam,
        'estimasi_panen' => $request->estimasi_panen,
        'tanggal_panen' => $request->tanggal_panen,
        'hasil_panen' => $request->hasil_panen,
        'status_aktif' => 'AKTIF',
        'status_verifikasi' => 'PENDING',
        'created_by' => $user->sub, // 🔥 dari JWT payload
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Aktivitas berhasil disimpan',
        'data' => $data
    ], 201);

    try {
        $namaPetani = auth()->user()->name ?? 'Seorang Petani';
        $namaLahan = \Illuminate\Support\Facades\DB::table('lahan_sawah')
                        ->where('id', $request->lahan_id)->value('nama_lahan') ?? 'Tidak diketahui';

        \Illuminate\Support\Facades\DB::table('notifikasi')->insert([
            'role_id_penerima' => 2, // 2 = Mengarah ke semua Petugas
            'user_id_penerima' => null, // Broadcast
            'judul' => 'Laporan Panen Baru',
            'pesan' => "Petani {$namaPetani} mengirimkan laporan panen untuk lahan {$namaLahan}. Segera lakukan verifikasi.",
            'is_read' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    } catch (\Exception $e) {
        // Abaikan error notifikasi agar tidak mengganggu proses simpan utama petani
        \Illuminate\Support\Facades\Log::error('Gagal membuat notifikasi: ' . $e->getMessage());
    }
}
  /**
     * DETAIL DATA
     */
    public function show($id)
    {
        $data = SiklusTanam::with([
            'bibit',
            'lahan'
        ])->find($id);

        if (!$data) {

            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }

    public function riwayatPanen(Request $request)
    {
        $limit = $request->get('limit', 5);

        $data = SiklusTanam::with([
                'lahan:id,nama_lahan,luas_lahan_hektar',
                'bibit:id,nama_bibit'
            ])
            ->latest()
            ->take($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'lahan_id' => $item->lahan_id,
                    'bibit_id' => $item->bibit_id,
                    'tanggal_tanam' => $item->tanggal_tanam,
                    'tanggal_panen' => $item->tanggal_panen,
                    'estimasi_panen' => $item->estimasi_panen,
                    'hasil_panen' => $item->hasil_panen,
                    'status_aktif' => $item->status_aktif,
                    'status_verifikasi' => $item->status_verifikasi,
                    'created_by' => $item->created_by,
                    'lahan' => $item->lahan ? [
                        'id' => $item->lahan->id,
                        'nama_lahan' => $item->lahan->nama_lahan,
                        'luas_lahan_hektar' => $item->lahan->luas_lahan_hektar,
                    ] : null,
                    'bibit' => $item->bibit ? [
                        'id' => $item->bibit->id,
                        'nama_bibit' => $item->bibit->nama_bibit,
                    ] : null,
                ];
            })
            ->values()
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * UPDATE DATA AKTIVITAS TANAM
     */
    public function update(Request $request, $id)
    {
        $data = SiklusTanam::find($id);

        if (!$data) {

            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        /**
         * CEGAH UPDATE JIKA SUDAH DIVERIFIKASI
         */
        if ($data->status_verifikasi == 'DITERIMA') {

            return response()->json([
                'success' => false,
                'message' => 'Data yang sudah diverifikasi tidak boleh diubah'
            ], 400);
        }

        $request->validate([

            'lahan_id' => 'required|integer',
            'bibit_id' => 'required|integer',

            'tanggal_tanam' => 'required|date',

            'estimasi_panen' => 'required|numeric',

            'tanggal_panen' => 'required|date',

            'hasil_panen' => 'required|numeric',
            'created_by' => 'required|integer',
        ]);

        $data->update([

            'lahan_id' => $request->lahan_id,
            'bibit_id' => $request->bibit_id,

            'tanggal_tanam' => $request->tanggal_tanam,
            'estimasi_panen' => $request->estimasi_panen,
            'tanggal_panen' => $request->tanggal_panen,

            'hasil_panen' => $request->hasil_panen,
            'created_by' => $request->created_by,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Aktivitas tanam berhasil diperbarui',
            'data' => $data
        ], 200);
    }

    /**
     * APPROVE OLEH PETUGAS
     */
    public function approve($id)
    {
        $data = SiklusTanam::find($id);

        if (!$data) {

            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        /**
         * CEGAH DOUBLE APPROVE
         */
        if ($data->status_verifikasi == 'DITERIMA') {

            return response()->json([
                'success' => false,
                'message' => 'Data sudah diverifikasi'
            ], 400);
        }

        /**
         * UPDATE STATUS VERIFIKASI
         */
        $data->update([
            'status_verifikasi' => 'DITERIMA'
        ]);

        /**
         * AMBIL DATA LAHAN
         */
        $lahan = LahanSawah::find($data->lahan_id);

        /**
         * UPDATE TOTAL HASIL PANEN
         * TOTAL = AKUMULASI SELURUH MUSIM
         */
        if ($lahan) {

            $lahan->increment(
                'total_hasil_panen_ton',
                $data->hasil_panen
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Aktivitas berhasil diverifikasi'
        ], 200);
    }

    /**
     * REJECT OLEH PETUGAS
     */
    public function reject($id)
    {
        $data = SiklusTanam::find($id);

        if (!$data) {

            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        /**
         * UPDATE STATUS MENJADI DITOLAK
         */
        $data->update([
            'status_verifikasi' => 'DITOLAK'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Aktivitas ditolak'
        ], 200);
    }

    /**
     * HAPUS DATA
     */
    public function destroy($id)
    {
        $data = SiklusTanam::find($id);

        if (!$data) {

            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        /**
         * CEGAH HAPUS DATA YANG SUDAH DIVERIFIKASI
         */
        if ($data->status_verifikasi == 'DITERIMA') {

            return response()->json([
                'success' => false,
                'message' => 'Data yang sudah diverifikasi tidak boleh dihapus'
            ], 400);
        }

        $data->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus'
        ], 200);
    }
    public function getPendingVerifications()
    {
        // Menarik data siklus tanam yang statusnya belum DITERIMA atau DITOLAK
        // Memuat relasi data tabel bibit dan lahan sawah agar detailnya terlihat oleh Petugas
        $pendingData = SiklusTanam::with(['bibit', 'lahan'])
            ->where('status_verifikasi', 'PENDING')
            ->orWhereNull('status_verifikasi') // Antisipasi jika default database kosong
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil antrean verifikasi tanam',
            'data' => $pendingData
        ], 200);
    }
}