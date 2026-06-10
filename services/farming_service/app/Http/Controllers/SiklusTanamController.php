<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SiklusTanam;
use App\Models\LahanSawah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SiklusTanamController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => SiklusTanam::with(['bibit', 'lahan'])->orderByDesc('id')->get()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'lahan_id' => 'required|integer',
            'bibit_id' => 'required|integer',
            'tanggal_tanam' => 'required|date',
            'estimasi_panen' => 'nullable',
            'tanggal_panen' => 'required|date',
            'hasil_panen' => 'required|numeric|min:0',
        ]);

        $user = $request->attributes->get('auth');

        $lahan = LahanSawah::where('id', $request->lahan_id)
            ->where('user_id', $user->sub)
            ->where('status_verifikasi', 'DITERIMA')
            ->first();

        if (!$lahan) {
            return response()->json([
                'success' => false,
                'message' => 'Lahan tidak valid atau belum diverifikasi petugas.'
            ], 403);
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
            'created_by' => $user->sub,
        ]);

        $this->buatNotifikasiPetugas(
            'Laporan Hasil Panen Baru',
            'Petani mengirim laporan panen untuk lahan ' . $lahan->nama_lahan . '. Segera lakukan verifikasi.'
        );

        return response()->json([
            'success' => true,
            'message' => 'Laporan hasil panen berhasil dikirim dan menunggu verifikasi petugas',
            'data' => $data
        ], 201);
    }

    public function show($id)
    {
        $data = SiklusTanam::with(['bibit', 'lahan'])->find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function update(Request $request, $id)
    {
        $data = SiklusTanam::find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        if ($data->status_verifikasi === 'DITERIMA') {
            return response()->json([
                'success' => false,
                'message' => 'Data yang sudah diverifikasi tidak boleh diubah'
            ], 400);
        }

        $request->validate([
            'lahan_id' => 'required|integer',
            'bibit_id' => 'required|integer',
            'tanggal_tanam' => 'required|date',
            'estimasi_panen' => 'nullable',
            'tanggal_panen' => 'required|date',
            'hasil_panen' => 'required|numeric|min:0',
        ]);

        $data->update([
            'lahan_id' => $request->lahan_id,
            'bibit_id' => $request->bibit_id,
            'tanggal_tanam' => $request->tanggal_tanam,
            'estimasi_panen' => $request->estimasi_panen,
            'tanggal_panen' => $request->tanggal_panen,
            'hasil_panen' => $request->hasil_panen,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Aktivitas tanam berhasil diperbarui',
            'data' => $data->fresh()
        ]);
    }

    public function totalProduksi(Request $request)
    {
        $user = $request->attributes->get('auth');
        $tahun = Carbon::now()->year;

        $total = SiklusTanam::where('created_by', $user->sub)
            ->where('status_verifikasi', 'DITERIMA')
            ->whereYear('tanggal_panen', $tahun)
            ->sum('hasil_panen');

        return response()->json([
            'success' => true,
            'data' => [
                'tahun' => $tahun,
                'total_produksi' => $total
            ]
        ]);
    }

    public function getPendingVerifications()
    {
        $pendingData = SiklusTanam::with(['bibit', 'lahan'])
            ->where('status_verifikasi', 'PENDING')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Antrean verifikasi hasil panen berhasil diambil',
            'data' => $pendingData
        ]);
    }

    public function approve($id)
    {
        $data = SiklusTanam::find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data hasil panen tidak ditemukan'
            ], 404);
        }

        if ($data->status_verifikasi === 'DITERIMA') {
            return response()->json([
                'success' => false,
                'message' => 'Data hasil panen sudah diterima sebelumnya'
            ], 400);
        }

        DB::transaction(function () use ($data) {
            $data->update([
                'status_verifikasi' => 'DITERIMA',
                'status_aktif' => 'NONAKTIF',
            ]);

            $this->hitungUlangProduktivitasLahan($data->lahan_id);
        });

        return response()->json([
            'success' => true,
            'message' => 'Hasil panen berhasil diterima dan masuk statistik'
        ]);
    }

    public function reject($id)
    {
        $data = SiklusTanam::find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data hasil panen tidak ditemukan'
            ], 404);
        }

        if ($data->status_verifikasi === 'DITOLAK') {
            return response()->json([
                'success' => false,
                'message' => 'Data hasil panen sudah ditolak sebelumnya'
            ], 400);
        }

        $data->update([
            'status_verifikasi' => 'DITOLAK'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Hasil panen berhasil ditolak'
        ]);
    }

    public function destroy($id)
    {
        $data = SiklusTanam::find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        if ($data->status_verifikasi === 'DITERIMA') {
            return response()->json([
                'success' => false,
                'message' => 'Data yang sudah diverifikasi tidak boleh dihapus'
            ], 400);
        }

        $data->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus'
        ]);
    }

    private function hitungUlangProduktivitasLahan(int $lahanId): void
    {
        $lahan = LahanSawah::find($lahanId);

        if (!$lahan) {
            return;
        }

        $totalPanen = SiklusTanam::where('lahan_id', $lahanId)
            ->where('status_verifikasi', 'DITERIMA')
            ->sum('hasil_panen');

        $luas = (float) $lahan->luas_lahan_hektar;
        $produktivitas = $luas > 0 ? $totalPanen / $luas : 0;

        $lahan->update([
            'hasil_panen_ton' => round($totalPanen, 2),
            'produktivitas_ton_ha' => round($produktivitas, 2),
        ]);
    }

    private function buatNotifikasiPetugas(string $judul, string $pesan): void
    {
        try {
            DB::table('notifikasi')->insert([
                'role_id_penerima' => 2,
                'user_id_penerima' => null,
                'judul' => $judul,
                'pesan' => $pesan,
                'is_read' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat notifikasi petugas: ' . $e->getMessage());
        }
    }
}