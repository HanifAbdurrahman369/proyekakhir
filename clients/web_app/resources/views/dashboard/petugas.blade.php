@extends('layouts.app')

@section('title', 'Dashboard Petugas')

@php
    $page = $page ?? request()->query('page', 'dashboard');
    $stats = $stats ?? [];
    $pendingLahan = $pendingLahan ?? [];
    $pendingPanen = $pendingPanen ?? $panenPending ?? [];
    $panenPending = $panenPending ?? $pendingPanen ?? [];
    $notifikasi = $notifikasi ?? [];
    $referensi = $referensi ?? [];
    $koleksiLahan = $koleksiLahan ?? ['type' => 'FeatureCollection', 'features' => []];
    $batasWilayah = $batasWilayah ?? ['type' => 'FeatureCollection', 'features' => []];
    $batasKecamatan = $batasKecamatan ?? ['type' => 'FeatureCollection', 'features' => []];
    $spasialRows = $spasialRows ?? [];
    $spasialSummary = $spasialSummary ?? [];
    $lahanDiterima = $lahanDiterima ?? $lahan ?? [];
    $lahanBelumDipetakan = $lahanBelumDipetakan ?? [];
    $monitoring = $monitoring ?? [];

    $ambil = function ($row, $keys, $default = '-') {
        foreach ((array) $keys as $key) {
            $value = data_get($row, $key);
            if ($value !== null && $value !== '') return $value;
        }
        return $default;
    };
    $angka = fn($v, $d = 2) => number_format((float)($v ?: 0), $d, ',', '.');
    $isActive = fn($target) => $page === $target
        ? 'bg-primary-600 text-white shadow-lg shadow-green-100 border-primary-100'
        : 'bg-white text-slate-600 border border-primary-100 hover:text-primary-700 hover:border-primary-100';

    $pendingLahanCount = data_get($stats, 'pending_lahan', is_countable($pendingLahan) ? count($pendingLahan) : 0);
    $pendingPanenCount = data_get($stats, 'pending_panen', is_countable($pendingPanen) ? count($pendingPanen) : 0);
    $totalPending = data_get($stats, 'total_pending', (int)$pendingLahanCount + (int)$pendingPanenCount);

    $spasialRows = is_array($spasialRows) && count($spasialRows) ? $spasialRows : $lahanDiterima;
    $punyaSpasialLengkap = fn($item) => !empty(data_get($item, 'polygon_geojson') ?? data_get($item, 'geojson') ?? data_get($item, 'polygon_area'));
    $lahanBaruSpasial = collect($lahanBelumDipetakan)->values();
    $lahanLamaSpasial = isset($lahanLamaSpasial)
        ? collect($lahanLamaSpasial)->values()
        : collect($spasialRows)
            ->filter(fn($item) => $punyaSpasialLengkap($item))
            ->values();
    $totalSpasial = data_get($spasialSummary, 'total', is_countable($spasialRows) ? count($spasialRows) : 0);
    $sudahDipetakan = data_get($spasialSummary, 'sudah_dipetakan', $lahanLamaSpasial->count());
    $belumDipetakan = data_get($spasialSummary, 'belum_dipetakan', $lahanBaruSpasial->count());
    $persentaseLengkap = data_get($spasialSummary, 'persentase_lengkap', $totalSpasial > 0 ? round(($sudahDipetakan / $totalSpasial) * 100, 2) : 0);
@endphp

