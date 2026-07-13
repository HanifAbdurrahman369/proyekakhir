<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NotifikasiController extends Controller
{
    private const ROLE_KELOMPOK_TANI = 1;
    private const ROLE_PETUGAS = 2;
    private const ROLE_BRIGADE_PANGAN = 5;

    private const PETUGAS_VISIBLE_REFS = ['lahan_sawah', 'panen_padi'];
    private const PETUGAS_LEGACY_REFS = ['lahan_sawah', 'panen_padi', 'lapor_panen', 'siklus_tanam'];
    private const PETANI_VISIBLE_REFS = ['lahan_ditolak', 'panen_ditolak', 'monitoring_kondisi'];

    public function getNotifikasiPetugas(Request $request)
    {
        return $this->index($request);
    }

    public function index(Request $request)
    {
        [$roleId, $userId] = $this->resolveRequestUser($request);

        if (!in_array($roleId, [self::ROLE_KELOMPOK_TANI, self::ROLE_PETUGAS, self::ROLE_BRIGADE_PANGAN], true)) {
            return $this->emptyResponse();
        }

        $visibleRefs = $this->syncActionableNotifications($roleId, $userId);
        if (empty($visibleRefs)) {
            return $this->emptyResponse();
        }

        $query = DB::table('notifikasi')
            ->where('role_id_penerima', $roleId)
            ->whereIn('ref_type', $visibleRefs);

        if ($roleId === self::ROLE_PETUGAS) {
            if ($userId) {
                $query->where(function ($scope) use ($userId) {
                    $scope->where('user_id_penerima', $userId)
                        ->orWhereNull('user_id_penerima');
                });
            }
        } else {
            if (!$userId) {
                return $this->emptyResponse();
            }
            $query->where('user_id_penerima', $userId);
        }

        $notifikasi = (clone $query)
            ->orderBy('is_read')
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get();

        $unreadCount = (clone $query)->count();
        $pendingCounts = $roleId === self::ROLE_PETUGAS
            ? $this->pendingCounts($userId)
            : [
                'pending_lahan' => 0,
                'pending_panen' => 0,
                'total_pending' => $unreadCount,
            ];

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

        DB::table('notifikasi')->where('id', $id)->update([
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

    public function destroy($id)
    {
        DB::table('notifikasi')->where('id', $id)->update([
            'is_read' => 1,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai terbaca.',
        ]);
    }

    private function resolveRequestUser(Request $request): array
    {
        $auth = $request->attributes->get('auth');

        $roleId = (int) ($request->query('role_id')
            ?: data_get($auth, 'role_id')
            ?: data_get($auth, 'role')
            ?: 0);

        $userId = (int) ($request->query('user_id')
            ?: data_get($auth, 'sub')
            ?: data_get($auth, 'id')
            ?: 0);

        return [$roleId, $userId ?: null];
    }

    private function syncActionableNotifications(int $roleId, ?int $userId): array
    {
        try {
            if ($roleId === self::ROLE_PETUGAS) {
                return $this->syncPetugasNotifications($userId);
            }

            if (in_array($roleId, [self::ROLE_KELOMPOK_TANI, self::ROLE_BRIGADE_PANGAN], true) && $userId) {
                return $this->syncPetaniNotifications($roleId, $userId);
            }
        } catch (\Throwable $error) {
            report($error);
        }

        return [];
    }

    private function syncPetugasNotifications(?int $petugasId): array
    {
        $lahanIds = [];
        foreach ($this->pendingLahanQuery($petugasId)->get() as $row) {
            $lahanIds[] = (int) $row->id;
            $namaLahan = $this->cleanText($row->nama_lahan ?? 'lahan sawah');
            $namaPetani = $this->cleanText($row->nama_petani ?? 'Kelompok Tani/Brigade Pangan');

            $this->upsertNotification(
                self::ROLE_PETUGAS,
                $row->assigned_petugas_id ? (int) $row->assigned_petugas_id : $petugasId,
                'Pengajuan Lahan Menunggu',
                $namaPetani . ' mengajukan lahan ' . $namaLahan . ' dan perlu diverifikasi.',
                'lahan_sawah',
                (int) $row->id,
                '/verifikasi-data-petani?lahan_id=' . $row->id
            );
        }

        $panenIds = [];
        foreach ($this->pendingPanenQuery($petugasId)->get() as $row) {
            $panenIds[] = (int) $row->id;
            $namaLahan = $this->cleanText($row->nama_lahan ?? 'lahan sawah');
            $namaPetani = $this->cleanText($row->nama_petani ?? 'Kelompok Tani/Brigade Pangan');

            $this->upsertNotification(
                self::ROLE_PETUGAS,
                $row->assigned_petugas_id ? (int) $row->assigned_petugas_id : $petugasId,
                'Laporan Panen Menunggu',
                $namaPetani . ' mengirim laporan panen untuk ' . $namaLahan . ' dan perlu diverifikasi.',
                'panen_padi',
                (int) $row->id,
                '/verifikasi-data-petani?tipe=panen&id=' . $row->id
            );
        }

        $this->deleteStaleRef(self::ROLE_PETUGAS, 'lahan_sawah', $lahanIds, $petugasId, true);
        $this->deleteStaleRef(self::ROLE_PETUGAS, 'panen_padi', $panenIds, $petugasId, true);
        $this->deleteInvalidRefGlobally(self::ROLE_PETUGAS, 'lahan_sawah', $this->globalPendingLahanIds());
        $this->deleteInvalidRefGlobally(self::ROLE_PETUGAS, 'panen_padi', $this->globalPendingPanenIds());
        $this->deleteLegacyPetugasNotifications();

        return self::PETUGAS_VISIBLE_REFS;
    }

    private function syncPetaniNotifications(int $roleId, int $userId): array
    {
        $rejectedLahanIds = [];
        $rejectedLahan = DB::table('lahan_sawah as ls')
            ->where('ls.pemilik_id', $userId)
            ->where('ls.status_verifikasi', 'DITOLAK')
            ->select('ls.id', 'ls.nama_lahan', 'ls.alasan_penolakan', 'ls.catatan_verifikasi', 'ls.updated_at')
            ->orderByDesc('ls.updated_at')
            ->get();

        foreach ($rejectedLahan as $row) {
            $rejectedLahanIds[] = (int) $row->id;
            $namaLahan = $this->cleanText($row->nama_lahan ?? 'lahan sawah');
            $catatan = $this->cleanText($row->alasan_penolakan ?? $row->catatan_verifikasi ?? 'Lengkapi kembali data pengajuan.');

            $this->upsertNotification(
                $roleId,
                $userId,
                'Pengajuan Lahan Perlu Diperbaiki',
                $namaLahan . ' ditolak petugas. ' . $catatan,
                'lahan_ditolak',
                (int) $row->id,
                '/lahan/' . $row->id . '/edit'
            );
        }

        $rejectedPanenIds = [];
        $rejectedPanen = DB::table('panen_padi as pp')
            ->join('tanam_padi as tp', 'tp.id', '=', 'pp.tanam_padi_id')
            ->join('lahan_sawah as ls', 'ls.id', '=', 'tp.lahan_id')
            ->where('pp.pemilik_id', $userId)
            ->where('pp.status_verifikasi', 'DITOLAK')
            ->select('pp.id', 'pp.catatan_verifikasi', 'pp.updated_at', 'ls.nama_lahan')
            ->orderByDesc('pp.updated_at')
            ->get();

        foreach ($rejectedPanen as $row) {
            $rejectedPanenIds[] = (int) $row->id;
            $namaLahan = $this->cleanText($row->nama_lahan ?? 'lahan sawah');
            $catatan = $this->cleanText($row->catatan_verifikasi ?? 'Perbaiki kembali laporan panen.');

            $this->upsertNotification(
                $roleId,
                $userId,
                'Laporan Panen Perlu Diperbaiki',
                'Laporan panen ' . $namaLahan . ' ditolak petugas. ' . $catatan,
                'panen_ditolak',
                (int) $row->id,
                '/panen/' . $row->id . '/edit'
            );
        }

        $this->deleteStaleRef($roleId, 'lahan_ditolak', $rejectedLahanIds, $userId);
        $this->deleteStaleRef($roleId, 'panen_ditolak', $rejectedPanenIds, $userId);
        $this->deleteInvalidRefGlobally($roleId, 'lahan_ditolak', $this->globalRejectedLahanIds());
        $this->deleteInvalidRefGlobally($roleId, 'panen_ditolak', $this->globalRejectedPanenIds());

        $monitoringIds = $this->syncMonitoringNotifications($roleId, $userId);
        $this->deleteStaleRef($roleId, 'monitoring_kondisi', $monitoringIds, $userId);
        $this->deleteInvalidRefGlobally($roleId, 'monitoring_kondisi', $this->globalActiveMonitoringIds());
        $this->deleteOrphanMonitoringNotifications();

        return self::PETANI_VISIBLE_REFS;
    }

    private function pendingCounts(?int $petugasId): array
    {
        try {
            $pendingLahan = $this->pendingLahanQuery($petugasId)->count();
            $pendingPanen = $this->pendingPanenQuery($petugasId)->count();

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

    private function pendingLahanQuery(?int $petugasId)
    {
        $query = DB::table('lahan_sawah as ls')
            ->leftJoin('users as pemilik', 'pemilik.id', '=', 'ls.pemilik_id')
            ->where('ls.status_verifikasi', 'PENDING')
            ->select([
                'ls.id',
                'ls.nama_lahan',
                'ls.pemilik_id',
                'ls.assigned_petugas_id',
                'ls.kecamatan_id',
                'ls.kelurahan_id',
                'ls.created_at',
                'ls.updated_at',
                'pemilik.nama_lengkap as nama_petani',
            ])
            ->orderByDesc('ls.updated_at')
            ->orderByDesc('ls.id');

        $this->applyPetugasScope($query, $petugasId, 'ls');

        return $query;
    }

    private function pendingPanenQuery(?int $petugasId)
    {
        $query = DB::table('panen_padi as pp')
            ->join('tanam_padi as tp', 'tp.id', '=', 'pp.tanam_padi_id')
            ->join('lahan_sawah as ls', 'ls.id', '=', 'tp.lahan_id')
            ->leftJoin('users as pemilik', 'pemilik.id', '=', 'pp.pemilik_id')
            ->where('pp.status_verifikasi', 'PENDING')
            ->select([
                'pp.id',
                'pp.tanam_padi_id',
                'pp.pemilik_id',
                'pp.tanggal_panen',
                'pp.hasil_panen_ton',
                'pp.created_at',
                'pp.updated_at',
                'ls.id as lahan_id',
                'ls.nama_lahan',
                'ls.assigned_petugas_id',
                'ls.kecamatan_id',
                'ls.kelurahan_id',
                'pemilik.nama_lengkap as nama_petani',
            ])
            ->orderByDesc('pp.updated_at')
            ->orderByDesc('pp.id');

        $this->applyPetugasScope($query, $petugasId, 'ls');

        return $query;
    }

    private function applyPetugasScope($query, ?int $petugasId, string $lahanAlias): void
    {
        if (!$petugasId) {
            return;
        }

        $wilayah = $this->petugasWilayah($petugasId);
        $hasAssignedScope = Schema::hasColumn('lahan_sawah', 'assigned_petugas_id');
        $hasKelurahanScope = !empty($wilayah['kelurahan_ids']);
        $hasKecamatanScope = (bool) $wilayah['kecamatan_id'];

        if (!$hasKelurahanScope && !$hasKecamatanScope) {
            if ($hasAssignedScope) {
                $query->where(function ($scope) use ($petugasId, $lahanAlias) {
                    $scope->where($lahanAlias . '.assigned_petugas_id', $petugasId)
                        ->orWhereNull($lahanAlias . '.assigned_petugas_id');
                });
            }
            return;
        }

        $query->where(function ($scope) use ($petugasId, $wilayah, $lahanAlias, $hasAssignedScope, $hasKelurahanScope, $hasKecamatanScope) {
            if ($hasAssignedScope) {
                $scope->orWhere($lahanAlias . '.assigned_petugas_id', $petugasId);
            }

            if ($hasKelurahanScope) {
                $scope->orWhereIn($lahanAlias . '.kelurahan_id', $wilayah['kelurahan_ids']);
            }

            if ($hasKecamatanScope) {
                $scope->orWhere($lahanAlias . '.kecamatan_id', $wilayah['kecamatan_id']);
            }
        });
    }

    private function petugasWilayah(int $petugasId): array
    {
        $row = DB::table('users')
            ->leftJoin('komunitas', 'komunitas.id', '=', 'users.komunitas_id')
            ->where('users.id', $petugasId)
            ->where('users.role_id', self::ROLE_PETUGAS)
            ->select('komunitas.wilayah_kecamatan_id', 'komunitas.wilayah_kelurahan_ids')
            ->first();

        return [
            'kecamatan_id' => $row?->wilayah_kecamatan_id ? (int) $row->wilayah_kecamatan_id : null,
            'kelurahan_ids' => $this->parseIdList($row?->wilayah_kelurahan_ids ?? null),
        ];
    }

    private function parseIdList($value): array
    {
        if (!$value) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        if (!is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($item) => (int) $item)
            ->filter(fn ($item) => $item > 0)
            ->values()
            ->all();
    }

    private function upsertNotification(int $roleId, ?int $userId, string $judul, string $pesan, string $refType, int $refId, string $targetUrl): void
    {
        $match = [
            'role_id_penerima' => $roleId,
            'user_id_penerima' => $userId,
            'ref_type' => $refType,
            'ref_id' => $refId,
        ];

        DB::table('notifikasi')->updateOrInsert($match, [
            'judul' => $judul,
            'pesan' => $pesan,
            'target_url' => $targetUrl,
            'is_read' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function deleteStaleRef(int $roleId, string $refType, array $activeIds, ?int $userId, bool $includeNullUser = false): void
    {
        $query = DB::table('notifikasi')
            ->where('role_id_penerima', $roleId)
            ->where('ref_type', $refType);

        if ($userId) {
            $query->where(function ($scope) use ($userId, $includeNullUser) {
                $scope->where('user_id_penerima', $userId);
                if ($includeNullUser) {
                    $scope->orWhereNull('user_id_penerima');
                }
            });
        } else {
            $query->whereNull('user_id_penerima');
        }

        if (!empty($activeIds)) {
            $query->whereNotIn('ref_id', array_values(array_unique($activeIds)));
        }

        $query->delete();
    }

    private function deleteInvalidRefGlobally(int $roleId, string $refType, array $activeIds): void
    {
        $query = DB::table('notifikasi')
            ->where('role_id_penerima', $roleId)
            ->where('ref_type', $refType);

        if (!empty($activeIds)) {
            $query->whereNotIn('ref_id', array_values(array_unique($activeIds)));
        }

        $query->delete();
    }

    private function deleteLegacyPetugasNotifications(): void
    {
        DB::table('notifikasi')
            ->where('role_id_penerima', self::ROLE_PETUGAS)
            ->whereIn('ref_type', array_diff(self::PETUGAS_LEGACY_REFS, self::PETUGAS_VISIBLE_REFS))
            ->delete();
    }

    private function globalPendingLahanIds(): array
    {
        return DB::table('lahan_sawah')
            ->where('status_verifikasi', 'PENDING')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function globalPendingPanenIds(): array
    {
        return DB::table('panen_padi')
            ->where('status_verifikasi', 'PENDING')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function globalRejectedLahanIds(): array
    {
        return DB::table('lahan_sawah')
            ->where('status_verifikasi', 'DITOLAK')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function globalRejectedPanenIds(): array
    {
        return DB::table('panen_padi')
            ->where('status_verifikasi', 'DITOLAK')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function syncMonitoringNotifications(int $roleId, int $userId): array
    {
        if (!Schema::hasTable('monitoring_kondisi') || !Schema::hasTable('lahan_huma')) {
            return [];
        }

        $activeIds = [];
        $rows = $this->activeMonitoringQuery()
            ->where('lh.pemilik_id', $userId)
            ->select('mk.id', 'mk.ph_air', 'mk.status_air', 'lh.nama_lahan')
            ->get();

        foreach ($rows as $row) {
            $activeIds[] = (int) $row->id;
            $namaLahan = $this->cleanText($row->nama_lahan ?? 'lahan termonitor');
            $detail = $row->ph_air !== null
                ? 'pH air saat ini ' . $row->ph_air . '.'
                : 'Status air saat ini ' . $this->cleanText($row->status_air ?? 'perlu dicek') . '.';

            $this->upsertNotification(
                $roleId,
                $userId,
                'Kondisi Lahan Perlu Dicek',
                $namaLahan . ' memiliki kondisi monitoring yang perlu ditindaklanjuti. ' . $detail,
                'monitoring_kondisi',
                (int) $row->id,
                '/dashboard-petani'
            );
        }

        return $activeIds;
    }

    private function globalActiveMonitoringIds(): array
    {
        if (!Schema::hasTable('monitoring_kondisi') || !Schema::hasTable('lahan_huma')) {
            return [];
        }

        return $this->activeMonitoringQuery()
            ->pluck('mk.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function activeMonitoringQuery()
    {
        $latest = DB::table('monitoring_kondisi')
            ->select('lahan_huma_id', DB::raw('MAX(id) as latest_id'))
            ->groupBy('lahan_huma_id');

        return DB::table('monitoring_kondisi as mk')
            ->joinSub($latest, 'latest_monitoring', function ($join) {
                $join->on('latest_monitoring.latest_id', '=', 'mk.id');
            })
            ->join('lahan_huma as lh', 'lh.id', '=', 'mk.lahan_huma_id')
            ->where(function ($query) {
                $query->where('mk.ph_air', '<', 5.5)
                    ->orWhere('mk.ph_air', '>', 7.5)
                    ->orWhere('mk.status_air', 'Banjir');
            });
    }

    private function deleteOrphanMonitoringNotifications(): void
    {
        DB::table('notifikasi')
            ->where('ref_type', 'monitoring_kondisi')
            ->whereNull('user_id_penerima')
            ->delete();
    }

    private function emptyResponse()
    {
        return response()->json([
            'success' => true,
            'unread_count' => 0,
            'pending_count' => 0,
            'pending_counts' => [
                'pending_lahan' => 0,
                'pending_panen' => 0,
                'total_pending' => 0,
            ],
            'data' => [],
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    private function cleanText($value): string
    {
        $text = trim((string) ($value ?? ''));
        return $text !== '' ? $text : '-';
    }
}
