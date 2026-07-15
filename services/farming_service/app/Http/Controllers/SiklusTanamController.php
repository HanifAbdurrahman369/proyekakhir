<?php

namespace App\Http\Controllers;

use App\Models\LahanSawah;
use App\Models\LaporPanen;
use App\Models\SiklusTanam;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SiklusTanamController extends Controller
{
    private const ROLE_KELOMPOK_TANI = 1;
    private const ROLE_PETUGAS = 2;
    private const ROLE_BRIGADE_PANGAN = 5;

    public function index(Request $request)
    {
        [$userId, $roleId] = $this->authUser($request);
        if (!$this->rolePetani($roleId)) {
            return $this->forbidden('Akses aktivitas tanam tidak diizinkan.');
        }

        $query = SiklusTanam::with(['bibit', 'pupuk', 'lahan.pemilik', 'panen'])->orderByDesc('id');
        $this->batasiTanamUntukPetani($query, $userId, $roleId);

        return response()->json([
            'success' => true,
            'data' => $query->get()->map(fn (SiklusTanam $item) => $this->formatTanam($item, $userId, $roleId)),
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function store(Request $request)
    {
        $request->validate([
            'lahan_id' => 'required|integer',
            'luas_tanam_hektar' => 'required|numeric|min:0.01',
            'bibit_id' => 'required|integer',
            'tanggal_tanam' => 'required|date|before_or_equal:today',
            'estimasi_hari_tanam' => 'nullable|integer|min:1|max:730',
            'pupuk_id' => 'required|integer',
            'tanggal_pemupukan' => 'required|date|before_or_equal:today',
            'takaran' => 'required|numeric|min:0.01',
        ]);

        [$userId, $roleId] = $this->authUser($request);
        if (!$this->rolePetani($roleId)) {
            return $this->forbidden('Hanya Kelompok Tani dan Brigade Pangan yang dapat membuat laporan tanam.');
        }

        $lahan = $this->lahanTanamYangDiizinkan($userId, $roleId, (int) $request->lahan_id);
        if (!$lahan) {
            return $this->forbidden('Lahan tidak terdaftar untuk petani ini atau belum disetujui.');
        }

        $luasTanam = (float) $request->luas_tanam_hektar;
        if ($luasTanam > (float) $lahan->luas_lahan_hektar) {
            return response()->json([
                'success' => false,
                'message' => 'Luas tanam tidak boleh lebih besar dari luas lahan.',
            ], 422);
        }

        $aturan = $this->validasiAturanTanam($roleId, (int) $request->bibit_id, $request->tanggal_tanam, $lahan, $userId);
        if ($aturan['error']) {
            return response()->json(['success' => false, 'message' => $aturan['error']], 422);
        }

        if (SiklusTanam::where('lahan_id', $lahan->id)->where('status_aktif', 'AKTIF')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Lahan masih memiliki proses tanam aktif. Selesaikan proses panen terlebih dahulu.',
            ], 422);
        }

        $bibit = $aturan['bibit'];
        $tanggalTanam = Carbon::parse($request->tanggal_tanam);
        $tanggalPemupukan = Carbon::parse($request->tanggal_pemupukan);
        if ($tanggalPemupukan->lt($tanggalTanam)) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal pemupukan tidak boleh sebelum tanggal tanam.',
            ], 422);
        }

        $pupuk = DB::table('jenis_pupuk')->where('id', $request->pupuk_id)->first();
        if (!$pupuk) {
            return response()->json(['success' => false, 'message' => 'Jenis pupuk tidak ditemukan.'], 422);
        }

        $estimasiHari = (int) ($request->input('estimasi_hari_tanam') ?: ($bibit->estimasi_hari_min ?? $bibit->masa_tanam_hari));
        $estimasiHariMax = (int) ($bibit->estimasi_hari_max ?? $estimasiHari);

        $data = DB::transaction(function () use (
            $lahan,
            $bibit,
            $userId,
            $tanggalTanam,
            $tanggalPemupukan,
            $estimasiHari,
            $estimasiHariMax,
            $pupuk,
            $luasTanam,
            $request
        ) {
            $tanam = SiklusTanam::create([
                'petani_id' => $userId,
                'lahan_id' => $lahan->id,
                'luas_tanam_hektar' => $luasTanam,
                'bibit_id' => $bibit->id,
                'pupuk_id' => $pupuk->id,
                'tanggal_tanam' => $tanggalTanam->toDateString(),
                'tanggal_pemupukan' => $tanggalPemupukan->toDateString(),
                'takaran_pupuk_kg' => $request->takaran,
                'pemupukan_dicatat_oleh' => $userId,
                'pemupukan_dicatat_at' => now(),
                'estimasi_hari' => $estimasiHari,
                'estimasi_tanggal_panen' => $tanggalTanam->copy()->addDays($estimasiHari)->toDateString(),
                'estimasi_tanggal_panen_akhir' => $tanggalTanam->copy()->addDays($estimasiHariMax)->toDateString(),
                'status_aktif' => 'AKTIF',
                'status_verifikasi' => 'PENDING',
            ]);

            DB::table('lahan_sawah')->where('id', $lahan->id)->update([
                'luas_tanam_hektar' => $luasTanam,
                'updated_at' => now(),
            ]);

            return $tanam;
        });

        return response()->json([
            'success' => true,
            'message' => 'Laporan tanam dan pemupukan awal berhasil disimpan bersamaan.',
            'data' => $this->formatTanam($data->load(['bibit', 'pupuk', 'lahan.pemilik', 'panen']), $userId, $roleId),
        ], 201, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function storeLaporPanen(Request $request)
    {
        $request->validate([
            'siklus_tanam_id' => 'required|integer',
            'tanggal_panen' => 'required|date|before_or_equal:today',
            'hasil_panen' => 'required|numeric|min:0.01',
        ]);

        [$userId, $roleId] = $this->authUser($request);
        if (!$this->rolePetani($roleId)) {
            return $this->forbidden('Laporan hasil panen hanya dapat dibuat oleh Kelompok Tani atau Brigade Pangan sebagai pemilik lahan.');
        }

        $tanam = SiklusTanam::with(['lahan', 'bibit'])
            ->where('id', $request->siklus_tanam_id)
            ->where('status_aktif', 'AKTIF')
            ->whereHas('lahan', fn ($query) => $query->where('pemilik_id', $userId))
            ->first();

        if (!$tanam) {
            return $this->forbidden('Data tanam tidak ditemukan, sudah selesai, atau lahan bukan milik Anda.');
        }

        $tanggalPanen = Carbon::parse($request->tanggal_panen);

        try {
            $laporan = LaporPanen::where('tanam_padi_id', $tanam->id)->first();
        if ($laporan && $laporan->status_verifikasi !== 'DITOLAK') {
            return response()->json([
                'success' => false,
                'message' => 'Proses tanam ini sudah memiliki laporan panen yang sedang atau telah diverifikasi.',
            ], 422);
        }

        $luas = (float) ($tanam->luas_tanam_hektar ?: $tanam->lahan->luas_tanam_hektar ?: $tanam->lahan->luas_lahan_hektar);
        $hasil = (float) $request->hasil_panen;
        $payload = [
            'tanam_padi_id' => $tanam->id,
            'lahan_id' => $tanam->lahan_id,
            'bibit_id' => $tanam->bibit_id,
            'pemilik_id' => $tanam->lahan->pemilik_id,
            'petani_id' => $userId,
            'nama_lahan' => $tanam->lahan->nama_lahan,
            'nama_bibit' => $tanam->bibit->nama_bibit,
            'varietas' => $tanam->bibit->varietas,
            'tanggal_tanam' => $tanam->tanggal_tanam,
            'tanggal_panen' => $tanggalPanen->toDateString(),
            'hasil_panen_ton' => $hasil,
            'status_verifikasi' => 'PENDING',
        ];

        $laporan = $laporan
            ? tap($laporan)->update($payload)
            : LaporPanen::create($payload);

        $this->buatNotifikasiPetugas(
            'Laporan Panen Baru',
            'Kelompok Tani/Brigade Pangan mengirim laporan panen untuk lahan ' . $tanam->lahan->nama_lahan . '.',
            'panen_padi',
            (int) $laporan->id,
            '/verifikasi-data-petani?tipe=panen&id=' . $laporan->id,
            $tanam->lahan->assigned_petugas_id ? (int) $tanam->lahan->assigned_petugas_id : null
        );

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Laporan panen berhasil dikirim dan menunggu verifikasi petugas.',
            'data' => $this->ambilDetailHasilPanen((int) $laporan->id),
        ], $laporan->wasRecentlyCreated ? 201 : 200);
    }

    public function updateLaporPanen(Request $request, $id)
    {
        $request->validate([
            'tanggal_panen' => 'required|date|before_or_equal:today',
            'hasil_panen' => 'required|numeric|min:0.01',
        ]);

        [$userId, $roleId] = $this->authUser($request);
        if (!$this->rolePetani($roleId)) {
            return $this->forbidden('Perbaikan laporan panen hanya dapat dilakukan Kelompok Tani atau Brigade Pangan.');
        }

        $laporan = LaporPanen::with('siklusTanam')
            ->where('id', $id)
            ->where('pemilik_id', $userId)
            ->first();
        if (!$laporan) {
            return response()->json(['success' => false, 'message' => 'Laporan panen tidak ditemukan atau akses ditolak.'], 404);
        }

        $tanggalPanen = Carbon::parse($request->tanggal_panen);

        $hasil = (float) $request->hasil_panen;
        $luas = (float) ($laporan->siklusTanam->luas_tanam_hektar ?: $laporan->siklusTanam->lahan->luas_tanam_hektar ?: $laporan->siklusTanam->lahan->luas_lahan_hektar);
        $laporan->update([
            'tanggal_panen' => $tanggalPanen->toDateString(),
            'hasil_panen_ton' => $hasil,
            'produktivitas_ton_ha' => $luas > 0 ? round($hasil / $luas, 2) : 0,
            'status_verifikasi' => 'PENDING',
            'catatan_verifikasi' => null,
            'diverifikasi_oleh' => null,
            'diverifikasi_at' => null,
        ]);

        $this->buatNotifikasiPetugas(
            'Perbaikan Laporan Panen',
            'Kelompok Tani/Brigade Pangan mengajukan ulang laporan panen yang telah diperbaiki.',
            'panen_padi',
            (int) $laporan->id,
            '/verifikasi-data-petani?tipe=panen&id=' . $laporan->id,
            $this->assignedPetugasLahan((int) $laporan->lahan_id)
        );

        return response()->json([
            'success' => true,
            'message' => 'Laporan panen berhasil diperbaiki dan diajukan ulang.',
            'data' => $this->ambilDetailHasilPanen((int) $laporan->id),
        ]);
    }

    public function showLaporPanen(Request $request, $id)
    {
        [$userId, $roleId] = $this->authUser($request);
        $query = $this->queryHasilPanen()->where('pp.id', $id);
        if ($roleId === self::ROLE_KELOMPOK_TANI) {
            $query->where('pp.pemilik_id', $userId);
        } elseif ($roleId === self::ROLE_BRIGADE_PANGAN) {
            $query->where('pp.pemilik_id', $userId);
        } elseif ($roleId === self::ROLE_PETUGAS) {
            $this->applyPetugasWilayahScope($query, $userId);
        } else {
            return $this->forbidden('Detail laporan panen tidak dapat diakses.');
        }

        $row = $query->first();
        return $row
            ? response()->json(['success' => true, 'data' => $this->formatHasilPanen($row)])
            : response()->json(['success' => false, 'message' => 'Laporan panen tidak ditemukan.'], 404);
    }

    public function show(Request $request, $id)
    {
        [$userId, $roleId] = $this->authUser($request);
        $query = SiklusTanam::with(['bibit', 'pupuk', 'lahan.pemilik', 'panen'])->where('id', $id);
        $this->batasiTanamUntukPetani($query, $userId, $roleId);
        $data = $query->first();

        return $data
            ? response()->json(['success' => true, 'data' => $this->formatTanam($data, $userId, $roleId)])
            : response()->json(['success' => false, 'message' => 'Data tanam tidak ditemukan.'], 404);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'lahan_id' => 'required|integer',
            'luas_tanam_hektar' => 'required|numeric|min:0.01',
            'bibit_id' => 'required|integer',
            'tanggal_tanam' => 'required|date|before_or_equal:today',
            'estimasi_hari_tanam' => 'nullable|integer|min:1|max:730',
            'pupuk_id' => 'required|integer',
            'tanggal_pemupukan' => 'required|date|before_or_equal:today',
            'takaran' => 'required|numeric|min:0.01',
        ]);

        [$userId, $roleId] = $this->authUser($request);
        if (!$this->rolePetani($roleId)) {
            return $this->forbidden('Anda tidak memiliki akses untuk mengubah laporan tanam.');
        }

        $query = SiklusTanam::where('id', $id);
        $this->batasiTanamUntukPetani($query, $userId, $roleId);
        $data = $query->first();
        
        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Data tanam tidak ditemukan atau Anda tidak memiliki akses.'], 404);
        }
        if ($data->status_aktif === 'NONAKTIF') {
            return response()->json(['success' => false, 'message' => 'Data tanam yang sudah tidak aktif (NONAKTIF) tidak boleh diubah.'], 400);
        }

        $lahan = $this->lahanTanamYangDiizinkan($userId, $roleId, (int) $request->lahan_id);
        if (!$lahan) {
            return response()->json(['success' => false, 'message' => 'Lahan tidak tersedia atau tidak diizinkan.'], 422);
        }

        $aturan = $this->validasiAturanTanam($roleId, (int) $request->bibit_id, $request->tanggal_tanam, $lahan, $userId);
        if ($aturan['error']) {
            return response()->json(['success' => false, 'message' => $aturan['error']], 422);
        }

        $luasTanam = (float) $request->luas_tanam_hektar;
        if ($luasTanam > (float) $lahan->luas_lahan_hektar) {
            return response()->json([
                'success' => false,
                'message' => 'Luas tanam tidak boleh lebih besar dari luas lahan.',
            ], 422);
        }

        $tanggalTanam = Carbon::parse($request->tanggal_tanam);
        $tanggalPemupukan = Carbon::parse($request->tanggal_pemupukan);
        if ($tanggalPemupukan->lt($tanggalTanam)) {
            return response()->json(['success' => false, 'message' => 'Tanggal pemupukan tidak boleh sebelum tanggal tanam.'], 422);
        }

        $pupuk = DB::table('jenis_pupuk')->where('id', $request->pupuk_id)->first();
        if (!$pupuk) {
            return response()->json(['success' => false, 'message' => 'Jenis pupuk tidak ditemukan.'], 422);
        }

        $estimasiHari = (int) ($request->input('estimasi_hari_tanam') ?: ($aturan['bibit']->estimasi_hari_min ?? $aturan['bibit']->masa_tanam_hari));
        $estimasiHariMax = (int) ($aturan['bibit']->estimasi_hari_max ?? $estimasiHari);
        DB::transaction(function () use ($data, $lahan, $aturan, $tanggalTanam, $tanggalPemupukan, $estimasiHari, $estimasiHariMax, $pupuk, $luasTanam, $request, $userId) {
            $data->update([
                'lahan_id' => $lahan->id,
                'luas_tanam_hektar' => $luasTanam,
                'bibit_id' => $aturan['bibit']->id,
                'pupuk_id' => $pupuk->id,
                'tanggal_tanam' => $tanggalTanam->toDateString(),
                'tanggal_pemupukan' => $tanggalPemupukan->toDateString(),
                'takaran_pupuk_kg' => $request->takaran,
                'pemupukan_dicatat_oleh' => $userId,
                'pemupukan_dicatat_at' => now(),
                'estimasi_hari' => $estimasiHari,
                'estimasi_tanggal_panen' => $tanggalTanam->copy()->addDays($estimasiHari)->toDateString(),
                'estimasi_tanggal_panen_akhir' => $tanggalTanam->copy()->addDays($estimasiHariMax)->toDateString(),
            ]);

            DB::table('lahan_sawah')->where('id', $lahan->id)->update([
                'luas_tanam_hektar' => $luasTanam,
                'updated_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Laporan tanam berhasil diperbarui.',
            'data' => $this->formatTanam($data->fresh(['bibit', 'pupuk', 'lahan.pemilik', 'panen']), $userId, $roleId),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        [$userId, $roleId] = $this->authUser($request);
        if (!$this->rolePetani($roleId)) {
            return $this->forbidden('Anda tidak memiliki akses untuk menghapus laporan tanam.');
        }

        $query = SiklusTanam::where('id', $id);
        $this->batasiTanamUntukPetani($query, $userId, $roleId);
        $data = $query->first();
        
        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Data tanam tidak ditemukan atau Anda tidak memiliki akses.'], 404);
        }
        if ($data->status_aktif === 'NONAKTIF' || LaporPanen::where('tanam_padi_id', $id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Data tanam yang memiliki laporan panen tidak boleh dihapus.'], 400);
        }

        $data->delete();
        return response()->json(['success' => true, 'message' => 'Data tanam berhasil dihapus.']);
    }

    public function totalProduksi(Request $request)
    {
        [$userId, $roleId] = $this->authUser($request);
        $query = LaporPanen::where('status_verifikasi', 'DITERIMA')
            ->whereYear('tanggal_panen', now()->year)
            ->whereDate('tanggal_panen', '<=', now()->toDateString());
        if ($roleId === self::ROLE_KELOMPOK_TANI) {
            $query->where('pemilik_id', $userId);
        } elseif ($roleId === self::ROLE_BRIGADE_PANGAN) {
            $query->where('pemilik_id', $userId);
        } else {
            return $this->forbidden('Ringkasan produksi hanya tersedia untuk petani.');
        }

        return response()->json([
            'success' => true,
            'data' => ['tahun' => now()->year, 'total_produksi' => (float) $query->sum('hasil_panen_ton')],
        ]);
    }

    public function getPendingVerifications(Request $request)
    {
        [$petugasId, $roleId] = $this->authUser($request);
        if ($roleId !== self::ROLE_PETUGAS) {
            return $this->forbidden('Antrean verifikasi hanya dapat diakses petugas.');
        }

        $query = $this->queryHasilPanen()
            ->where('pp.status_verifikasi', 'PENDING');

        $this->applyPetugasWilayahScope($query, $petugasId);

        $data = $query->orderByDesc('pp.created_at')
            ->get()
            ->map(fn ($row) => $this->formatHasilPanen($row));

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function verifyHarvest(Request $request, $id)
    {
        $aksi = strtoupper((string) $request->input('aksi', $request->input('status', '')));
        if (in_array($aksi, ['DITERIMA', 'TERIMA', 'SETUJU', 'APPROVE', 'APPROVED'], true)) {
            return $this->approve($request, $id);
        }
        if (in_array($aksi, ['DITOLAK', 'TOLAK', 'REJECT', 'REJECTED'], true)) {
            return $this->reject($request, $id);
        }

        return response()->json(['success' => false, 'message' => 'Aksi verifikasi tidak valid.'], 422);
    }

    public function approve(Request $request, $id)
    {
        [$petugasId, $roleId] = $this->authUser($request);
        if ($roleId !== self::ROLE_PETUGAS) {
            return $this->forbidden('Verifikasi hasil panen hanya dapat dilakukan petugas.');
        }

        $result = DB::transaction(function () use ($id, $petugasId) {
            $panen = LaporPanen::where('id', $id)->lockForUpdate()->first();
            if (!$panen) {
                return ['status' => 404, 'body' => ['success' => false, 'message' => 'Data hasil panen tidak ditemukan.']];
            }
            if ($panen->status_verifikasi === 'DITERIMA') {
                return ['status' => 409, 'body' => ['success' => false, 'message' => 'Laporan panen sudah pernah disetujui.']];
            }
            $tanam = SiklusTanam::where('id', $panen->tanam_padi_id)->first();
            if (!$tanam || !$this->petugasBolehVerifikasiLahan($petugasId, (int) $tanam->lahan_id)) {
                return ['status' => 403, 'body' => ['success' => false, 'message' => 'Laporan panen berada di luar wilayah kerja petugas.']];
            }

            $panen->update([
                'status_verifikasi' => 'DITERIMA',
                'catatan_verifikasi' => null,
                'diverifikasi_oleh' => $petugasId,
                'diverifikasi_at' => now(),
            ]);

            SiklusTanam::where('id', $panen->tanam_padi_id)->update([
                'status_aktif' => 'NONAKTIF',
                'status_verifikasi' => 'DITERIMA',
                'diverifikasi_oleh' => $petugasId,
                'diverifikasi_at' => now(),
                'catatan_verifikasi' => null,
            ]);

            $this->sinkronkanInfoLahanDariPanenTerakhir((int) $panen->lahan_id);
            $this->tandaiNotifikasiPanenTerbaca((int) $panen->id);

            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'message' => 'Laporan panen berhasil disetujui dan menjadi riwayat panen.',
                    'data' => $this->ambilDetailHasilPanen((int) $panen->id),
                ],
            ];
        });

        return response()->json($result['body'], $result['status'], [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function reject(Request $request, $id)
    {
        [$petugasId, $roleId] = $this->authUser($request);
        if ($roleId !== self::ROLE_PETUGAS) {
            return $this->forbidden('Verifikasi hasil panen hanya dapat dilakukan petugas.');
        }

        $request->validate(['alasan_penolakan' => 'nullable|string|max:1000']);
        $panen = LaporPanen::find($id);
        if (!$panen) {
            return response()->json(['success' => false, 'message' => 'Data hasil panen tidak ditemukan.'], 404);
        }
        if ($panen->status_verifikasi === 'DITERIMA') {
            return response()->json(['success' => false, 'message' => 'Hasil panen yang sudah diterima tidak boleh ditolak ulang.'], 400);
        }
        $tanam = SiklusTanam::where('id', $panen->tanam_padi_id)->first();
        if (!$tanam || !$this->petugasBolehVerifikasiLahan($petugasId, (int) $tanam->lahan_id)) {
            return response()->json(['success' => false, 'message' => 'Laporan panen berada di luar wilayah kerja petugas.'], 403);
        }

        $panen->update([
            'status_verifikasi' => 'DITOLAK',
            'catatan_verifikasi' => $request->input('alasan_penolakan') ?: $request->input('catatan_verifikasi'),
            'diverifikasi_oleh' => $petugasId,
            'diverifikasi_at' => now(),
        ]);
        $this->tandaiNotifikasiPanenTerbaca((int) $panen->id);

        return response()->json([
            'success' => true,
            'message' => 'Laporan panen berhasil ditolak.',
            'data' => $this->ambilDetailHasilPanen((int) $panen->id),
        ]);
    }

    public function getJenisPupuk()
    {
        $data = DB::table('jenis_pupuk')->get()->map(fn ($row) => [
            'id' => (int) $row->id,
            'nama_pupuk' => $this->safeText($row->nama_bibit ?? $row->nama_pupuk ?? '-'),
            'tipe_pupuk' => $this->safeText($row->varietas ?? $row->tipe_pupuk ?? null),
        ]);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getMySiklusTanam(Request $request)
    {
        return $this->index($request);
    }

    public function storeSiklusPupuk(Request $request)
    {
        $request->validate([
            'siklus_tanam_id' => 'required|integer',
            'pupuk_id' => 'required|integer',
            'tanggal_pemupukan' => 'required|date|before_or_equal:today',
            'takaran' => 'required|numeric|min:0.01',
        ]);

        [$userId, $roleId] = $this->authUser($request);
        if (!$this->rolePetani($roleId)) {
            return $this->forbidden('Catatan pemupukan hanya tersedia untuk petani.');
        }

        $query = SiklusTanam::with('lahan')->where('id', $request->siklus_tanam_id)->where('status_aktif', 'AKTIF');
        $this->batasiTanamUntukPetani($query, $userId, $roleId);
        $tanam = $query->first();
        if (!$tanam) {
            return $this->forbidden('Data tanam tidak valid atau sudah selesai.');
        }

        $tanggal = Carbon::parse($request->tanggal_pemupukan);
        if ($tanggal->lt(Carbon::parse($tanam->tanggal_tanam))) {
            return response()->json(['success' => false, 'message' => 'Tanggal pemupukan tidak boleh sebelum tanggal tanam.'], 422);
        }

        $pupuk = DB::table('jenis_pupuk')->where('id', $request->pupuk_id)->first();
        if (!$pupuk) {
            return response()->json(['success' => false, 'message' => 'Jenis pupuk tidak ditemukan.'], 422);
        }

        $entry = [
            'id' => (int) $tanam->id,
            'pupuk_id' => (int) $pupuk->id,
            'nama_pupuk' => $pupuk->nama_bibit ?? $pupuk->nama_pupuk ?? '-',
            'tipe_pupuk' => $pupuk->varietas ?? $pupuk->tipe_pupuk ?? null,
            'tanggal_pemupukan' => $tanggal->toDateString(),
            'takaran' => (float) $request->takaran,
            'dicatat_oleh' => $userId,
            'dicatat_at' => now()->toIso8601String(),
        ];
        $tanam->update([
            'pupuk_id' => $pupuk->id,
            'tanggal_pemupukan' => $tanggal->toDateString(),
            'takaran_pupuk_kg' => $request->takaran,
            'pemupukan_dicatat_oleh' => $userId,
            'pemupukan_dicatat_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rincian pemupukan pada data tanam berhasil diperbarui.',
            'data' => array_merge($entry, ['siklus_tanam_id' => (int) $tanam->id]),
        ]);
    }

    public function getSiklusPupuk(Request $request)
    {
        [$userId, $roleId] = $this->authUser($request);
        if (!$this->rolePetani($roleId)) {
            return $this->forbidden('Riwayat pemupukan hanya tersedia untuk petani.');
        }

        $query = SiklusTanam::with(['lahan', 'pupuk'])->orderByDesc('tanggal_pemupukan')->orderByDesc('id');
        $this->batasiTanamUntukPetani($query, $userId, $roleId);
        $riwayat = $query->get()->map(function (SiklusTanam $tanam) {
            return [
                'id' => (int) $tanam->id,
                'siklus_tanam_id' => (int) $tanam->id,
                'nama_lahan' => $tanam->lahan?->nama_lahan ?? '-',
                'nama_pupuk' => $tanam->pupuk?->nama_bibit ?? $tanam->pupuk?->nama_pupuk ?? '-',
                'tipe_pupuk' => $tanam->pupuk?->varietas ?? $tanam->pupuk?->tipe_pupuk,
                'tanggal_pemupukan' => optional($tanam->tanggal_pemupukan)->format('Y-m-d'),
                'takaran' => (float) $tanam->takaran_pupuk_kg,
            ];
        })->values();

        $perPage = max(1, (int) $request->input('per_page', 3));
        $page = max(1, (int) $request->input('pupuk_page', 1));
        $paginator = new LengthAwarePaginator(
            $riwayat->forPage($page, $perPage)->values(),
            $riwayat->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'pageName' => 'pupuk_page']
        );

        return response()->json(['success' => true, 'data' => $paginator]);
    }

    private function queryHasilPanen()
    {
        return DB::table('panen_padi as pp')
            ->join('tanam_padi as tp', 'tp.id', '=', 'pp.tanam_padi_id')
            ->join('lahan_sawah as ls', 'ls.id', '=', 'tp.lahan_id')
            ->leftJoin('users as pemilik', 'pemilik.id', '=', 'pp.pemilik_id')
            ->leftJoin('jenis_bibit as jb', 'jb.id', '=', 'tp.bibit_id')
            ->leftJoin('kecamatan as kc', 'kc.id', '=', 'ls.kecamatan_id')
            ->leftJoin('kelurahan as kl', 'kl.id', '=', 'ls.kelurahan_id')
            ->select([
                'pp.*',
                'tp.lahan_id',
                'tp.bibit_id',
                'tp.tanggal_tanam',
                'tp.luas_tanam_hektar as tanam_luas_tanam_hektar',
                'tp.estimasi_hari',
                'tp.estimasi_tanggal_panen',
                'tp.estimasi_tanggal_panen_akhir',
                'tp.status_aktif',
                'ls.nama_lahan',
                'ls.luas_lahan_hektar as lahan_luas_lahan_hektar',
                'ls.luas_tanam_hektar as lahan_luas_tanam_hektar',
                'ls.hasil_panen_ton as lahan_hasil_panen_ton',
                'ls.produktivitas_ton_ha as lahan_produktivitas_ton_ha',
                'ls.alamat_detail',
                'ls.latitude',
                'ls.longitude',
                'pemilik.nama_lengkap as nama_pemilik',
                'pemilik.email as email_pemilik',
                'pemilik.no_hp as no_hp_pemilik',
                'jb.nama_bibit',
                'jb.varietas',
                'jb.masa_tanam_hari',
                'kc.nama_kecamatan',
                'kl.nama_kelurahan',
            ]);
    }

    private function ambilDetailHasilPanen(int $id): ?array
    {
        $row = $this->queryHasilPanen()->where('pp.id', $id)->first();
        return $row ? $this->formatHasilPanen($row) : null;
    }

    private function formatHasilPanen($row): array
    {
        $hasilPanen = (float) $row->hasil_panen_ton;
        $luasTanam = (float) ($row->tanam_luas_tanam_hektar ?? $row->lahan_luas_tanam_hektar ?? $row->lahan_luas_lahan_hektar ?? 0);
        return [
            'id' => (int) $row->id,
            'siklus_tanam_id' => (int) $row->tanam_padi_id,
            'tanam_padi_id' => (int) $row->tanam_padi_id,
            'lahan_id' => (int) $row->lahan_id,
            'bibit_id' => (int) $row->bibit_id,
            'tanggal_tanam' => $row->tanggal_tanam,
            'estimasi_panen' => (int) ($row->estimasi_hari ?? 0),
            'estimasi_tanggal_panen' => $row->estimasi_tanggal_panen,
            'estimasi_tanggal_panen_akhir' => $row->estimasi_tanggal_panen_akhir,
            'status_aktif' => $row->status_aktif,
            'tanggal_panen' => $row->tanggal_panen,
            'hasil_panen' => (float) $row->hasil_panen_ton,
            'hasil_panen_ton' => (float) $row->hasil_panen_ton,
            'hasil_panen_label' => number_format((float) $row->hasil_panen_ton, 2, ',', '.') . ' Ton',
            'status_verifikasi' => $row->status_verifikasi,
            'catatan_verifikasi' => $this->safeText($row->catatan_verifikasi),
            'created_by' => (int) $row->pemilik_id,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
            'nama_petani' => $this->safeText($row->nama_pemilik),
            'email_petani' => $this->safeText($row->email_pemilik),
            'no_hp_petani' => $this->safeText($row->no_hp_pemilik),
            'nama_bibit' => $this->safeText($row->nama_bibit),
            'varietas' => $this->safeText($row->varietas),
            'masa_tanam_hari' => (int) ($row->masa_tanam_hari ?? $row->estimasi_hari ?? 0),
            'nama_lahan' => $this->safeText($row->nama_lahan),
            'pemilik_lahan' => $this->safeText($row->nama_pemilik),
            'luas_lahan_hektar' => (float) ($row->lahan_luas_lahan_hektar ?? 0),
            'luas_tanam_hektar' => $luasTanam,
            'produktivitas_pengajuan_ton_ha' => $luasTanam > 0 ? round($hasilPanen / $luasTanam, 2) : 0,
            'lahan_hasil_panen_ton' => (float) ($row->lahan_hasil_panen_ton ?? 0),
            'lahan_produktivitas_ton_ha' => (float) ($row->lahan_produktivitas_ton_ha ?? 0),
            'alamat_detail' => $this->safeText($row->alamat_detail),
            'latitude' => $row->latitude !== null ? (float) $row->latitude : null,
            'longitude' => $row->longitude !== null ? (float) $row->longitude : null,
            'nama_kecamatan' => $this->safeText($row->nama_kecamatan),
            'nama_kelurahan' => $this->safeText($row->nama_kelurahan),
            'petani' => [
                'id' => (int) $row->pemilik_id,
                'nama_lengkap' => $this->safeText($row->nama_pemilik),
                'email' => $this->safeText($row->email_pemilik),
                'no_hp' => $this->safeText($row->no_hp_pemilik),
            ],
            'lahan' => [
                'id' => (int) $row->lahan_id,
                'nama_lahan' => $this->safeText($row->nama_lahan),
                'pemilik_lahan' => $this->safeText($row->nama_pemilik),
                'luas_lahan_hektar' => (float) ($row->lahan_luas_lahan_hektar ?? 0),
                'luas_tanam_hektar' => $luasTanam,
            ],
        ];
    }

    private function sinkronkanInfoLahanDariPanenTerakhir(int $lahanId): void
    {
        $panen = LaporPanen::with(['siklusTanam.lahan'])
            ->whereHas('siklusTanam', function ($q) use ($lahanId) {
                $q->where('lahan_id', $lahanId);
            })
            ->where('status_verifikasi', 'DITERIMA')
            ->whereDate('tanggal_panen', '<=', now()->toDateString())
            ->orderByDesc('tanggal_panen')
            ->orderByDesc('id')
            ->first();
        if (!$panen) {
            return;
        }

        $luas = (float) ($panen->siklusTanam->luas_tanam_hektar ?: $panen->siklusTanam->lahan->luas_tanam_hektar ?: $panen->siklusTanam->lahan->luas_lahan_hektar);
        $produktivitas = $luas > 0 ? round($panen->hasil_panen_ton / $luas, 2) : 0;

        DB::table('lahan_sawah')->where('id', $lahanId)->update([
            'hasil_panen_ton' => $panen->hasil_panen_ton,
            'luas_tanam_hektar' => $luas,
            'produktivitas_ton_ha' => $produktivitas,
            'panen_terakhir_id' => $panen->id,
            'updated_at' => now(),
        ]);
    }

    private function batasiTanamUntukPetani($query, int $userId, int $roleId): void
    {
        if ($this->rolePetani($roleId)) {
            $query->whereHas('lahan', fn ($q) => $q->where('pemilik_id', $userId));
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    private function lahanTanamYangDiizinkan(int $userId, int $roleId, int $lahanId): ?LahanSawah
    {
        $query = LahanSawah::where('id', $lahanId)->where('status_verifikasi', 'DITERIMA');
        
        if ($this->rolePetani($roleId)) {
            return $query->where('pemilik_id', $userId)->first();
        }
        
        return null;
    }

    private function validasiAturanTanam(int $roleId, int $bibitId, string $tanggalTanam, LahanSawah $lahan, int $userId): array
    {
        $bibit = DB::table('jenis_bibit')->where('id', $bibitId)->first();
        if (!$bibit) {
            return ['error' => 'Jenis bibit tidak ditemukan.', 'bibit' => null];
        }

        if (!$this->rolePetani($roleId) || (int) $lahan->pemilik_id !== $userId) {
            return ['error' => 'Lahan tidak terdaftar untuk akun ini atau belum disetujui.', 'bibit' => $bibit];
        }

        return ['error' => null, 'bibit' => $bibit];
    }

    private function formatTanam(SiklusTanam $item, int $userId, int $roleId): array
    {
        $tanggalTanam = Carbon::parse($item->tanggal_tanam);
        $estimasi = $item->estimasi_tanggal_panen
            ? Carbon::parse($item->estimasi_tanggal_panen)
            : $tanggalTanam->copy()->addDays((int) $item->estimasi_hari);
        $totalHari = max(1, $tanggalTanam->diffInDays($estimasi));
        $progress = (int) min(100, max(0, round((max(0, $tanggalTanam->diffInDays(now(), false)) / $totalHari) * 100)));
        $pemilik = $item->lahan?->pemilik?->nama_lengkap ?? '-';
        $pemupukanAwal = [
            'id' => (int) $item->id,
            'pupuk_id' => (int) $item->pupuk_id,
            'nama_pupuk' => $item->pupuk?->nama_bibit ?? $item->pupuk?->nama_pupuk ?? '-',
            'tipe_pupuk' => $item->pupuk?->varietas ?? $item->pupuk?->tipe_pupuk,
            'tanggal_pemupukan' => optional($item->tanggal_pemupukan)->format('Y-m-d'),
            'takaran' => (float) $item->takaran_pupuk_kg,
            'dicatat_oleh' => (int) $item->pemupukan_dicatat_oleh,
            'dicatat_at' => optional($item->pemupukan_dicatat_at)->toIso8601String(),
        ];

        return [
            'id' => (int) $item->id,
            'lahan_id' => (int) $item->lahan_id,
            'bibit_id' => (int) $item->bibit_id,
            'tanggal_tanam' => optional($item->tanggal_tanam)->format('Y-m-d'),
            'estimasi_panen' => (int) $item->estimasi_hari,
            'estimasi_tanggal_panen' => $estimasi->toDateString(),
            'status_aktif' => $item->status_aktif,
            'tanggal_panen' => optional($item->panen?->tanggal_panen)->format('Y-m-d'),
            'hasil_panen' => $item->panen?->hasil_panen_ton,
            'status_verifikasi' => $item->status_verifikasi,
            'peran_pelapor' => $roleId === self::ROLE_BRIGADE_PANGAN ? 'brigade_pangan' : 'kelompok_tani',
            'created_by' => (int) $item->pemupukan_dicatat_oleh,
            'nama_lahan' => $item->lahan?->nama_lahan ?? '-',
            'pemilik_lahan' => $pemilik,
            'luas_lahan_hektar' => (float) ($item->lahan?->luas_lahan_hektar ?? 0),
            'luas_tanam_hektar' => (float) ($item->luas_tanam_hektar ?: $item->lahan?->luas_tanam_hektar ?: $item->lahan?->luas_lahan_hektar ?: 0),
            'nama_bibit' => $item->bibit?->nama_bibit ?? '-',
            'varietas' => $item->bibit?->varietas,
            'masa_tanam_hari' => (int) ($item->bibit?->masa_tanam_hari ?? $item->estimasi_hari),
            'progress_persen' => $progress,
            'hari_tersisa' => max(0, now()->startOfDay()->diffInDays($estimasi->startOfDay(), false)),
            'status_laporan_panen' => $item->panen?->status_verifikasi,
            'pemupukan_awal' => $pemupukanAwal,
            'pupuk_id' => $pemupukanAwal['pupuk_id'],
            'tanggal_pemupukan' => $pemupukanAwal['tanggal_pemupukan'],
            'takaran' => $pemupukanAwal['takaran'],
            'can_edit' => $item->status_aktif === 'AKTIF',
            'can_delete' => $item->status_aktif === 'AKTIF' && !$item->panen,
            'can_report_harvest' => $this->rolePetani($roleId)
                && (int) ($item->lahan?->pemilik_id ?? 0) === $userId
                && $item->status_aktif === 'AKTIF'
                && now()->startOfDay()->gte($estimasi->startOfDay())
                && (!$item->panen || $item->panen->status_verifikasi === 'DITOLAK'),
        ];
    }

    private function buatNotifikasiPetugas(string $judul, string $pesan, ?string $refType, ?int $refId, ?string $targetUrl, ?int $userIdPenerima = null): void
    {
        try {
            DB::table('notifikasi')->updateOrInsert([
                'role_id_penerima' => self::ROLE_PETUGAS,
                'user_id_penerima' => $userIdPenerima,
                'ref_type' => $refType,
                'ref_id' => $refId,
            ], [
                'role_id_penerima' => self::ROLE_PETUGAS,
                'user_id_penerima' => $userIdPenerima,
                'judul' => $judul,
                'pesan' => $pesan,
                'ref_type' => $refType,
                'ref_id' => $refId,
                'target_url' => $targetUrl,
                'is_read' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $error) {
            Log::warning('Notifikasi panen gagal dibuat.', ['error' => $error->getMessage()]);
        }
    }

    private function tandaiNotifikasiPanenTerbaca(int $id): void
    {
        DB::table('notifikasi')
            ->where('ref_type', 'panen_padi')
            ->where('ref_id', $id)
            ->delete();
    }

    private function assignedPetugasLahan(int $lahanId): ?int
    {
        $value = DB::table('lahan_sawah')->where('id', $lahanId)->value('assigned_petugas_id');
        return $value ? (int) $value : null;
    }

    private function applyPetugasWilayahScope($query, int $petugasId): void
    {
        $wilayah = $this->petugasWilayah($petugasId);
        if (!empty($wilayah['kelurahan_ids'])) {
            $query->whereIn('ls.kelurahan_id', $wilayah['kelurahan_ids']);
            return;
        }

        if ($wilayah['kecamatan_id']) {
            $query->where('ls.kecamatan_id', $wilayah['kecamatan_id']);
        }
    }

    private function petugasBolehVerifikasiLahan(int $petugasId, int $lahanId): bool
    {
        $wilayah = $this->petugasWilayah($petugasId);
        if (!$wilayah['kecamatan_id'] && empty($wilayah['kelurahan_ids'])) {
            return true;
        }

        $lahan = DB::table('lahan_sawah')
            ->where('id', $lahanId)
            ->select('kecamatan_id', 'kelurahan_id')
            ->first();

        if (!$lahan) {
            return false;
        }

        if (!empty($wilayah['kelurahan_ids'])) {
            return in_array((int) $lahan->kelurahan_id, $wilayah['kelurahan_ids'], true);
        }

        return (int) $lahan->kecamatan_id === (int) $wilayah['kecamatan_id'];
    }

    private function petugasWilayah(int $petugasId): array
    {
        $petugas = DB::table('users')
            ->join('komunitas', 'users.komunitas_id', '=', 'komunitas.id')
            ->where('users.id', $petugasId)
            ->where('users.role_id', self::ROLE_PETUGAS)
            ->select('komunitas.wilayah_kecamatan_id', 'komunitas.wilayah_kelurahan_ids')
            ->first();

        return [
            'kecamatan_id' => $petugas?->wilayah_kecamatan_id ? (int) $petugas->wilayah_kecamatan_id : null,
            'kelurahan_ids' => $this->kelurahanIds($petugas?->wilayah_kelurahan_ids ?? null),
        ];
    }

    private function kelurahanIds($value): array
    {
        if (is_array($value)) {
            return array_values(array_unique(array_map('intval', $value)));
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return array_values(array_unique(array_map('intval', $decoded)));
            }
        }

        return [];
    }

    private function authUser(Request $request): array
    {
        $user = $request->attributes->get('auth');
        return [(int) ($user->sub ?? $user->id ?? 0), (int) ($user->role_id ?? $user->role ?? 0)];
    }

    private function rolePetani(int $roleId): bool
    {
        return in_array($roleId, [self::ROLE_KELOMPOK_TANI, self::ROLE_BRIGADE_PANGAN], true);
    }

    private function forbidden(string $message)
    {
        return response()->json(['success' => false, 'message' => $message], 403);
    }

    private function safeText($value): ?string
    {
        return $value === null ? null : mb_convert_encoding((string) $value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
    }
}