@if($page === 'manajemen-data-spasial')
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <style>
            #petugasSpasialMap { height: 560px; min-height: 560px; overflow: hidden; z-index: 1; }
            .leaflet-container { font-family: 'Poppins', sans-serif; }
            .spatial-panel { background: rgba(255,255,255,.94); border: 1px solid rgba(231,239,216,.95); box-shadow: 0 16px 42px rgba(32,60,16,.065); }
            .spatial-map-shell { position: relative; border: 1px solid #dfeccc; border-radius: 20px; overflow: hidden; background: #eef5e4; }
            .spatial-map-tools { position: absolute; left: 16px; right: 16px; bottom: 16px; z-index: 650; display: flex; flex-wrap: wrap; gap: 8px; pointer-events: none; }
            .spatial-map-tools .map-tool { pointer-events: auto; background: rgba(255,255,255,.96); color: #334155; border: 1px solid rgba(226,232,240,.95); box-shadow: 0 12px 32px rgba(32,60,16,.12); backdrop-filter: blur(10px); }
            .spatial-map-tools .map-tool.is-primary { background: #65bd00; color: #fff; border-color: #65bd00; }
            .spatial-map-tools .map-tool.is-danger { background: #fff7f7; color: #dc2626; border-color: #fecaca; }
            .spatial-map-tools .map-tool.is-active { background: #203c10; color: #fff; border-color: #203c10; }
            .spatial-map-shell .leaflet-control-layers { border: 0; border-radius: 16px; box-shadow: 0 14px 34px rgba(32,60,16,.16); overflow: hidden; }
            .spatial-map-shell .leaflet-control-layers-expanded { padding: 12px 14px; color: #203c10; font-weight: 700; }
            .spatial-map-shell .leaflet-control-layers-selector { accent-color: #65bd00; }
            .spatial-choice { border: 1px solid #e7efd8; background: #fff; color: #475569; }
            .spatial-choice.is-active { border-color: #65bd00; background: #edf8dc; color: #203c10; box-shadow: inset 0 0 0 1px rgba(101,189,0,.18); }
            .spatial-row { border: 1px solid #edf4df; background: #fff; }
            .spatial-row.is-active { border-color: #65bd00; background: #f7fced; }
            .spatial-workspace.is-locked .spatial-form-body { display: none; }
            .spatial-workspace:not(.is-locked) .spatial-empty-state { display: none; }
            .spatial-list { max-height: 360px; overflow-y: auto; }
            .map-tool:disabled { opacity: .45; cursor: not-allowed; }
            .spatial-section-title { letter-spacing: .18em; }
            .spatial-field { border: 1px solid #e7efd8; border-radius: 16px; padding: 14px 16px; background: #fff; min-height: 86px; transition: border-color .18s ease, box-shadow .18s ease; }
            .spatial-field:focus-within { border-color: #65bd00; box-shadow: 0 0 0 3px rgba(101,189,0,.10); }
            .spatial-field label { margin-bottom: 10px; }
            .spatial-field input,
            .spatial-field select,
            .spatial-field textarea { border: 0 !important; box-shadow: none !important; background: transparent !important; padding: 0 !important; border-radius: 0 !important; }
            .spatial-field input:focus,
            .spatial-field select:focus,
            .spatial-field textarea:focus { outline: none !important; box-shadow: none !important; }
            .spatial-form-section { border-top: 1px solid #e7efd8; padding-top: 18px; }
        </style>
    @endpush
@endif

@section('content')
    <div class="space-y-6">
        @unless(in_array($page, ['manajemen-data-spasial', 'verifikasi-data-petani'], true))
            <div class="glass-card rounded-2xl p-5 md:p-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                    <div>
                        <p class="text-primary-700 text-xs font-bold uppercase tracking-[0.22em] mb-2">SIG-PALA BATOLA</p>
                        <h1 class="text-2xl md:text-3xl font-extrabold text-primary-900">Dashboard Petugas</h1>
                        <p class="text-sm text-slate-500 mt-2 max-w-3xl">
                            Verifikasi pengajuan petani, kelola data spasial lahan, dan input parameter lingkungan lapangan.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ url('/dashboard-petugas') }}" class="px-4 py-2 rounded-xl text-sm font-bold transition {{ $isActive('dashboard') }}">Dashboard</a>
                        <a href="{{ url('/verifikasi-data-petani') }}" class="px-4 py-2 rounded-xl text-sm font-bold transition {{ $isActive('verifikasi-data-petani') }}">Verifikasi</a>
                        <a href="{{ url('/manajemen-data-spasial') }}" class="px-4 py-2 rounded-xl text-sm font-bold transition {{ $isActive('manajemen-data-spasial') }}">Data Spasial</a>
                        <a href="{{ url('/input-parameter-lingkungan') }}" class="px-4 py-2 rounded-xl text-sm font-bold transition {{ $isActive('input-parameter-lingkungan') }}">Parameter</a>
                    </div>
                </div>
            </div>
        @endunless

        @if(session('success'))
            <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-green-800 text-sm font-semibold">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-700 text-sm font-semibold">{{ session('error') }}</div>
        @endif

        @if($page === 'dashboard')
            <div class="grid md:grid-cols-3 gap-5">
                <div class="soft-card bg-white rounded-2xl p-6 border border-primary-100">
                    <p class="text-sm text-slate-500 font-semibold">Total Antrean</p>
                    <h2 class="text-4xl font-extrabold text-primary-900 mt-2">{{ $totalPending }}</h2>
                    <p class="text-xs text-slate-400 mt-2">Lahan baru + laporan hasil panen pending</p>
                </div>
                <div class="soft-card bg-white rounded-2xl p-6 border border-primary-100">
                    <p class="text-sm text-slate-500 font-semibold">Pengajuan Lahan</p>
                    <h2 class="text-4xl font-extrabold text-amber-600 mt-2">{{ $pendingLahanCount }}</h2>
                    <p class="text-xs text-slate-400 mt-2">Menunggu verifikasi petugas</p>
                </div>
                <div class="soft-card bg-white rounded-2xl p-6 border border-primary-100">
                    <p class="text-sm text-slate-500 font-semibold">Laporan Panen</p>
                    <h2 class="text-4xl font-extrabold text-primary-700 mt-2">{{ $pendingPanenCount }}</h2>
                    <p class="text-xs text-slate-400 mt-2">Menunggu legalisasi data panen</p>
                </div>
            </div>

            <div class="soft-card bg-white rounded-2xl p-6 border border-primary-100">
                <h2 class="text-xl font-extrabold text-primary-900 mb-2">Ringkasan Tugas Petugas</h2>
                <p class="text-sm text-slate-500 mb-5">Gunakan menu kerja berikut untuk menjalankan alur verifikasi dan pemetaan lahan.</p>
                <div class="grid md:grid-cols-3 gap-4">
                    <a href="{{ url('/verifikasi-data-petani') }}" class="rounded-2xl border border-primary-100 bg-white p-5 hover:bg-primary-50 transition">
                        <p class="font-bold text-primary-900">Verifikasi Data Petani</p>
                        <p class="text-sm text-slate-500 mt-1">Setujui atau tolak lahan dan hasil panen.</p>
                    </a>
                    <a href="{{ url('/manajemen-data-spasial') }}" class="rounded-2xl border border-primary-100 bg-white p-5 hover:bg-primary-50 transition">
                        <p class="font-bold text-primary-900">Manajemen Data Spasial</p>
                        <p class="text-sm text-slate-500 mt-1">Buat titik lokasi dan polygon lahan.</p>
                    </a>
                    <a href="{{ url('/input-parameter-lingkungan') }}" class="rounded-2xl border border-primary-100 bg-white p-5 hover:bg-primary-50 transition">
                        <p class="font-bold text-primary-900">Parameter Lingkungan</p>
                        <p class="text-sm text-slate-500 mt-1">Catat pH air, tinggi muka air, dan kondisi lapangan.</p>
                    </a>
                </div>
            </div>
        @endif

        @if($page === 'verifikasi-data-petani')
            <div class="space-y-6">
                <div class="soft-card bg-white rounded-2xl border border-primary-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-primary-100">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="text-xl font-extrabold text-primary-900">Antrean Pengajuan Lahan Baru</h2>
                            <span data-petugas-pending-lahan class="px-3 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-xs font-extrabold">{{ is_countable($pendingLahan) ? count($pendingLahan) : 0 }} belum diverifikasi</span>
                        </div>
                        <p class="text-sm text-slate-500 mt-1">Lahan yang disetujui akan masuk ke Manajemen Data Spasial untuk dibuat titik dan polygon.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr>
                                    <th class="px-5 py-4">Pengaju</th>
                                    <th class="px-5 py-4">Lahan</th>
                                    <th class="px-5 py-4">Wilayah</th>
                                    <th class="px-5 py-4">Luas</th>
                                    <th class="px-5 py-4">Status</th>
                                    <th class="px-5 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-primary-100">
                                @forelse($pendingLahan as $item)
                                    @php
                                        $lahanId = $ambil($item, ['id']);
                                        $lahanDetailPayload = [
                                            'id' => $lahanId,
                                            'nama_petani' => $ambil($item, ['nama_petani','petani.nama_lengkap','user.nama_lengkap']),
                                            'email_petani' => $ambil($item, ['email_petani','petani.email','user.email']),
                                            'nama_lahan' => $ambil($item, ['nama_lahan','lahan.nama_lahan']),
                                            'pemilik_lahan' => $ambil($item, ['pemilik_lahan']),
                                            'nama_kecamatan' => $ambil($item, ['nama_kecamatan','kecamatan.nama_kecamatan']),
                                            'nama_kelurahan' => $ambil($item, ['nama_kelurahan','kelurahan.nama_kelurahan']),
                                            'luas_lahan_hektar' => $angka($ambil($item, ['luas_lahan_hektar'], 0)),
                                            'alamat_detail' => $ambil($item, ['alamat_detail'], ''),
                                            'status_verifikasi' => $ambil($item, ['status_verifikasi'], 'PENDING'),
                                        ];
                                        $lahanDetailJson = htmlspecialchars(json_encode($lahanDetailPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
                                    @endphp
                                    <tr class="{{ (string)request('lahan_id') === (string)$lahanId ? 'bg-primary-50' : '' }}">
                                        <td class="px-5 py-4">
                                            <p class="font-bold text-primary-900">{{ $ambil($item, ['nama_petani','petani.nama_lengkap','user.nama_lengkap']) }}</p>
                                            <p class="text-xs text-slate-500">{{ $ambil($item, ['email_petani','petani.email','user.email']) }}</p>
                                        </td>
                                        <td class="px-5 py-4">
                                            <p class="font-bold text-primary-900">{{ $ambil($item, ['nama_lahan','lahan.nama_lahan']) }}</p>
                                            <p class="text-xs text-slate-500">Pemilik: {{ $ambil($item, ['pemilik_lahan']) }}</p>
                                            <p class="text-xs text-slate-500">{{ $ambil($item, ['alamat_detail'], '') }}</p>
                                        </td>
                                        <td class="px-5 py-4">
                                            <p class="text-slate-700">{{ $ambil($item, ['nama_kecamatan','kecamatan.nama_kecamatan']) }}</p>
                                            <p class="text-xs text-slate-500">{{ $ambil($item, ['nama_kelurahan','kelurahan.nama_kelurahan']) }}</p>
                                        </td>
                                        <td class="px-5 py-4 text-slate-700">{{ $angka($ambil($item, ['luas_lahan_hektar'], 0)) }} Ha</td>
                                        <td class="px-5 py-4"><span class="px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200">PENDING</span></td>
                                        <td class="px-5 py-4">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                <button type="button"
                                                        class="btnDetailLahan px-4 py-2 rounded-xl bg-white text-primary-700 border border-primary-100 font-bold hover:bg-primary-50 transition"
                                                        data-lahan="{!! $lahanDetailJson !!}"
                                                        data-approve-url="{{ url('/petugas/verifikasi-lahan/' . $lahanId . '/diterima') }}"
                                                        data-reject-url="{{ url('/petugas/verifikasi-lahan/' . $lahanId . '/ditolak') }}">
                                                    Detail
                                                </button>
                                                <form method="POST" action="{{ url('/petugas/verifikasi-lahan/' . $lahanId . '/diterima') }}" onsubmit="return confirm('Setujui pengajuan lahan ini? Pastikan detail pengajuan sudah benar.');">
                                                    @csrf
                                                    <button class="px-4 py-2 rounded-xl bg-green-50 text-green-700 border border-green-200 font-bold hover:bg-green-600 hover:text-white transition">Setujui</button>
                                                </form>
                                                <button type="button"
                                                        class="btnTolakLahan px-4 py-2 rounded-xl bg-red-50 text-red-600 border border-red-200 font-bold hover:bg-red-600 hover:text-white transition"
                                                        data-reject-url="{{ url('/petugas/verifikasi-lahan/' . $lahanId . '/ditolak') }}"
                                                        data-nama="{{ $ambil($item, ['nama_lahan','lahan.nama_lahan']) }}">
                                                    Tolak
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Belum ada pengajuan lahan baru.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="soft-card bg-white rounded-2xl border border-primary-100 overflow-hidden">
                    <div class="px-5 py-4 border-b border-primary-100">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="text-xl font-extrabold text-primary-900">Antrean Hasil Panen</h2>
                            <span data-petugas-pending-panen class="px-3 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-xs font-extrabold">{{ is_countable($panenPending) ? count($panenPending) : 0 }} belum diverifikasi</span>
                        </div>
                        <p class="text-sm text-slate-500 mt-1">Menampilkan pengaju, lahan, bibit, tanggal tanam, tanggal panen, dan hasil panen.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr>
                                    <th class="px-5 py-4">Pengaju</th>
                                    <th class="px-5 py-4">Lahan</th>
                                    <th class="px-5 py-4">Bibit</th>
                                    <th class="px-5 py-4">Tanggal Tanam</th>
                                    <th class="px-5 py-4">Tanggal Panen</th>
                                    <th class="px-5 py-4">Hasil</th>
                                    <th class="px-5 py-4">Status</th>
                                    <th class="px-5 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-primary-100">
                                @forelse($panenPending as $panen)
                                    <tr class="{{ (string)request('id') === (string)$ambil($panen, ['id'], '') ? 'bg-primary-50' : '' }}">
                                        <td class="px-5 py-4">
                                            <p class="font-bold text-primary-900">{{ $ambil($panen, ['nama_petani','petani.nama_lengkap','user.nama_lengkap']) }}</p>
                                            <p class="text-xs text-slate-500">Email: {{ $ambil($panen, ['email_petani','petani.email','user.email']) }}</p>
                                            <p class="text-xs text-slate-500">No HP: {{ $ambil($panen, ['no_hp_petani','petani.no_hp','user.no_hp']) }}</p>
                                        </td>
                                        <td class="px-5 py-4">
                                            <p class="font-bold text-primary-900">{{ $ambil($panen, ['nama_lahan','lahan.nama_lahan']) }}</p>
                                            <p class="text-xs text-slate-500">Pemilik: {{ $ambil($panen, ['pemilik_lahan','lahan.pemilik_lahan']) }}</p>
                                            <p class="text-xs text-slate-500">Luas: {{ $angka($ambil($panen, ['luas_lahan_hektar','lahan.luas_lahan_hektar'], 0)) }} Ha</p>
                                        </td>
                                        <td class="px-5 py-4">
                                            <p class="font-bold text-primary-900">{{ $ambil($panen, ['nama_bibit','bibit.nama_bibit']) }}</p>
                                            <p class="text-xs text-slate-500">Varietas: {{ $ambil($panen, ['varietas','bibit.varietas']) }}</p>
                                            <p class="text-xs text-slate-500">Masa tanam: {{ $ambil($panen, ['masa_tanam_hari','bibit.masa_tanam_hari'], '-') }} hari</p>
                                        </td>
                                        <td class="px-5 py-4 text-slate-700">{{ $ambil($panen, ['tanggal_tanam']) }}</td>
                                        <td class="px-5 py-4 text-slate-700">{{ $ambil($panen, ['tanggal_panen']) }}</td>
                                        <td class="px-5 py-4"><p class="font-extrabold text-primary-700">{{ $angka($ambil($panen, ['hasil_panen'], 0)) }} Ton</p></td>
                                        <td class="px-5 py-4"><span class="px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200">{{ $ambil($panen, ['status_verifikasi'], 'PENDING') }}</span></td>
                                        <td class="px-5 py-4">
                                            <div class="flex justify-end gap-2">
                                                <form method="POST" action="{{ url('/petugas/verifikasi-panen/' . $ambil($panen, ['id']) . '/diterima') }}">@csrf<button class="px-4 py-2 rounded-xl bg-green-50 text-green-700 border border-green-200 font-bold hover:bg-green-600 hover:text-white transition">Setujui</button></form>
                                                <form method="POST" action="{{ url('/petugas/verifikasi-panen/' . $ambil($panen, ['id']) . '/ditolak') }}">@csrf<button class="px-4 py-2 rounded-xl bg-red-50 text-red-600 border border-red-200 font-bold hover:bg-red-600 hover:text-white transition">Tolak</button></form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="px-5 py-8 text-center text-slate-500">Belum ada hasil panen berstatus PENDING.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="lahanDetailModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/45 px-4 py-6">
                    <div class="w-full max-w-4xl rounded-3xl bg-white shadow-2xl border border-primary-100 overflow-hidden">
                        <div class="px-6 py-5 border-b border-primary-100 bg-[#f7fced] flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-primary-700">Detail Pengajuan Lahan</p>
                                <h3 id="detailNamaLahan" class="text-2xl font-extrabold text-primary-900 mt-1">-</h3>
                                <p id="detailSubLahan" class="text-sm text-slate-500 mt-1">-</p>
                            </div>
                            <button type="button" class="modalClose w-10 h-10 rounded-2xl border border-primary-100 bg-white text-primary-700 font-extrabold hover:bg-primary-50">x</button>
                        </div>
                        <div class="p-6">
                            <div class="grid md:grid-cols-2 gap-4">
                                <div class="rounded-2xl border border-primary-100 p-4">
                                    <p class="text-xs font-bold text-slate-500 uppercase">Pengaju</p>
                                    <p id="detailPengaju" class="text-primary-900 font-bold mt-2">-</p>
                                    <p id="detailEmail" class="text-sm text-slate-500 mt-1">-</p>
                                </div>
                                <div class="rounded-2xl border border-primary-100 p-4">
                                    <p class="text-xs font-bold text-slate-500 uppercase">Pemilik Lahan</p>
                                    <p id="detailPemilik" class="text-primary-900 font-bold mt-2">-</p>
                                    <p id="detailStatus" class="text-sm text-amber-700 mt-1">PENDING</p>
                                </div>
                                <div class="rounded-2xl border border-primary-100 p-4">
                                    <p class="text-xs font-bold text-slate-500 uppercase">Wilayah</p>
                                    <p id="detailWilayah" class="text-primary-900 font-bold mt-2">-</p>
                                </div>
                                <div class="rounded-2xl border border-primary-100 p-4">
                                    <p class="text-xs font-bold text-slate-500 uppercase">Estimasi Luas</p>
                                    <p id="detailLuas" class="text-primary-900 font-bold mt-2">0 Ha</p>
                                </div>
                                <div class="md:col-span-2 rounded-2xl border border-primary-100 p-4">
                                    <p class="text-xs font-bold text-slate-500 uppercase">Alamat Detail</p>
                                    <p id="detailAlamat" class="text-primary-900 font-semibold mt-2 leading-relaxed">-</p>
                                </div>
                            </div>

                            <div class="mt-6 flex flex-col sm:flex-row justify-end gap-3">
                                <button type="button" id="detailRejectButton" class="px-5 py-3 rounded-2xl bg-red-50 text-red-600 border border-red-200 font-bold hover:bg-red-600 hover:text-white transition">Tolak</button>
                                <form method="POST" action="#" id="detailApproveForm" onsubmit="return confirm('Setujui pengajuan lahan ini? Pastikan seluruh detail pengajuan sudah sesuai.');">
                                    @csrf
                                    <button class="w-full sm:w-auto px-5 py-3 rounded-2xl bg-green-50 text-green-700 border border-green-200 font-bold hover:bg-green-600 hover:text-white transition">Setujui Pengajuan</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="lahanRejectModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/45 px-4 py-6">
                    <div class="w-full max-w-xl rounded-3xl bg-white shadow-2xl border border-red-100 overflow-hidden">
                        <div class="px-6 py-5 border-b border-red-100 bg-red-50 flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-red-600">Tolak Pengajuan</p>
                                <h3 id="rejectNamaLahan" class="text-xl font-extrabold text-red-900 mt-1">-</h3>
                                <p class="text-sm text-red-700 mt-1">Alasan ini akan terlihat oleh petani sebagai dasar perbaikan.</p>
                            </div>
                            <button type="button" class="modalClose w-10 h-10 rounded-2xl border border-red-100 bg-white text-red-600 font-extrabold hover:bg-red-50">x</button>
                        </div>
                        <form method="POST" action="#" id="rejectLahanForm" class="p-6 space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-bold text-slate-600 mb-2">Alasan Penolakan</label>
                                <textarea name="alasan_penolakan" rows="5" required minlength="5" maxlength="700"
                                          class="w-full rounded-2xl border border-red-100 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-red-200"
                                          placeholder="Contoh: alamat lahan belum lengkap, lokasi tidak sesuai wilayah, atau dokumen pendukung perlu diperbaiki."></textarea>
                            </div>
                            <div class="flex flex-col sm:flex-row justify-end gap-3">
                                <button type="button" class="modalClose px-5 py-3 rounded-2xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition">Batal</button>
                                <button class="px-5 py-3 rounded-2xl bg-red-600 text-white font-bold hover:bg-red-700 transition">Kirim Penolakan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if($page === 'manajemen-data-spasial')
            <div class="space-y-5">
                <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-5">
                    <div>
                        <p class="text-primary-700 text-xs font-bold uppercase tracking-[0.22em] mb-2">Manajemen Data Spasial</p>
                        <h1 class="text-2xl md:text-3xl font-extrabold text-primary-900">Pemetaan Lahan Sawah</h1>
                        <p class="text-sm text-slate-500 mt-2 max-w-3xl">Pilih lahan terlebih dahulu, lalu tentukan titik tengah, gambar batas area, dan lengkapi informasi lahan.</p>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 min-w-0">
                        <div class="rounded-2xl border border-primary-100 bg-white/80 px-4 py-3">
                            <p class="text-[10px] text-slate-400 font-bold uppercase">Total</p>
                            <p class="text-lg font-extrabold text-primary-900">{{ $totalSpasial }}</p>
                        </div>
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                            <p class="text-[10px] text-amber-600 font-bold uppercase">Baru</p>
                            <p class="text-lg font-extrabold text-amber-700">{{ $belumDipetakan }}</p>
                        </div>
                        <div class="rounded-2xl border border-primary-100 bg-white/80 px-4 py-3">
                            <p class="text-[10px] text-slate-400 font-bold uppercase">Dipetakan</p>
                            <p class="text-lg font-extrabold text-primary-700">{{ $sudahDipetakan }}</p>
                        </div>
                        <div class="rounded-2xl border border-primary-100 bg-white/80 px-4 py-3">
                            <p class="text-[10px] text-slate-400 font-bold uppercase">Lengkap</p>
                            <p class="text-lg font-extrabold text-primary-900">{{ $angka($persentaseLengkap, 0) }}%</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-5">
                    <section class="spatial-panel rounded-2xl overflow-hidden">
                        <div class="px-5 py-4 border-b border-primary-100 bg-[#f7fced]">
                            <p class="spatial-section-title text-xs text-primary-700 font-bold uppercase">Pilih Lahan</p>
                            <h2 class="text-lg font-extrabold text-primary-900 mt-1">Sumber Data Pemetaan</h2>
                        </div>
                        <div class="p-4 space-y-4">
                            <div class="grid md:grid-cols-2 gap-3">
                                <button type="button" class="spatial-choice sourceToggle is-active rounded-2xl px-4 py-3 text-left transition" data-source="baru">
                                    <span class="block text-sm font-extrabold">Lahan Belum Dipetakan</span>
                                    <span class="block text-xs mt-1">{{ $lahanBaruSpasial->count() }} disetujui, belum memiliki polygon</span>
                                </button>
                                <button type="button" class="spatial-choice sourceToggle rounded-2xl px-4 py-3 text-left transition" data-source="lama">
                                    <span class="block text-sm font-extrabold">Lahan Sudah Dipetakan</span>
                                    <span class="block text-xs mt-1">{{ $lahanLamaSpasial->count() }} data memiliki polygon</span>
                                </button>
                            </div>

                            <div>
                                <div id="sourceListBaru" class="spatial-list space-y-2">
                                    @forelse($lahanBaruSpasial as $item)
                                        <button type="button" class="spatial-row btnPilihLahan w-full rounded-2xl p-4 text-left transition hover:bg-primary-50" data-source="baru" data-lahan-id="{{ $ambil($item, ['id']) }}">
                                            <span class="flex items-start justify-between gap-3">
                                                <span>
                                                    <span class="block font-extrabold text-primary-900">{{ $ambil($item, ['nama_lahan']) }}</span>
                                                    <span class="block text-xs text-slate-500 mt-1">{{ $ambil($item, ['nama_kecamatan']) }} / {{ $ambil($item, ['nama_kelurahan']) }}</span>
                                                </span>
                                                <span class="px-2 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold">BELUM DIPETAKAN</span>
                                            </span>
                                            <span class="block text-xs text-slate-500 mt-3">{{ $ambil($item, ['alamat_detail'], '') }} - Sudah disetujui</span>
                                        </button>
                                    @empty
                                        <div class="rounded-2xl border border-primary-100 bg-white px-4 py-8 text-center text-sm text-slate-500">Tidak ada lahan disetujui yang menunggu pemetaan.</div>
                                    @endforelse
                                </div>

                                <div id="sourceListLama" class="spatial-list space-y-2 hidden">
                                    @forelse($lahanLamaSpasial as $item)
                                        <button type="button" class="spatial-row btnPilihLahan w-full rounded-2xl p-4 text-left transition hover:bg-primary-50" data-source="lama" data-lahan-id="{{ $ambil($item, ['id']) }}">
                                            <span class="flex items-start justify-between gap-3">
                                                <span>
                                                    <span class="block font-extrabold text-primary-900">{{ $ambil($item, ['nama_lahan']) }}</span>
                                                    <span class="block text-xs text-slate-500 mt-1">{{ $ambil($item, ['nama_kecamatan']) }} / {{ $ambil($item, ['nama_kelurahan']) }}</span>
                                                </span>
                                                <span class="px-2 py-1 rounded-full bg-green-50 text-green-700 border border-green-200 text-[10px] font-bold">SUDAH DIPETAKAN</span>
                                            </span>
                                            <span class="block text-xs text-slate-500 mt-3">{{ $angka($ambil($item, ['luas_lahan_hektar'], 0)) }} Ha - {{ $ambil($item, ['pemilik_lahan','nama_petani']) }} - polygon tersedia</span>
                                        </button>
                                    @empty
                                        <div class="rounded-2xl border border-primary-100 bg-white px-4 py-8 text-center text-sm text-slate-500">Belum ada lahan yang sudah memiliki polygon.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="spatial-panel rounded-2xl p-4">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                            <div>
                                <h2 class="text-lg font-extrabold text-primary-900">Peta Kerja Petugas</h2>
                                <p id="selectedMapLabel" class="text-sm text-slate-500 mt-1">Batas Kabupaten Barito Kuala dan layer Kecamatan Belawang tersedia di kontrol peta. Pilih lahan untuk mulai mengatur titik dan batas area.</p>
                            </div>
                        </div>
                        <div class="spatial-map-shell">
                            <div id="petugasSpasialMap"></div>
                            <div class="spatial-map-tools">
                                <button type="button" id="btnSetPointMode" class="map-tool px-4 py-2 rounded-xl font-bold text-sm">Titik Tengah</button>
                                <button type="button" id="btnPolygonMode" class="map-tool is-primary px-4 py-2 rounded-xl font-bold text-sm">Gambar Batas</button>
                                <button type="button" id="btnFinishPolygon" class="map-tool px-4 py-2 rounded-xl font-bold text-sm">Selesai</button>
                                <button type="button" id="btnUndoPolygonPoint" class="map-tool px-4 py-2 rounded-xl font-bold text-sm">Urungkan Titik</button>
                                <button type="button" id="btnClearPolygon" class="map-tool is-danger px-4 py-2 rounded-xl font-bold text-sm">Kosongkan Batas</button>
                            </div>
                        </div>
                        <div id="polygonProgress" class="mt-3 rounded-2xl border border-primary-100 bg-primary-50 px-4 py-3 text-sm text-primary-900">
                            Pilih lahan, lalu gunakan tombol di dalam peta untuk mengatur titik tengah dan batas area. Titik batas minimal 3 dan dapat lebih dari 4 titik.
                        </div>
                    </section>
                </div>

                <div id="spatialWorkspace" class="spatial-workspace is-locked spatial-panel rounded-2xl p-5">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 border-b border-primary-100 pb-4">
                        <div>
                            <p id="selectedSourceLabel" class="text-xs text-primary-700 font-bold uppercase tracking-[0.18em]">Belum memilih lahan</p>
                            <h2 id="selectedLahanTitle" class="text-xl font-extrabold text-primary-900 mt-1">Informasi Titik dan Batas Area</h2>
                            <p id="selectedLahanMeta" class="text-sm text-slate-500 mt-1">Pilih lahan belum dipetakan atau lahan sudah dipetakan dari panel kiri untuk membuka formulir pemetaan.</p>
                        </div>
                        <span id="formModeBadge" class="w-fit px-3 py-1 rounded-full bg-slate-50 text-slate-500 text-xs font-bold border border-slate-200">Terkunci</span>
                    </div>

                    <div class="spatial-empty-state py-12 text-center">
                        <p class="text-primary-900 font-extrabold">Pilih salah satu lahan untuk mulai bekerja.</p>
                        <p class="text-sm text-slate-500 mt-2">Form dibuat bertahap agar petugas dapat memeriksa sumber lahan, lokasi peta, lalu menyimpan perubahan dengan lebih rapi.</p>
                    </div>

                    <div class="spatial-form-body pt-5">
                        <form method="POST" action="{{ url('/petugas/spasial/simpan') }}" id="spasialForm" class="space-y-5">
                            @csrf
                            <input type="hidden" name="_method" id="form_method" value="">
                            <input type="hidden" name="lahan_id" id="lahan_id">
                            <input type="hidden" name="user_id" id="user_id">

                            <div class="spatial-form-section">
                                <p class="text-xs font-extrabold text-primary-700 uppercase tracking-[0.18em] mb-4">Identitas Lahan</p>
                                <div class="grid lg:grid-cols-2 gap-4">
                                    <div class="spatial-field"><label class="block text-xs font-bold text-slate-500">Nama Lahan</label><input name="nama_lahan" id="nama_lahan" class="w-full" required></div>
                                    <div class="spatial-field"><label class="block text-xs font-bold text-slate-500">Pemilik Lahan</label><input name="pemilik_lahan" id="pemilik_lahan" class="w-full"></div>
                                </div>
                            </div>

                            <div class="spatial-form-section">
                                <p class="text-xs font-extrabold text-primary-700 uppercase tracking-[0.18em] mb-4">Wilayah dan Klasifikasi</p>
                                <div class="grid lg:grid-cols-2 gap-4">
                                    <div class="spatial-field">
                                        <label class="block text-xs font-bold text-slate-500">Kecamatan</label>
                                        <select name="kecamatan_id" id="kecamatan_id" class="w-full" required>
                                            <option value="">Pilih kecamatan</option>
                                            @foreach(data_get($referensi, 'kecamatan', []) as $item)
                                                <option value="{{ $ambil($item, ['id']) }}">{{ $ambil($item, ['nama_kecamatan','nama']) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="spatial-field">
                                        <label class="block text-xs font-bold text-slate-500">Kelurahan</label>
                                        <select name="kelurahan_id" id="kelurahan_id" class="w-full">
                                            <option value="">Pilih kelurahan</option>
                                            @foreach(data_get($referensi, 'kelurahan', []) as $item)
                                                <option value="{{ $ambil($item, ['id']) }}" data-kecamatan="{{ $ambil($item, ['kecamatan_id'], '') }}">{{ $ambil($item, ['nama_kelurahan','nama']) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="grid lg:grid-cols-2 gap-4 mt-4">
                                    <div class="spatial-field">
                                        <label class="block text-xs font-bold text-slate-500">Tipe Lahan</label>
                                        <select name="tipe_lahan_id" id="tipe_lahan_id" class="w-full">
                                            <option value="">Pilih tipe lahan</option>
                                            @foreach(data_get($referensi, 'tipe_lahan', []) as $item)
                                                <option value="{{ $ambil($item, ['id']) }}">{{ $ambil($item, ['nama_tipe','nama']) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="spatial-field"><label class="block text-xs font-bold text-slate-500">Tahun Basis</label><select name="tahun_lbs" id="tahun_lbs" class="w-full"><option value="2024">2024</option><option value="2017">2017</option></select></div>
                                </div>
                            </div>

                            <div class="spatial-form-section">
                                <p class="text-xs font-extrabold text-primary-700 uppercase tracking-[0.18em] mb-4">Ukuran dan Titik Tengah</p>
                                <div class="grid lg:grid-cols-3 gap-4">
                                <div class="spatial-field">
                                    <label class="block text-xs font-bold text-slate-500">Luas Lahan (Ha)</label>
                                    <input name="luas_lahan_hektar" id="luas_lahan_hektar" type="number" step="0.01" class="w-full" required>
                                    <div class="mt-2 flex items-center justify-between gap-2">
                                        <p id="areaEstimateText" class="text-xs text-slate-500">Luas dari peta belum tersedia.</p>
                                        <button type="button" id="btnUseMapArea" class="hidden shrink-0 px-3 py-1 rounded-lg bg-primary-50 text-primary-700 border border-primary-100 text-xs font-bold">Gunakan</button>
                                    </div>
                                </div>
                                <div class="spatial-field"><label class="block text-xs font-bold text-slate-500">Latitude</label><input name="latitude" id="latitude" type="number" step="any" class="w-full" required></div>
                                <div class="spatial-field"><label class="block text-xs font-bold text-slate-500">Longitude</label><input name="longitude" id="longitude" type="number" step="any" class="w-full" required></div>
                                </div>
                            </div>

                            <div class="spatial-form-section">
                                <p class="text-xs font-extrabold text-primary-700 uppercase tracking-[0.18em] mb-4">Alamat dan Batas Area</p>
                                <div class="spatial-field"><label class="block text-xs font-bold text-slate-500">Alamat Detail</label><textarea name="alamat_detail" id="alamat_detail" rows="2" class="w-full"></textarea></div>
                                <div class="spatial-field mt-4">
                                    <label class="block text-xs font-bold text-slate-500">Data Batas Area Otomatis</label>
                                    <textarea name="polygon_geojson" id="polygon_geojson" rows="3" class="w-full font-mono text-xs bg-slate-50" required readonly placeholder="Klik tombol Gambar Batas, lalu klik minimal 3 titik di peta."></textarea>
                                    <p id="polygonStatusText" class="text-xs text-slate-500 mt-2">Belum ada batas area yang siap disimpan.</p>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-3">
                                <button class="btn-green flex-1 rounded-2xl py-3 font-extrabold transition">Simpan Data Spasial</button>
                                <button type="button" id="btnResetForm" class="px-5 py-3 rounded-2xl border border-primary-100 text-primary-700 font-bold hover:bg-primary-50 transition">Reset</button>
                            </div>
                        </form>
                        <form method="POST" action="#" id="deleteSpasialForm" class="mt-3 hidden" onsubmit="return confirm('Kosongkan batas area lahan ini? Data lahan tetap tersimpan.');">
                            @csrf
                            @method('DELETE')
                            <button class="w-full px-5 py-3 rounded-2xl bg-red-50 text-red-600 border border-red-200 font-bold hover:bg-red-600 hover:text-white transition">Kosongkan Batas Area Lahan Terpilih</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if($page === 'input-parameter-lingkungan')
            <div class="grid xl:grid-cols-2 gap-6">
                <div class="soft-card bg-white rounded-2xl border border-primary-100 p-5">
                    <h2 class="text-xl font-extrabold text-primary-900 mb-4">Input Parameter Lingkungan</h2>
                    <form method="POST" action="{{ url('/petugas/parameter-lingkungan/simpan') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-2">Lahan</label>
                            <select name="lahan_id" class="w-full" required>
                                <option value="">Pilih lahan</option>
                                @foreach($lahanDiterima as $item)
                                    <option value="{{ $ambil($item, ['id']) }}">{{ $ambil($item, ['nama_lahan']) }} - {{ $ambil($item, ['pemilik_lahan']) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-slate-500 mb-2">Tanggal Cek</label><input type="date" name="tanggal_cek" class="w-full" value="{{ date('Y-m-d') }}" required></div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 mb-2">Status Air</label>
                                <select name="status_air" class="w-full">
                                    <option value="Normal">Normal</option>
                                    <option value="Surut">Surut</option>
                                    <option value="Pasang">Pasang</option>
                                    <option value="Banjir">Banjir</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-slate-500 mb-2">pH Air</label><input type="number" step="0.01" name="ph_air" class="w-full"></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-2">Tinggi Muka Air</label><input type="number" step="0.01" name="tinggi_muka_air" class="w-full"></div>
                        </div>
                        <div><label class="block text-xs font-bold text-slate-500 mb-2">Catatan Petugas</label><textarea name="catatan_petugas" rows="3" class="w-full"></textarea></div>
                        <button class="btn-green rounded-2xl px-6 py-3 font-extrabold transition">Simpan Parameter</button>
                    </form>
                </div>

                <div class="soft-card bg-white rounded-2xl border border-primary-100 p-5">
                    <h2 class="text-xl font-extrabold text-primary-900 mb-4">Riwayat Monitoring</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead><tr><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">Lahan</th><th class="px-4 py-3">pH</th><th class="px-4 py-3">Air</th></tr></thead>
                            <tbody class="divide-y divide-primary-100">
                                @forelse($monitoring as $row)
                                    <tr><td class="px-4 py-3">{{ $ambil($row, ['tanggal_cek','created_at']) }}</td><td class="px-4 py-3">{{ $ambil($row, ['nama_lahan','lahan.nama_lahan']) }}</td><td class="px-4 py-3">{{ $ambil($row, ['ph_air']) }}</td><td class="px-4 py-3">{{ $ambil($row, ['status_air']) }}</td></tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Belum ada data monitoring.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@if($page === 'verifikasi-data-petani')
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const detailModal = document.getElementById('lahanDetailModal');
                const rejectModal = document.getElementById('lahanRejectModal');
                const approveForm = document.getElementById('detailApproveForm');
                const rejectForm = document.getElementById('rejectLahanForm');
                const detailRejectButton = document.getElementById('detailRejectButton');
                let activeRejectUrl = '';
                let activeRejectName = '';

                function showModal(modal) {
                    modal?.classList.remove('hidden');
                    modal?.classList.add('flex');
                }

                function hideModal(modal) {
                    modal?.classList.add('hidden');
                    modal?.classList.remove('flex');
                }

                function setText(id, value) {
                    const el = document.getElementById(id);
                    if (el) el.textContent = value || '-';
                }

                function openReject(url, name) {
                    activeRejectUrl = url || activeRejectUrl;
                    activeRejectName = name || activeRejectName || 'Pengajuan lahan';
                    if (rejectForm) {
                        rejectForm.action = activeRejectUrl || '#';
                        rejectForm.reset();
                    }
                    setText('rejectNamaLahan', activeRejectName);
                    hideModal(detailModal);
                    showModal(rejectModal);
                }

                document.querySelectorAll('.btnDetailLahan').forEach(button => {
                    button.addEventListener('click', function () {
                        let data = {};
                        try {
                            data = JSON.parse(this.dataset.lahan || '{}');
                        } catch (e) {
                            data = {};
                        }

                        activeRejectUrl = this.dataset.rejectUrl || '';
                        activeRejectName = data.nama_lahan || 'Pengajuan lahan';
                        if (approveForm) approveForm.action = this.dataset.approveUrl || '#';
                        if (detailRejectButton) detailRejectButton.dataset.rejectUrl = activeRejectUrl;

                        setText('detailNamaLahan', data.nama_lahan);
                        setText('detailSubLahan', `${data.nama_kecamatan || '-'} / ${data.nama_kelurahan || '-'}`);
                        setText('detailPengaju', data.nama_petani);
                        setText('detailEmail', data.email_petani);
                        setText('detailPemilik', data.pemilik_lahan);
                        setText('detailStatus', data.status_verifikasi || 'PENDING');
                        setText('detailWilayah', `${data.nama_kecamatan || '-'} / ${data.nama_kelurahan || '-'}`);
                        setText('detailLuas', `${data.luas_lahan_hektar || '0'} Ha`);
                        setText('detailAlamat', data.alamat_detail);
                        showModal(detailModal);
                    });
                });

                document.querySelectorAll('.btnTolakLahan').forEach(button => {
                    button.addEventListener('click', function () {
                        openReject(this.dataset.rejectUrl || '', this.dataset.nama || 'Pengajuan lahan');
                    });
                });

                detailRejectButton?.addEventListener('click', function () {
                    openReject(this.dataset.rejectUrl || activeRejectUrl, activeRejectName);
                });

                document.querySelectorAll('.modalClose').forEach(button => {
                    button.addEventListener('click', function () {
                        hideModal(detailModal);
                        hideModal(rejectModal);
                    });
                });

                [detailModal, rejectModal].forEach(modal => {
                    modal?.addEventListener('click', function (event) {
                        if (event.target === modal) hideModal(modal);
                    });
                });
            });
        </script>
    @endpush
@endif

@if($page === 'manajemen-data-spasial')
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const mapEl = document.getElementById('petugasSpasialMap');
                if (!mapEl || typeof L === 'undefined') return;

                const batasWilayah = @json($batasWilayah);
                const batasKecamatan = @json($batasKecamatan);
                const semuaLahanRaw = @json($spasialRows);
                const antreanLahanRaw = @json($lahanBaruSpasial);
                const highlightLahanId = @json((string)($highlightLahanId ?? ''));
                const updateBaseUrl = @json(url('/petugas/spasial'));
                const storeUrl = @json(url('/petugas/spasial/simpan'));

                const batolaCenter = [-3.05, 114.62];
                const batolaBounds = L.latLngBounds([[-3.55, 114.20], [-2.45, 115.05]]);
                const map = L.map('petugasSpasialMap', { maxBounds: batolaBounds, maxBoundsViscosity: 0.75, doubleClickZoom: false }).setView(batolaCenter, 10);

                const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                });

                const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    maxZoom: 19,
                    attribution: 'Tiles &copy; Esri'
                }).addTo(map);

                const batasKabupatenGroup = L.layerGroup().addTo(map);
                const batasKecamatanGroup = L.layerGroup().addTo(map);

                L.control.layers({
                    'Satelit': satelliteLayer,
                    'Peta Jalan': osmLayer
                }, {
                    'Batas Kabupaten': batasKabupatenGroup,
                    'Kecamatan Belawang': batasKecamatanGroup
                }, {
                    position: 'topright',
                    collapsed: true
                }).addTo(map);

                map.fitBounds(batolaBounds);

                let marker = null;
                let polygonMode = false;
                let polygonPoints = [];
                let polygonLayer = null;
                let batasLayer = null;

                const form = document.getElementById('spasialForm');
                const methodInput = document.getElementById('form_method');
                const modeBadge = document.getElementById('formModeBadge');
                const lahanIdInput = document.getElementById('lahan_id');
                const userInput = document.getElementById('user_id');
                const latInput = document.getElementById('latitude');
                const lngInput = document.getElementById('longitude');
                const polygonInput = document.getElementById('polygon_geojson');
                const luasInput = document.getElementById('luas_lahan_hektar');
                const kecamatanSelect = document.getElementById('kecamatan_id');
                const kelurahanSelect = document.getElementById('kelurahan_id');
                const workspace = document.getElementById('spatialWorkspace');
                const selectedSourceLabel = document.getElementById('selectedSourceLabel');
                const selectedLahanTitle = document.getElementById('selectedLahanTitle');
                const selectedLahanMeta = document.getElementById('selectedLahanMeta');
                const selectedMapLabel = document.getElementById('selectedMapLabel');
                const deleteForm = document.getElementById('deleteSpasialForm');
                const polygonProgress = document.getElementById('polygonProgress');
                const polygonStatusText = document.getElementById('polygonStatusText');
                const btnSetPointMode = document.getElementById('btnSetPointMode');
                const btnPolygonMode = document.getElementById('btnPolygonMode');
                const btnFinishPolygon = document.getElementById('btnFinishPolygon');
                const btnUndoPolygonPoint = document.getElementById('btnUndoPolygonPoint');
                const btnClearPolygon = document.getElementById('btnClearPolygon');
                const areaEstimateText = document.getElementById('areaEstimateText');
                const btnUseMapArea = document.getElementById('btnUseMapArea');

                function normalizeList(value) {
                    if (Array.isArray(value)) return value;
                    if (value && typeof value === 'object') return Object.values(value);
                    return [];
                }

                const semuaLahan = normalizeList(semuaLahanRaw);
                const antreanLahan = normalizeList(antreanLahanRaw);
                const dataById = new Map([...semuaLahan, ...antreanLahan].map(item => [String(item.id), item]));
                let selectedLahanId = null;
                let manualLuasEdited = false;
                let lastMapAreaHa = 0;

                function setValue(id, value) {
                    const el = document.getElementById(id);
                    if (el) el.value = value ?? '';
                }

                function setFormMode(id, source) {
                    if (!form || !methodInput) return;

                    if (id) {
                        form.action = `${updateBaseUrl}/${id}`;
                        methodInput.value = 'PUT';
                        if (modeBadge) {
                            modeBadge.textContent = source === 'baru' ? 'Belum Dipetakan' : 'Sudah Dipetakan';
                            modeBadge.className = source === 'baru'
                                ? 'px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200'
                                : 'px-3 py-1 rounded-full bg-primary-50 text-primary-700 text-xs font-bold border border-primary-100';
                        }
                    } else {
                        form.action = storeUrl;
                        methodInput.value = '';
                        if (modeBadge) {
                            modeBadge.textContent = 'Belum Dipetakan';
                            modeBadge.className = 'px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200';
                        }
                    }
                }

                function setSource(source) {
                    document.querySelectorAll('.sourceToggle').forEach(button => {
                        button.classList.toggle('is-active', button.dataset.source === source);
                    });

                    document.getElementById('sourceListBaru')?.classList.toggle('hidden', source !== 'baru');
                    document.getElementById('sourceListLama')?.classList.toggle('hidden', source !== 'lama');
                }

                function setSelectedRow(id) {
                    document.querySelectorAll('.btnPilihLahan').forEach(button => {
                        button.classList.toggle('is-active', String(button.dataset.lahanId || '') === String(id || ''));
                    });
                }

                function hitungLuasHektar(points) {
                    if (!Array.isArray(points) || points.length < 3) return 0;

                    const latRata = points.reduce((sum, point) => sum + point.lat, 0) / points.length;
                    const meterPerDerajatLat = 111320;
                    const meterPerDerajatLng = 111320 * Math.cos(latRata * Math.PI / 180);
                    const projected = points.map(point => ({
                        x: point.lng * meterPerDerajatLng,
                        y: point.lat * meterPerDerajatLat
                    }));

                    let luasMeter = 0;
                    for (let i = 0; i < projected.length; i += 1) {
                        const next = projected[(i + 1) % projected.length];
                        luasMeter += projected[i].x * next.y - next.x * projected[i].y;
                    }

                    return Math.abs(luasMeter) / 2 / 10000;
                }

                function updateLuasDariPeta(fillInput = false) {
                    lastMapAreaHa = hitungLuasHektar(polygonPoints);

                    if (lastMapAreaHa > 0) {
                        const formatted = lastMapAreaHa.toFixed(2);
                        if (areaEstimateText) areaEstimateText.textContent = `Estimasi peta: ${formatted} Ha`;
                        btnUseMapArea?.classList.remove('hidden');

                        if (fillInput && !manualLuasEdited && luasInput) {
                            luasInput.value = formatted;
                        }
                    } else {
                        if (areaEstimateText) areaEstimateText.textContent = 'Luas dari peta belum tersedia.';
                        btnUseMapArea?.classList.add('hidden');
                    }
                }

                function polygonMessage() {
                    if (!selectedLahanId) {
                        return 'Pilih lahan, lalu gunakan tombol peta untuk mengatur titik tengah dan batas area. Titik batas minimal 3 dan dapat lebih dari 4 titik.';
                    }

                    if (polygonMode) {
                        return `Mode gambar aktif. Klik peta untuk menambah titik batas area tanpa batas maksimum. Titik saat ini: ${polygonPoints.length}.`;
                    }

                    if (polygonPoints.length >= 3) {
                        return `Batas area siap disimpan dengan ${polygonPoints.length} titik.`;
                    }

                    return `Klik Titik Tengah untuk mengatur lokasi tengah, atau Gambar Batas untuk membuat area. Titik batas: ${polygonPoints.length}.`;
                }

                function updateMapTools() {
                    const hasSelection = Boolean(selectedLahanId);
                    const canDraw = hasSelection;

                    [btnSetPointMode, btnPolygonMode, btnFinishPolygon, btnUndoPolygonPoint, btnClearPolygon].forEach(button => {
                        if (button) button.disabled = !canDraw;
                    });

                    btnPolygonMode?.classList.toggle('is-active', polygonMode);
                    btnSetPointMode?.classList.toggle('is-active', canDraw && !polygonMode);

                    if (btnPolygonMode) {
                        btnPolygonMode.textContent = polygonMode ? 'Sedang Menggambar' : 'Gambar Batas';
                    }

                    if (btnFinishPolygon) btnFinishPolygon.disabled = !canDraw || polygonPoints.length < 3;
                    if (btnUndoPolygonPoint) btnUndoPolygonPoint.disabled = !canDraw || polygonPoints.length === 0;
                    if (btnClearPolygon) btnClearPolygon.disabled = !canDraw || polygonPoints.length === 0;

                    if (polygonProgress) polygonProgress.textContent = polygonMessage();
                    if (polygonStatusText) {
                        polygonStatusText.textContent = polygonPoints.length >= 3
                            ? `Batas area siap disimpan. Jumlah titik: ${polygonPoints.length}.`
                            : `Minimal 3 titik batas area diperlukan. Titik saat ini: ${polygonPoints.length}.`;
                    }
                }

                function drawBatasWilayah() {
                    if (!batasWilayah || !batasWilayah.type) return;

                    batasLayer = L.geoJSON(batasWilayah, {
                        interactive: false,
                        style: {
                            color: '#203c10',
                            weight: 2.4,
                            opacity: 0.9,
                            fillOpacity: 0,
                            fillColor: 'transparent',
                            dashArray: '6 6'
                        }
                    }).addTo(batasKabupatenGroup);

                    try {
                        map.fitBounds(batasLayer.getBounds(), { padding: [18, 18] });
                    } catch (e) {
                        map.fitBounds(batolaBounds);
                    }
                }

                function drawBatasKecamatan() {
                    const collection = batasKecamatan?.data || batasKecamatan;
                    if (!collection || !collection.type) return;

                    L.geoJSON(collection, {
                        interactive: false,
                        style: {
                            color: '#f59e0b',
                            weight: 2.4,
                            opacity: 0.95,
                            fillOpacity: 0,
                            fillColor: 'transparent',
                            dashArray: '8 6'
                        }
                    }).addTo(batasKecamatanGroup);
                }

                function setPoint(latlng) {
                    if (latInput) latInput.value = latlng.lat.toFixed(7);
                    if (lngInput) lngInput.value = latlng.lng.toFixed(7);
                    if (marker) marker.setLatLng(latlng);
                    else {
                        marker = L.marker(latlng, { draggable: true }).addTo(map);
                        marker.on('dragend', function(e) {
                            const pos = e.target.getLatLng();
                            if (latInput) latInput.value = pos.lat.toFixed(7);
                            if (lngInput) lngInput.value = pos.lng.toFixed(7);
                        });
                    }
                }

                function clearPolygonLayer() {
                    if (polygonLayer) map.removeLayer(polygonLayer);
                    polygonLayer = null;
                }

                function refreshPolygon() {
                    clearPolygonLayer();
                    if (polygonPoints.length > 0) {
                        const layers = [];

                        if (polygonPoints.length >= 3) {
                            layers.push(L.polygon(polygonPoints, { color: '#3e7d00', weight: 3, fillOpacity: 0.18 }));
                        } else if (polygonPoints.length >= 2) {
                            layers.push(L.polyline(polygonPoints, { color: '#3e7d00', weight: 3, dashArray: '6 6' }));
                        }

                        polygonPoints.forEach((point, index) => {
                            layers.push(L.circleMarker(point, {
                                radius: 5,
                                color: '#203c10',
                                weight: 2,
                                fillColor: '#65bd00',
                                fillOpacity: 0.9
                            }).bindTooltip(String(index + 1), { permanent: false }));
                        });

                        polygonLayer = L.layerGroup(layers).addTo(map);
                    }

                    if (polygonPoints.length >= 3) {
                        const coords = polygonPoints.map(p => [p.lng, p.lat]);
                        coords.push([polygonPoints[0].lng, polygonPoints[0].lat]);
                        if (polygonInput) polygonInput.value = JSON.stringify({ type: 'Polygon', coordinates: [coords] });
                        updateLuasDariPeta(true);
                        if (!marker && (!latInput?.value || !lngInput?.value)) {
                            const centroid = polygonPoints.reduce((acc, point) => ({
                                lat: acc.lat + point.lat / polygonPoints.length,
                                lng: acc.lng + point.lng / polygonPoints.length
                            }), { lat: 0, lng: 0 });
                            setPoint(L.latLng(centroid.lat, centroid.lng));
                        }
                    } else if (polygonInput) {
                        polygonInput.value = '';
                        updateLuasDariPeta(false);
                    }
                    updateMapTools();
                }

                function readGeometry(geojson) {
                    if (!geojson) return null;
                    try {
                        const parsed = typeof geojson === 'string' ? JSON.parse(geojson) : geojson;
                        return parsed.type === 'Feature' ? parsed.geometry : parsed;
                    } catch (e) {
                        return null;
                    }
                }

                function drawGeometry(geojson) {
                    const geometry = readGeometry(geojson);
                    clearPolygonLayer();
                    polygonPoints = [];

                    if (!geometry) return;

                    polygonLayer = L.geoJSON(geometry, {
                        style: { color: '#3e7d00', weight: 3, fillOpacity: 0.22 }
                    }).addTo(map);

                    const firstRing = geometry.type === 'Polygon'
                        ? geometry.coordinates?.[0]
                        : geometry.coordinates?.[0]?.[0];

                    if (Array.isArray(firstRing)) {
                        polygonPoints = firstRing.slice(0, -1).map(coord => L.latLng(coord[1], coord[0]));
                    }

                    updateLuasDariPeta(false);
                    updateMapTools();

                    try {
                        map.fitBounds(polygonLayer.getBounds(), { padding: [24, 24] });
                    } catch (e) {}
                }

                function filterKelurahan() {
                    if (!kecamatanSelect || !kelurahanSelect) return;
                    const kecamatanId = kecamatanSelect.value;

                    Array.from(kelurahanSelect.options).forEach(option => {
                        if (!option.value) {
                            option.hidden = false;
                            return;
                        }

                        const itemKecamatan = option.dataset.kecamatan || '';
                        option.hidden = kecamatanId && itemKecamatan && itemKecamatan !== kecamatanId;
                    });
                }

                function fillForm(data, source = 'lama') {
                    if (!data) return;

                    selectedLahanId = data.id ? String(data.id) : null;
                    manualLuasEdited = false;
                    lastMapAreaHa = 0;
                    setSource(source);
                    setSelectedRow(selectedLahanId);
                    workspace?.classList.remove('is-locked');
                    setFormMode(data.id, source);
                    if (lahanIdInput) lahanIdInput.value = data.id ?? '';
                    if (userInput) userInput.value = data.user_id ?? '';

                    setValue('nama_lahan', data.nama_lahan);
                    setValue('pemilik_lahan', data.pemilik_lahan || data.nama_petani);
                    setValue('kecamatan_id', data.kecamatan_id);
                    filterKelurahan();
                    setValue('kelurahan_id', data.kelurahan_id);
                    setValue('tipe_lahan_id', data.tipe_lahan_id);
                    setValue('tahun_lbs', data.tahun_lbs || '2024');
                    setValue('luas_lahan_hektar', data.luas_lahan_hektar);
                    setValue('alamat_detail', data.alamat_detail);
                    setValue('latitude', data.latitude);
                    setValue('longitude', data.longitude);
                    setValue('polygon_geojson', data.polygon_geojson || data.geojson || '');

                    const lokasi = [data.nama_kecamatan, data.nama_kelurahan].filter(Boolean).join(' / ') || 'Lokasi belum lengkap';
                    const pemilik = data.pemilik_lahan || data.nama_petani || 'Pemilik belum diisi';
                    if (selectedSourceLabel) selectedSourceLabel.textContent = source === 'baru' ? 'Lahan belum dipetakan' : 'Lahan sudah dipetakan';
                    if (selectedLahanTitle) selectedLahanTitle.textContent = data.nama_lahan || 'Lahan Sawah Terpilih';
                    if (selectedLahanMeta) selectedLahanMeta.textContent = `${lokasi} - ${pemilik}`;
                    if (selectedMapLabel) {
                        selectedMapLabel.textContent = `${data.nama_lahan || 'Lahan terpilih'} - klik peta untuk mengatur titik tengah atau gunakan Gambar Batas untuk membuat area.`;
                    }

                    const hasPolygon = Boolean(data.polygon_geojson || data.geojson);
                    if (deleteForm) {
                        deleteForm.action = data.id ? `${updateBaseUrl}/${data.id}` : '#';
                        deleteForm.classList.toggle('hidden', !data.id || !hasPolygon);
                    }

                    if (data.latitude && data.longitude) {
                        const latlng = L.latLng(parseFloat(data.latitude), parseFloat(data.longitude));
                        setPoint(latlng);
                        map.setView(latlng, 15);
                    } else if (marker) {
                        map.removeLayer(marker);
                        marker = null;
                    }

                    if (hasPolygon) {
                        drawGeometry(data.polygon_geojson || data.geojson);
                    } else {
                        clearPolygonLayer();
                        polygonPoints = [];
                        if (polygonInput) polygonInput.value = '';
                        updateLuasDariPeta(false);
                        updateMapTools();
                    }

                    form?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                function resetForm() {
                    selectedLahanId = null;
                    polygonMode = false;
                    manualLuasEdited = false;
                    lastMapAreaHa = 0;
                    form?.reset();
                    setFormMode(null, null);
                    workspace?.classList.add('is-locked');
                    setSelectedRow(null);
                    if (selectedSourceLabel) selectedSourceLabel.textContent = 'Belum memilih lahan';
                    if (selectedLahanTitle) selectedLahanTitle.textContent = 'Informasi Titik dan Batas Area';
                    if (selectedLahanMeta) selectedLahanMeta.textContent = 'Pilih lahan belum dipetakan atau lahan sudah dipetakan dari panel kiri untuk membuka formulir pemetaan.';
                    if (selectedMapLabel) selectedMapLabel.textContent = 'Batas Kabupaten Barito Kuala dan layer Kecamatan Belawang tersedia di kontrol peta. Pilih lahan untuk mulai mengatur titik dan batas area.';
                    if (deleteForm) {
                        deleteForm.action = '#';
                        deleteForm.classList.add('hidden');
                    }
                    if (lahanIdInput) lahanIdInput.value = '';
                    if (userInput) userInput.value = '';
                    if (marker) {
                        map.removeLayer(marker);
                        marker = null;
                    }
                    clearPolygonLayer();
                    polygonPoints = [];
                    if (polygonInput) polygonInput.value = '';
                    updateLuasDariPeta(false);
                    filterKelurahan();
                    updateMapTools();
                }

                drawBatasWilayah();
                drawBatasKecamatan();

                map.on('click', function(e) {
                    if (!selectedLahanId) {
                        if (selectedMapLabel) selectedMapLabel.textContent = 'Pilih lahan terlebih dahulu sebelum menentukan titik atau batas area.';
                        updateMapTools();
                        return;
                    }

                    if (polygonMode) {
                        polygonPoints.push(e.latlng);
                        refreshPolygon();
                    } else {
                        setPoint(e.latlng);
                        updateMapTools();
                    }
                });

                btnSetPointMode?.addEventListener('click', function () {
                    if (!selectedLahanId) {
                        if (selectedMapLabel) selectedMapLabel.textContent = 'Pilih lahan terlebih dahulu sebelum mengatur titik tengah.';
                        updateMapTools();
                        return;
                    }

                    polygonMode = false;
                    if (selectedMapLabel) selectedMapLabel.textContent = 'Mode titik tengah aktif. Klik satu lokasi di peta.';
                    updateMapTools();
                });

                btnPolygonMode?.addEventListener('click', function () {
                    if (!selectedLahanId) {
                        if (selectedMapLabel) selectedMapLabel.textContent = 'Pilih lahan terlebih dahulu sebelum menggambar batas area.';
                        updateMapTools();
                        return;
                    }

                    polygonMode = !polygonMode;
                    if (selectedMapLabel) selectedMapLabel.textContent = polygonMode
                        ? 'Mode gambar batas aktif. Klik peta berurutan mengelilingi lahan.'
                        : 'Mode gambar batas dimatikan.';
                    updateMapTools();
                });

                btnFinishPolygon?.addEventListener('click', function () {
                    if (!selectedLahanId || polygonPoints.length < 3) {
                        if (selectedMapLabel) selectedMapLabel.textContent = 'Minimal 3 titik diperlukan untuk menyelesaikan batas area.';
                        updateMapTools();
                        return;
                    }

                    polygonMode = false;
                    refreshPolygon();
                    if (selectedMapLabel) selectedMapLabel.textContent = 'Batas area siap disimpan. Periksa informasi lahan lalu tekan Simpan.';
                    updateMapTools();
                });

                btnUndoPolygonPoint?.addEventListener('click', function () {
                    if (!selectedLahanId || polygonPoints.length === 0) return;
                    polygonPoints.pop();
                    refreshPolygon();
                    if (selectedMapLabel) selectedMapLabel.textContent = 'Titik terakhir batas area dibatalkan.';
                });

                btnClearPolygon?.addEventListener('click', function () {
                    if (!selectedLahanId) {
                        if (selectedMapLabel) selectedMapLabel.textContent = 'Pilih lahan terlebih dahulu sebelum mengatur batas area.';
                        updateMapTools();
                        return;
                    }

                    polygonPoints = [];
                    polygonMode = false;
                    clearPolygonLayer();
                    if (polygonInput) polygonInput.value = '';
                    updateMapTools();
                });

                document.getElementById('btnResetForm')?.addEventListener('click', resetForm);
                kecamatanSelect?.addEventListener('change', filterKelurahan);
                luasInput?.addEventListener('input', function() {
                    manualLuasEdited = true;
                });

                btnUseMapArea?.addEventListener('click', function() {
                    if (lastMapAreaHa > 0 && luasInput) {
                        luasInput.value = lastMapAreaHa.toFixed(2);
                        manualLuasEdited = false;
                    }
                });

                document.querySelectorAll('.sourceToggle').forEach(button => {
                    button.addEventListener('click', function() {
                        setSource(this.dataset.source || 'baru');
                    });
                });

                document.querySelectorAll('.btnPilihLahan').forEach(button => {
                    button.addEventListener('click', function() {
                        const id = String(this.dataset.lahanId || '');
                        const row = dataById.get(id);

                        if (!row) {
                            if (selectedMapLabel) selectedMapLabel.textContent = 'Data lahan tidak ditemukan. Muat ulang halaman lalu coba lagi.';
                            return;
                        }

                        fillForm(row, this.dataset.source || 'lama');
                    });
                });

                if (highlightLahanId && dataById.has(String(highlightLahanId))) {
                    const row = dataById.get(String(highlightLahanId));
                    const hasPolygon = Boolean(row.polygon_geojson || row.geojson || row.polygon_area);
                    const source = hasPolygon ? 'lama' : 'baru';
                    fillForm(row, source);
                }

                form?.addEventListener('submit', function(e) {
                    if (!selectedLahanId) {
                        e.preventDefault();
                        if (selectedMapLabel) selectedMapLabel.textContent = 'Pilih lahan terlebih dahulu sebelum menyimpan pemetaan.';
                        updateMapTools();
                        return;
                    }

                    if (!latInput?.value || !lngInput?.value) {
                        e.preventDefault();
                        if (selectedMapLabel) selectedMapLabel.textContent = 'Tentukan titik tengah lahan di peta terlebih dahulu.';
                        updateMapTools();
                        return;
                    }

                    if (!polygonInput?.value || polygonPoints.length < 3) {
                        e.preventDefault();
                        if (selectedMapLabel) selectedMapLabel.textContent = 'Gambar batas area minimal 3 titik sebelum menyimpan.';
                        updateMapTools();
                    }
                });

                filterKelurahan();
                updateMapTools();
            });
        </script>
    @endpush
@endif
