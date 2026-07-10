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
    $petani = $petani ?? [];

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
            ->filter(fn($item) => $punyaSpasialLengkap($item) && !str_starts_with($item['id'] ?? '', 'H-'))
            ->values();
    $lahanTermonitor = isset($lahanTermonitor) 
        ? collect($lahanTermonitor)->values() 
        : collect($spasialRows)->filter(fn($item) => str_starts_with($item['id'] ?? '', 'H-'))->values();
    $totalSpasial = data_get($spasialSummary, 'total', is_countable($spasialRows) ? count($spasialRows) : 0);
    $sudahDipetakan = data_get($spasialSummary, 'sudah_dipetakan', $lahanLamaSpasial->count());
    $belumDipetakan = data_get($spasialSummary, 'belum_dipetakan', $lahanBaruSpasial->count());
    $termonitorCount = data_get($spasialSummary, 'termonitor', $lahanTermonitor->count());
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
            .spatial-map-shell .sigpala-kecamatan-label { background: rgba(255,255,255,.88); border: 1px solid rgba(32,60,16,.12); border-radius: 999px; box-shadow: 0 8px 20px rgba(15,23,42,.14); color: #203c10; font-size: 10px; font-weight: 800; letter-spacing: .03em; padding: 4px 8px; text-transform: uppercase; }
            .spatial-map-shell .sigpala-kecamatan-label::before { display: none; }
            .spatial-map-shell .sigpala-wilayah-label { background: rgba(32,60,16,.92); border: 0; border-radius: 999px; box-shadow: 0 10px 24px rgba(15,23,42,.18); color: #fff; font-size: 11px; font-weight: 800; letter-spacing: .04em; padding: 5px 10px; text-transform: uppercase; }
            .spatial-map-shell .sigpala-wilayah-label::before { display: none; }
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
        @if($page === 'dashboard')
            <div class="glass-card rounded-2xl p-5 md:p-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                    <div>
                        <p class="text-primary-700 text-xs font-bold uppercase tracking-[0.22em] mb-2">SiTani BATOLA</p>
                        <h1 class="text-2xl md:text-3xl font-extrabold text-primary-900">Dashboard Petugas</h1>
                        <p class="text-sm text-slate-500 mt-2 max-w-3xl">
                            Verifikasi pengajuan petani, kelola data spasial lahan, dan input parameter lingkungan lapangan.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ url('/dashboard-petugas') }}" class="px-4 py-2 rounded-xl text-sm font-bold transition {{ $isActive('dashboard') }}">Dashboard</a>
                        <a href="{{ url('/manajemen-data-spasial') }}" class="px-4 py-2 rounded-xl text-sm font-bold transition {{ $isActive('manajemen-data-spasial') }}">Data Spasial Lahan</a>
                        <a href="{{ url('/lahan-termonitor') }}" class="px-4 py-2 rounded-xl text-sm font-bold transition {{ $isActive('lahan-termonitor') }}">Lahan Termonitor</a>
                        <a href="{{ url('/verifikasi-data-petani') }}" class="px-4 py-2 rounded-xl text-sm font-bold transition {{ $isActive('verifikasi-data-petani') }}">Verifikasi</a>
                        <a href="{{ url('/manajemen-komunitas') }}" class="px-4 py-2 rounded-xl text-sm font-bold transition {{ $isActive('manajemen-komunitas') }}">Komunitas</a></div>
                </div>
            </div>
        @endif

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
                    <a href="{{ url('/lahan-termonitor') }}" class="rounded-2xl border border-primary-100 bg-white p-5 hover:bg-primary-50 transition">
                        <p class="font-bold text-primary-900">Lahan Termonitor (IoT)</p>
                        <p class="text-sm text-slate-500 mt-1">Sinkronisasi data lahan dan sensor dari Huma.</p>
                    </a>
                </div>
            </div>
        @endif

        @if($page === 'manajemen-komunitas')
            <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-primary-900">Manajemen Komunitas</h1>
                    <p class="text-sm text-slate-500 mt-1">Kelola data Kelompok Tani dan Brigade Pangan.</p>
                </div>
                <button onclick="bukaModalTambahKomunitas()" class="flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-primary-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Tambah Data
                </button>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-6 py-4 font-bold">Nama Entitas</th>
                                <th class="px-6 py-4 font-bold">Jenis / NIK</th>
                                <th class="px-6 py-4 font-bold">Ketua / Penanggung Jawab</th>
                                <th class="px-6 py-4 font-bold">Kontak & Alamat</th>
                                <th class="px-6 py-4 font-bold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($komunitas as $item)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-primary-900">{{ $item['nama_komunitas'] ?? '-' }}</p>
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold {{ $item['status_keanggotaan'] === 'AKTIF' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                            {{ $item['status_keanggotaan'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-primary-700 uppercase">{{ str_replace('_', ' ', $item['jenis_komunitas']) }}</p>
                                        <p class="text-xs text-slate-500 mt-1">NIK: {{ $item['nik'] ?? '-' }}</p>
                                    </td>
                                    <td class="px-6 py-4">{{ $item['nama'] }}</td>
                                    <td class="px-6 py-4">
                                        <p>{{ $item['nomor_hp'] ?? '-' }}</p>
                                        <p class="text-xs text-slate-500 truncate max-w-[150px]">{{ $item['alamat'] ?? '-' }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button onclick="editKomunitas({{ json_encode($item) }})" class="text-blue-600 hover:text-blue-800 text-sm font-bold mr-3">Edit</button>
                                        <button onclick="hapusKomunitas({{ $item['id'] }})" class="text-red-600 hover:text-red-800 text-sm font-bold">Hapus</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="h-12 w-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                            <p>Belum ada data komunitas.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Form Komunitas -->
            <div id="modalKomunitas" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm transition-opacity flex justify-center items-center p-4">
                <div class="bg-white rounded-3xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                    <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-white sticky top-0 z-10">
                        <h3 class="text-lg font-bold text-primary-900" id="modalKomunitasTitle">Tambah Komunitas</h3>
                        <button type="button" onclick="tutupModalKomunitas()" class="text-slate-400 hover:text-slate-600 transition">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="p-6 overflow-y-auto">
                        <form id="formKomunitas" onsubmit="simpanKomunitas(event)" class="space-y-5">
                            <input type="hidden" id="komunitas_id" name="komunitas_id">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block mb-2 text-xs font-bold text-slate-700 uppercase">Jenis Entitas</label>
                                    <select id="jenis_komunitas" name="jenis_komunitas" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none" required>
                                        <option value="kelompok_tani">Kelompok Tani</option>
                                        <option value="brigade_pangan">Brigade Pangan</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block mb-2 text-xs font-bold text-slate-700 uppercase">Nama Entitas (Komunitas)</label>
                                    <input type="text" id="nama_komunitas" name="nama_komunitas" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none" placeholder="Cth: Kelompok Tani Maju Jaya" required>
                                </div>
                                <div>
                                    <label class="block mb-2 text-xs font-bold text-slate-700 uppercase">NIK Ketua / Penanggung Jawab</label>
                                    <input type="text" id="nik" name="nik" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none" placeholder="16 digit NIK" required>
                                </div>
                                <div>
                                    <label class="block mb-2 text-xs font-bold text-slate-700 uppercase">Nama Ketua / Penanggung Jawab</label>
                                    <input type="text" id="nama" name="nama" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none" required>
                                </div>
                                <div>
                                    <label class="block mb-2 text-xs font-bold text-slate-700 uppercase">Nomor HP</label>
                                    <input type="text" id="nomor_hp" name="nomor_hp" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none">
                                </div>
                                <div>
                                    <label class="block mb-2 text-xs font-bold text-slate-700 uppercase">ID Kecamatan</label>
                                    <input type="number" id="wilayah_kecamatan_id" name="wilayah_kecamatan_id" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none" placeholder="ID Kecamatan">
                                </div>
                                <div>
                                    <label class="block mb-2 text-xs font-bold text-slate-700 uppercase">Instansi Asal</label>
                                    <input type="text" id="instansi_asal" name="instansi_asal" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none">
                                </div>
                                <div>
                                    <label class="block mb-2 text-xs font-bold text-slate-700 uppercase">Nama BPP</label>
                                    <input type="text" id="nama_bpp" name="nama_bpp" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block mb-2 text-xs font-bold text-slate-700 uppercase">Alamat Sekretariat</label>
                                <textarea id="alamat" name="alamat" rows="2" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none"></textarea>
                            </div>

                            <div>
                                <label class="block mb-2 text-xs font-bold text-slate-700 uppercase">IDs Kelurahan (JSON format)</label>
                                <input type="text" id="wilayah_kelurahan_ids" name="wilayah_kelurahan_ids" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none" placeholder='Contoh: [1,2,3]'>
                            </div>
                            
                            <div id="divStatus" class="hidden">
                                <label class="block mb-2 text-xs font-bold text-slate-700 uppercase">Status Keanggotaan</label>
                                <select id="status_keanggotaan" name="status_keanggotaan" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 outline-none">
                                    <option value="AKTIF">Aktif</option>
                                    <option value="TIDAK_AKTIF">Tidak Aktif</option>
                                </select>
                            </div>
                            
                            <div class="flex justify-end gap-3 pt-5 border-t border-slate-100">
                                <button type="button" onclick="tutupModalKomunitas()" class="px-5 py-2.5 rounded-xl font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">Batal</button>
                                <button type="submit" id="btnSubmitKomunitas" class="px-5 py-2.5 rounded-xl font-bold text-white bg-primary-600 hover:bg-primary-700 transition">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @push('scripts')
            <script>
                function bukaModalTambahKomunitas() {
                    document.getElementById('formKomunitas').reset();
                    document.getElementById('komunitas_id').value = '';
                    document.getElementById('modalKomunitasTitle').textContent = 'Tambah Komunitas';
                    document.getElementById('divStatus').classList.add('hidden');
                    document.getElementById('modalKomunitas').classList.remove('hidden');
                }

                function tutupModalKomunitas() {
                    document.getElementById('modalKomunitas').classList.add('hidden');
                }

                function editKomunitas(data) {
                    document.getElementById('komunitas_id').value = data.id;
                    document.getElementById('jenis_komunitas').value = data.jenis_komunitas;
                    document.getElementById('nama_komunitas').value = data.nama_komunitas || '';
                    document.getElementById('nik').value = data.nik || '';
                    document.getElementById('nama').value = data.nama || '';
                    document.getElementById('nomor_hp').value = data.nomor_hp || '';
                    document.getElementById('alamat').value = data.alamat || '';
                    
                    document.getElementById('wilayah_kecamatan_id').value = data.wilayah_kecamatan_id || '';
                    document.getElementById('wilayah_kelurahan_ids').value = data.wilayah_kelurahan_ids ? (typeof data.wilayah_kelurahan_ids === 'object' ? JSON.stringify(data.wilayah_kelurahan_ids) : data.wilayah_kelurahan_ids) : '';
                    document.getElementById('instansi_asal').value = data.instansi_asal || '';
                    document.getElementById('nama_bpp').value = data.nama_bpp || '';
                    
                    document.getElementById('status_keanggotaan').value = data.status_keanggotaan || 'AKTIF';
                    
                    document.getElementById('modalKomunitasTitle').textContent = 'Edit Komunitas';
                    document.getElementById('divStatus').classList.remove('hidden');
                    document.getElementById('modalKomunitas').classList.remove('hidden');
                }

                function simpanKomunitas(e) {
                    e.preventDefault();
                    const id = document.getElementById('komunitas_id').value;
                    const url = id ? `/petugas/komunitas/${id}` : '/petugas/komunitas';
                    const method = id ? 'PUT' : 'POST';
                    const data = {
                        jenis_komunitas: document.getElementById('jenis_komunitas').value,
                        nama_komunitas: document.getElementById('nama_komunitas').value,
                        nik: document.getElementById('nik').value,
                        nama: document.getElementById('nama').value,
                        nomor_hp: document.getElementById('nomor_hp').value,
                        alamat: document.getElementById('alamat').value,
                        wilayah_kecamatan_id: document.getElementById('wilayah_kecamatan_id').value || null,
                        instansi_asal: document.getElementById('instansi_asal').value,
                        nama_bpp: document.getElementById('nama_bpp').value,
                    };
                    
                    const kelurahanInput = document.getElementById('wilayah_kelurahan_ids').value;
                    if (kelurahanInput) {
                        try {
                            data.wilayah_kelurahan_ids = JSON.parse(kelurahanInput);
                        } catch (e) {
                            data.wilayah_kelurahan_ids = kelurahanInput;
                        }
                    } else {
                        data.wilayah_kelurahan_ids = null;
                    }
                    if (id) {
                        data.status_keanggotaan = document.getElementById('status_keanggotaan').value;
                    }

                    // Add CSRF token for web_app routes
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                    fetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Authorization': 'Bearer {{ session('token') }}'
                        },
                        body: JSON.stringify(data)
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            alert(res.message);
                            location.reload();
                        } else {
                            alert(res.message || 'Gagal menyimpan data.');
                        }
                    })
                    .catch(err => alert('Terjadi kesalahan sistem.'));
                }

                function hapusKomunitas(id) {
                    if (confirm('Yakin ingin menghapus komunitas ini? Data pengguna yang terkait juga mungkin akan terpengaruh.')) {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        
                        fetch(`/petugas/komunitas/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Authorization': 'Bearer {{ session('token') }}'
                            }
                        })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                alert(res.message);
                                location.reload();
                            } else {
                                alert(res.message || 'Gagal menghapus data.');
                            }
                        })
                        .catch(err => alert('Terjadi kesalahan sistem.'));
                    }
                }
            </script>
            @endpush
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
                                            <p class="text-[10px] font-bold text-primary-700 uppercase mt-0.5">{{ $ambil($item, ['petani.role_id', 'user.role_id']) == 5 ? 'Brigade Pangan' : 'Kelompok Tani' }}</p>
                                            <p class="text-xs text-slate-500 mt-1">{{ $ambil($item, ['email_petani','petani.email','user.email']) }}</p>
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
                                    <th class="px-5 py-4">Estimasi Panen</th>
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
                                            <p class="font-bold text-primary-900">{{ $ambil($panen, ['nama_pemilik', 'pemilik.nama_lengkap', 'user.nama_lengkap']) }}</p>
                                            <p class="text-[10px] font-bold text-primary-700 uppercase mt-0.5">{{ $ambil($panen, ['pemilik.role_id', 'user.role_id']) == 5 ? 'Brigade Pangan' : 'Kelompok Tani' }}</p>
                                            <p class="text-xs text-slate-500 mt-1">Email: {{ $ambil($panen, ['email_pemilik', 'pemilik.email', 'user.email']) }}</p>
                                            <p class="text-xs text-slate-500">No HP: {{ $ambil($panen, ['no_hp_pemilik', 'pemilik.no_hp', 'user.no_hp']) }}</p>
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
                                        <td class="px-5 py-4 text-slate-700">{{ \Carbon\Carbon::parse($ambil($panen, ['tanggal_tanam']))->format('d M Y') }}</td>
                                        <td class="px-5 py-4 text-slate-700 whitespace-nowrap">
                                            {{ $ambil($panen, ['estimasi_tanggal_panen']) ? \Carbon\Carbon::parse($ambil($panen, ['estimasi_tanggal_panen']))->format('d M Y') : '-' }}
                                            @if($ambil($panen, ['estimasi_tanggal_panen_akhir']))
                                                <br>s/d<br>{{ \Carbon\Carbon::parse($ambil($panen, ['estimasi_tanggal_panen_akhir']))->format('d M Y') }}
                                            @endif
                                        </td>
                                        <td class="px-5 py-4 text-slate-700">{{ \Carbon\Carbon::parse($ambil($panen, ['tanggal_panen']))->format('d M Y') }}</td>
                                        <td class="px-5 py-4"><p class="font-extrabold text-primary-700">{{ $angka($ambil($panen, ['hasil_panen'], 0)) }} Ton</p></td>
                                        <td class="px-5 py-4">
                                            <div class="flex flex-col gap-1">
                                                <span class="w-fit px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200">
                                                    {{ $ambil($panen, ['status_verifikasi'], 'PENDING') }}
                                                </span>
                                                @if(!empty($ambil($panen, ['catatan_verifikasi'])))
                                                    <span class="text-xs text-slate-500 max-w-[150px] break-words">
                                                        Catatan: {{ $ambil($panen, ['catatan_verifikasi']) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <div class="flex justify-end gap-2">
                                                <form method="POST" action="{{ url('/petugas/verifikasi-panen/' . $ambil($panen, ['id']) . '/diterima') }}" onsubmit="return confirm('Setujui laporan panen ini?');">
                                                    @csrf
                                                    <button class="px-4 py-2 rounded-xl bg-green-50 text-green-700 border border-green-200 font-bold hover:bg-green-600 hover:text-white transition">Setujui</button>
                                                </form>
                                                <button type="button"
                                                        class="btnTolakPanen px-4 py-2 rounded-xl bg-red-50 text-red-600 border border-red-200 font-bold hover:bg-red-600 hover:text-white transition"
                                                        data-reject-url="{{ url('/petugas/verifikasi-panen/' . $ambil($panen, ['id']) . '/ditolak') }}"
                                                        data-nama="Laporan Panen {{ $ambil($panen, ['nama_lahan','lahan.nama_lahan']) }} - {{ $ambil($panen, ['nama_petani','petani.nama_lengkap']) }}">
                                                    Tolak
                                                </button>
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
                                    <p id="detailNoHp" class="text-sm text-slate-500 mt-1">-</p>
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
                                <form method="POST" action="#" id="detailApproveForm" class="flex-1 sm:flex-none flex flex-col sm:flex-row gap-3 items-center" onsubmit="return confirm('Setujui pengajuan lahan ini? Pastikan seluruh detail pengajuan sudah sesuai.');">
                                    @csrf
                                    <button class="w-full sm:w-auto px-5 py-3 rounded-2xl bg-green-50 text-green-700 border border-green-200 font-bold hover:bg-green-600 hover:text-white transition whitespace-nowrap">Setujui Pengajuan</button>
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
                            <p class="text-[10px] text-slate-400 font-bold uppercase">Total (SiTani)</p>
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
                            <div class="grid md:grid-cols-3 gap-3">
                                <button type="button" class="spatial-choice sourceToggle is-active rounded-2xl px-4 py-3 text-left transition" data-source="baru">
                                    <span class="block text-sm font-extrabold">Lahan Belum Dipetakan</span>
                                    <span class="block text-xs mt-1">{{ $lahanBaruSpasial->count() }} disetujui, belum memiliki polygon</span>
                                </button>
                                <button type="button" class="spatial-choice sourceToggle rounded-2xl px-4 py-3 text-left transition" data-source="lama">
                                    <span class="block text-sm font-extrabold">Lahan Sudah Dipetakan</span>
                                    <span class="block text-xs mt-1">{{ $lahanLamaSpasial->count() }} data memiliki polygon</span>
                                </button>
                                <button type="button" class="spatial-choice sourceToggle rounded-2xl px-4 py-3 text-left transition" data-source="termonitor">
                                    <span class="block text-sm font-extrabold text-slate-800">Lahan Termonitor (Huma)</span>
                                    <span class="block text-xs mt-1">{{ $termonitorCount }} lahan terhubung sensor IoT Huma</span>
                                </button>
                            </div>

                            <div class="mb-4">
                                <input type="text" id="spatialSearch" placeholder="Cari nama lahan, wilayah, atau pemilik..." class="w-full rounded-xl border border-primary-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 bg-white">
                            </div>

                            <div>
                                <div id="sourceListBaru" class="spatial-list space-y-2">
                                    @forelse($lahanBaruSpasial as $item)
                                        <button type="button" class="spatial-row btnPilihLahan w-full rounded-2xl p-4 text-left transition hover:bg-primary-50" data-source="baru" data-lahan-id="{{ $ambil($item, ['id']) }}" data-search="{{ strtolower($ambil($item, ['nama_lahan']) . ' ' . $ambil($item, ['nama_kecamatan']) . ' ' . $ambil($item, ['nama_kelurahan']) . ' ' . $ambil($item, ['pemilik_lahan','nama_petani'])) }}">
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
                                        <button type="button" class="spatial-row btnPilihLahan w-full rounded-2xl p-4 text-left transition hover:bg-primary-50" data-source="lama" data-lahan-id="{{ $ambil($item, ['id']) }}" data-search="{{ strtolower($ambil($item, ['nama_lahan']) . ' ' . $ambil($item, ['nama_kecamatan']) . ' ' . $ambil($item, ['nama_kelurahan']) . ' ' . $ambil($item, ['pemilik_lahan','nama_petani'])) }}">
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

                                <div id="sourceListTermonitor" class="spatial-list space-y-2 hidden">
                                    @forelse($lahanTermonitor as $item)
                                        <button type="button" class="spatial-row btnPilihLahan w-full rounded-2xl p-4 text-left transition hover:bg-primary-50" data-source="termonitor" data-lahan-id="{{ $ambil($item, ['id']) }}" data-search="{{ strtolower($ambil($item, ['nama_lahan']) . ' ' . $ambil($item, ['nama_kecamatan']) . ' ' . $ambil($item, ['nama_kelurahan']) . ' ' . $ambil($item, ['pemilik_lahan','nama_petani'])) }}">
                                            <span class="flex items-start justify-between gap-3">
                                                <span>
                                                    <span class="block font-extrabold text-primary-900">{{ $ambil($item, ['nama_lahan']) }}</span>
                                                    <span class="block text-xs text-slate-500 mt-1">{{ $ambil($item, ['nama_kecamatan']) }} / {{ $ambil($item, ['nama_kelurahan']) }}</span>
                                                </span>
                                                <span class="px-2 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold">TERMONITOR</span>
                                            </span>
                                            <span class="block text-xs text-slate-500 mt-3">{{ $angka($ambil($item, ['luas_lahan_hektar'], 0)) }} Ha - Huma IoT ({{ $ambil($item, ['pemilik_lahan','nama_petani']) }})</span>
                                        </button>
                                    @empty
                                        <div class="rounded-2xl border border-primary-100 bg-white px-4 py-8 text-center text-sm text-slate-500">Belum ada lahan termonitor IoT Huma.</div>
                                    @endforelse
                                </div>

                                <!-- Pagination Controls -->
                                <div id="spatialPagination" class="mt-4 flex flex-wrap justify-center gap-1"></div>
                            </div>
                        </div>
                    </section>

                    <section class="spatial-panel rounded-2xl p-4">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                            <div>
                                <h2 class="text-lg font-extrabold text-primary-900">Peta Kerja Petugas</h2>
                                <p id="selectedMapLabel" class="text-sm text-slate-500 mt-1">Batas Kabupaten Barito Kuala dan layer batas kecamatan tersedia di kontrol peta. Pilih lahan untuk mulai mengatur titik dan batas area.</p>
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

                <div id="spatialWorkspace" class="spatial-workspace is-locked bg-white rounded-2xl shadow-sm border border-primary-100 overflow-hidden">
                    <div class="px-6 py-5 bg-gradient-to-r from-primary-50 to-white border-b border-primary-100 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div>
                            <p id="selectedSourceLabel" class="text-xs text-primary-600 font-bold uppercase tracking-wider mb-1">Belum memilih lahan</p>
                            <h2 id="selectedLahanTitle" class="text-2xl font-extrabold text-primary-900">Informasi Lahan Sawah</h2>
                            <p id="selectedLahanMeta" class="text-sm text-slate-500 mt-1">Pilih lahan dari panel sebelah kiri untuk mulai mengatur titik dan batas area.</p>
                        </div>
                        <span id="formModeBadge" class="w-fit px-4 py-1.5 rounded-full bg-slate-100 text-slate-500 text-xs font-bold border border-slate-200 shadow-sm">Terkunci</span>
                    </div>

                    <div class="spatial-empty-state py-16 px-6 text-center">
                        <div class="w-16 h-16 rounded-full bg-primary-50 flex items-center justify-center mx-auto mb-4 border border-primary-100">
                            <svg class="w-8 h-8 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                        </div>
                        <p class="text-lg text-primary-900 font-extrabold">Pilih Lahan Sawah untuk Memulai</p>
                        <p class="text-sm text-slate-500 mt-2 max-w-md mx-auto">Anda dapat memeriksa informasi lahan, menentukan titik pusat di peta, serta menggambar batas polygon area dengan akurat.</p>
                    </div>

                    <div class="spatial-form-body p-6">
                        <form method="POST" action="{{ url('/petugas/spasial/simpan') }}" id="spasialForm" class="space-y-8">
                            @csrf
                            <input type="hidden" name="_method" id="form_method" value="">
                            <input type="hidden" name="lahan_id" id="lahan_id">
                            <input type="hidden" name="user_id" id="user_id">

                            <!-- Section: Identitas Lahan -->
                            <div class="spatial-form-section bg-slate-50 rounded-2xl p-5 border border-slate-100">
                                <div class="flex items-center gap-2 mb-4 border-b border-slate-200 pb-3">
                                    <div class="w-6 h-6 rounded-lg bg-primary-100 flex items-center justify-center text-primary-700 font-bold text-xs">1</div>
                                    <p class="text-sm font-extrabold text-primary-900 uppercase tracking-wide">Identitas Lahan</p>
                                </div>
                                <div class="grid lg:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Nama Lahan</label>
                                        <input name="nama_lahan" id="nama_lahan" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-all bg-white" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Pemilik Lahan</label>
                                        <input id="pemilik_lahan" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm bg-slate-100 text-slate-600 cursor-not-allowed outline-none" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Wilayah & Klasifikasi -->
                            <div class="spatial-form-section bg-slate-50 rounded-2xl p-5 border border-slate-100">
                                <div class="flex items-center gap-2 mb-4 border-b border-slate-200 pb-3">
                                    <div class="w-6 h-6 rounded-lg bg-primary-100 flex items-center justify-center text-primary-700 font-bold text-xs">2</div>
                                    <p class="text-sm font-extrabold text-primary-900 uppercase tracking-wide">Wilayah & Klasifikasi</p>
                                </div>
                                <div class="grid lg:grid-cols-2 gap-5 mb-5">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Kecamatan</label>
                                        <select name="kecamatan_id" id="kecamatan_id" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-all bg-white" required>
                                            <option value="">Pilih kecamatan</option>
                                            @foreach(data_get($referensi, 'kecamatan', []) as $item)
                                                <option value="{{ $ambil($item, ['id']) }}">{{ $ambil($item, ['nama_kecamatan','nama']) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Kelurahan</label>
                                        <select name="kelurahan_id" id="kelurahan_id" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-all bg-white">
                                            <option value="">Pilih kelurahan</option>
                                            @foreach(data_get($referensi, 'kelurahan', []) as $item)
                                                <option value="{{ $ambil($item, ['id']) }}" data-kecamatan="{{ $ambil($item, ['kecamatan_id'], '') }}">{{ $ambil($item, ['nama_kelurahan','nama']) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="grid lg:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Tipe Lahan</label>
                                        <select name="tipe_lahan_id" id="tipe_lahan_id" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-all bg-white">
                                            <option value="">Pilih tipe lahan</option>
                                            @foreach(data_get($referensi, 'tipe_lahan', []) as $item)
                                                <option value="{{ $ambil($item, ['id']) }}">{{ $ambil($item, ['nama_tipe','nama']) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Tahun Basis</label>
                                        <select name="tahun_lbs" id="tahun_lbs" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-all bg-white">
                                            <option value="2024">2024</option>
                                            <option value="2017">2017</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Data Spasial -->
                            <div class="spatial-form-section bg-slate-50 rounded-2xl p-5 border border-slate-100">
                                <div class="flex items-center gap-2 mb-4 border-b border-slate-200 pb-3">
                                    <div class="w-6 h-6 rounded-lg bg-primary-100 flex items-center justify-center text-primary-700 font-bold text-xs">3</div>
                                    <p class="text-sm font-extrabold text-primary-900 uppercase tracking-wide">Pemetaan & Spasial</p>
                                </div>
                                
                                <div class="grid lg:grid-cols-3 gap-5 mb-5">
                                    <div class="col-span-1 lg:col-span-3">
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Alamat Detail</label>
                                        <textarea name="alamat_detail" id="alamat_detail" rows="2" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-all bg-white"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Luas Lahan (Ha)</label>
                                        <input name="luas_lahan_hektar" id="luas_lahan_hektar" type="number" step="0.01" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-all bg-white" required>
                                        <div class="mt-2 flex items-center justify-between gap-2">
                                            <p id="areaEstimateText" class="text-xs text-slate-500 font-medium">Luas peta belum tersedia.</p>
                                            <button type="button" id="btnUseMapArea" class="hidden shrink-0 px-3 py-1 rounded-lg bg-primary-100 text-primary-700 hover:bg-primary-200 text-[10px] font-bold uppercase tracking-wider transition-colors">Gunakan</button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Latitude (Y)</label>
                                        <input name="latitude" id="latitude" type="number" step="any" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-all bg-white" required>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Longitude (X)</label>
                                        <input name="longitude" id="longitude" type="number" step="any" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 outline-none transition-all bg-white" required>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1.5 flex justify-between items-center">
                                        <span>Data Polygon Geometri (GeoJSON)</span>
                                        <span id="polygonStatusText" class="text-[10px] font-medium text-amber-600 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">Menunggu Input</span>
                                    </label>
                                    <textarea name="polygon_geojson" id="polygon_geojson" rows="3" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-xs font-mono bg-slate-100 text-slate-600 cursor-not-allowed outline-none" required readonly placeholder="Klik tombol Gambar Batas pada peta, lalu klik minimal 3 titik batas lahan."></textarea>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                                <button class="flex-1 px-6 py-3.5 rounded-xl bg-primary-600 text-white font-extrabold hover:bg-primary-700 shadow-sm transition-all focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    Simpan Data Spasial
                                </button>
                                <button type="button" id="btnResetForm" class="px-6 py-3.5 rounded-xl border border-slate-300 text-slate-700 font-bold hover:bg-slate-50 transition-all">
                                    Batal & Reset
                                </button>
                            </div>
                        </form>
                        
                        <form method="POST" action="#" id="deleteSpasialForm" class="mt-4 hidden" onsubmit="return confirm('Apakah Anda yakin ingin mengosongkan batas area lahan ini? Data administrasi lahan tetap akan tersimpan.');">
                            @csrf
                            @method('DELETE')
                            <button class="w-full px-6 py-3.5 rounded-xl bg-red-50 text-red-600 border border-red-200 font-bold hover:bg-red-600 hover:text-white transition-all">
                                Kosongkan Batas Area Lahan Ini
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if($page === 'lahan-termonitor')
            <div class="space-y-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-extrabold text-primary-900">Lahan Termonitor (Integrasi Huma)</h2>
                        <p class="text-sm text-slate-500 mt-1">Tarik data lahan dan log sensor terbaru dari perangkat Huma.</p>
                    </div>
                    <button id="btnSyncHuma" class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl shadow-lg shadow-primary-200 transition flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Sinkronkan Data Huma
                    </button>
                </div>

                <div class="space-y-6">
                    <!-- Tabel Preview Data (Dari API Huma) -->
                    <div class="soft-card bg-white rounded-2xl border border-primary-100 p-5">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-extrabold text-primary-900">Preview Data (Dari Huma)</h3>
                            <span class="px-3 py-1 bg-amber-50 text-amber-700 border border-amber-200 text-xs font-bold rounded-full">Belum Tersimpan</span>
                        </div>
                        <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
                            <table class="w-full text-left text-sm whitespace-nowrap">
                                <thead class="bg-primary-50 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-4 py-3 font-bold text-primary-900 rounded-l-xl">Device ID</th>
                                        <th class="px-4 py-3 font-bold text-primary-900">Nama Lahan</th>
                                        <th class="px-4 py-3 font-bold text-primary-900">Alamat</th>
                                        <th class="px-4 py-3 font-bold text-primary-900">Koordinat</th>
                                        <th class="px-4 py-3 font-bold text-primary-900">pH</th>
                                        <th class="px-4 py-3 font-bold text-primary-900">N-P-K</th>
                                        <th class="px-4 py-3 font-bold text-primary-900">Water Lvl</th>
                                        <th class="px-4 py-3 font-bold text-primary-900">Rekomendasi Pupuk</th>
                                        <th class="px-4 py-3 font-bold text-primary-900 rounded-r-xl">Waktu Rekam</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-primary-100">
                                    @forelse($previewData['lands'] ?? [] as $land)
                                        @php
                                            $sensor = collect($previewData['sensors'] ?? [])->where('device_id', $land['device_id'])->first();
                                        @endphp
                                        <tr class="hover:bg-slate-50 transition">
                                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $land['device_id'] ?? '-' }}</td>
                                            <td class="px-4 py-3 font-bold text-primary-800">{{ $land['nama_lahan'] ?? ($land['name'] ?? '-') }}</td>
                                            <td class="px-4 py-3 text-xs text-slate-600">{{ $land['alamat'] ?? ($land['address'] ?? '-') }}</td>
                                            <td class="px-4 py-3 text-xs text-slate-600">{{ $land['latitude'] ?? '-' }}, {{ $land['longitude'] ?? '-' }}</td>
                                            <td class="px-4 py-3 text-slate-700 font-bold">{{ $sensor['ph_tanah'] ?? '-' }}</td>
                                            <td class="px-4 py-3 text-slate-600 text-xs">
                                                N: {{ $sensor['n'] ?? '-' }}, P: {{ $sensor['p'] ?? '-' }}, K: {{ $sensor['k'] ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-slate-600 text-xs">{{ $sensor['water_level'] ?? '-' }}</td>
                                            <td class="px-4 py-3 text-slate-600 text-xs">-</td>
                                            <td class="px-4 py-3 text-slate-500 text-[10px]">{{ $sensor['waktu_rekam'] ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="9" class="px-4 py-8 text-center text-slate-500">Tidak ada data preview dari Huma.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tabel Data Tersimpan (SiTani) -->
                    <div class="soft-card bg-white rounded-2xl border border-primary-100 p-5">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-extrabold text-primary-900">Data Termonitor (SiTani)</h3>
                            <span class="px-3 py-1 bg-green-50 text-green-700 border border-green-200 text-xs font-bold rounded-full">Tersimpan</span>
                        </div>
                        <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
                            <table class="w-full text-left text-sm whitespace-nowrap">
                                <thead class="bg-primary-50 sticky top-0 z-10">
                                    <tr>
                                        <th class="px-4 py-3 font-bold text-primary-900 rounded-l-xl">Device ID</th>
                                        <th class="px-4 py-3 font-bold text-primary-900">Nama Lahan</th>
                                        <th class="px-4 py-3 font-bold text-primary-900">Alamat</th>
                                        <th class="px-4 py-3 font-bold text-primary-900">Koordinat</th>
                                        <th class="px-4 py-3 font-bold text-primary-900">pH</th>
                                        <th class="px-4 py-3 font-bold text-primary-900">N-P-K</th>
                                        <th class="px-4 py-3 font-bold text-primary-900">Water Lvl</th>
                                        <th class="px-4 py-3 font-bold text-primary-900">Rekomendasi Pupuk</th>
                                        <th class="px-4 py-3 font-bold text-primary-900 rounded-r-xl">Waktu Rekam</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-primary-100">
                                    @forelse($lahanHuma as $lahan)
                                        @php
                                            $catatanVerifikasi = json_decode($lahan['catatan_verifikasi'] ?? '{}', true);
                                            $deviceId = $catatanVerifikasi['huma_device_id'] ?? '-';
                                            
                                            // Cari log terbaru untuk lahan ini
                                            $latestLog = collect($monitoringHuma)->where('lahan_id', $lahan['id'])->first();
                                            $catatanPetugas = $latestLog ? json_decode($latestLog['catatan_petugas'] ?? '{}', true) : null;
                                        @endphp
                                        <tr class="hover:bg-slate-50 transition">
                                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $deviceId }}</td>
                                            <td class="px-4 py-3 font-bold text-primary-800">{{ $lahan['nama_lahan'] ?? '-' }}</td>
                                            <td class="px-4 py-3 text-xs text-slate-600">{{ $lahan['alamat_detail'] ?? '-' }}</td>
                                            <td class="px-4 py-3 text-xs text-slate-600">{{ $lahan['latitude'] ?? '-' }}, {{ $lahan['longitude'] ?? '-' }}</td>
                                            <td class="px-4 py-3 text-slate-700 font-bold">{{ $catatanPetugas['ph_tanah'] ?? '-' }}</td>
                                            <td class="px-4 py-3 text-slate-600 text-xs">
                                                N: {{ $catatanPetugas['n_level'] ?? '-' }}, P: {{ $catatanPetugas['p_level'] ?? '-' }}, K: {{ $catatanPetugas['k_level'] ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-slate-600 text-xs">{{ $catatanPetugas['water_level'] ?? '-' }}</td>
                                            <td class="px-4 py-3 text-slate-600 text-xs">
                                                @if(isset($catatanPetugas['rekomendasi_pupuk']) && is_array($catatanPetugas['rekomendasi_pupuk']))
                                                    {{ implode(', ', $catatanPetugas['rekomendasi_pupuk']) }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-slate-500 text-[10px]">{{ $latestLog['tanggal_cek'] ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="9" class="px-4 py-8 text-center text-slate-500">Belum ada lahan Huma yang tersimpan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            @push('scripts')
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const btnSync = document.getElementById('btnSyncHuma');
                        if (btnSync) {
                            btnSync.addEventListener('click', async function() {
                                const originalText = this.innerHTML;
                                this.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Menyinkronkan...';
                                this.disabled = true;
                                
                                try {
                                    const response = await fetch('/petugas/lahan-termonitor/sync', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Accept': 'application/json',
                                            'Authorization': 'Bearer ' + '{{ session("token") }}',
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        }
                                    });
                                    
                                    const result = await response.json();
                                    
                                    if (result.success) {
                                        alert('Sinkronisasi berhasil: ' + (result.message || ''));
                                        window.location.reload();
                                    } else {
                                        alert('Gagal: ' + (result.message || 'Unknown error'));
                                        this.innerHTML = originalText;
                                        this.disabled = false;
                                    }
                                } catch (error) {
                                    console.error(error);
                                    alert('Terjadi kesalahan jaringan.');
                                    this.innerHTML = originalText;
                                    this.disabled = false;
                                }
                            });
                        }
                    });
                </script>
            @endpush
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
                        setText('detailPengaju', data.nama_pemilik || data.pemilik_lahan);
                        setText('detailEmail', data.email_pemilik);
                        setText('detailNoHp', data.no_hp_pemilik);
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

                document.querySelectorAll('.btnTolakPanen').forEach(button => {
                    button.addEventListener('click', function () {
                        openReject(this.dataset.rejectUrl || '', this.dataset.nama || 'Laporan panen');
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

                const cartoLight = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                    maxZoom: 20,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
                }).addTo(map);

                const cartoDark = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                    maxZoom: 20,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
                });

                const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    maxZoom: 19,
                    attribution: 'Tiles &copy; Esri'
                });

                const batasKabupatenGroup = L.layerGroup().addTo(map);
                const batasKecamatanGroup = L.layerGroup().addTo(map);
                const semuaLahanGroup = L.layerGroup().addTo(map);

                L.control.layers({
                    'Carto Light (BI)': cartoLight,
                    'Carto Dark (BI)': cartoDark,
                    'Satelit (Esri)': satelliteLayer
                }, {
                    'Batas Kabupaten': batasKabupatenGroup,
                    'Batas Kecamatan': batasKecamatanGroup,
                    'Area Lahan': semuaLahanGroup
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

                const kecamatanPalette = [
                    '#15803d', '#0f766e', '#0369a1', '#7c3aed', '#c2410c',
                    '#be123c', '#047857', '#b45309', '#4338ca', '#0e7490',
                    '#65a30d', '#a21caf', '#1d4ed8', '#ca8a04', '#dc2626',
                    '#0891b2', '#4d7c0f'
                ];

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
                            modeBadge.textContent = source === 'baru' ? 'Belum Dipetakan' : (source === 'termonitor' ? 'Termonitor' : 'Sudah Dipetakan');
                            modeBadge.className = source === 'baru'
                                ? 'px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-bold border border-amber-200'
                                : (source === 'termonitor' 
                                    ? 'px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold border border-blue-200'
                                    : 'px-3 py-1 rounded-full bg-primary-50 text-primary-700 text-xs font-bold border border-primary-100');
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

                let currentSpatialSource = 'baru';
                let spatialSearchQuery = '';
                let currentSpatialPage = 1;
                const itemsPerPage = 5;

                function renderSpatialList() {
                    const activeListId = currentSpatialSource === 'baru' ? 'sourceListBaru' : (currentSpatialSource === 'termonitor' ? 'sourceListTermonitor' : 'sourceListLama');
                    const activeList = document.getElementById(activeListId);
                    if (!activeList) return;

                    const allItems = Array.from(activeList.querySelectorAll('.btnPilihLahan'));
                    const filteredItems = allItems.filter(item => {
                        const searchData = item.dataset.search || '';
                        return searchData.includes(spatialSearchQuery);
                    });

                    const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
                    if (currentSpatialPage > totalPages && totalPages > 0) currentSpatialPage = totalPages;
                    if (currentSpatialPage < 1) currentSpatialPage = 1;

                    const startIndex = (currentSpatialPage - 1) * itemsPerPage;
                    const endIndex = startIndex + itemsPerPage;

                    allItems.forEach(item => item.classList.add('hidden'));

                    filteredItems.slice(startIndex, endIndex).forEach(item => {
                        item.classList.remove('hidden');
                    });

                    renderSpatialPagination(totalPages);
                }

                function renderSpatialPagination(totalPages) {
                    const container = document.getElementById('spatialPagination');
                    if (!container) return;
                    container.innerHTML = '';

                    if (totalPages <= 1) return;

                    const createBtn = (text, page, isActive = false) => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.textContent = text;
                        btn.className = `w-8 h-8 flex items-center justify-center rounded-xl text-xs font-bold transition-colors ${isActive ? 'bg-primary-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}`;
                        btn.addEventListener('click', () => {
                            currentSpatialPage = page;
                            renderSpatialList();
                        });
                        return btn;
                    };

                    for (let i = 1; i <= totalPages; i++) {
                        container.appendChild(createBtn(i, i, i === currentSpatialPage));
                    }
                }

                document.getElementById('spatialSearch')?.addEventListener('input', function(e) {
                    spatialSearchQuery = e.target.value.toLowerCase();
                    currentSpatialPage = 1;
                    renderSpatialList();
                });

                function setSource(source) {
                    currentSpatialSource = source;
                    currentSpatialPage = 1;

                    document.querySelectorAll('.sourceToggle').forEach(button => {
                        button.classList.toggle('is-active', button.dataset.source === source);
                    });

                    document.getElementById('sourceListBaru')?.classList.toggle('hidden', source !== 'baru');
                    document.getElementById('sourceListLama')?.classList.toggle('hidden', source !== 'lama');
                    document.getElementById('sourceListTermonitor')?.classList.toggle('hidden', source !== 'termonitor');

                    renderSpatialList();
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

                function styleKabupaten(feature) {
                    const props = feature?.properties || {};

                    return {
                        color: props.warna_peta || '#203c10',
                        weight: 2.4,
                        opacity: 0.9,
                        fillOpacity: Number(props.fill_opacity ?? 0),
                        fillColor: props.fill_color || 'transparent',
                        dashArray: '6 6'
                    };
                }

                function labelWilayah(feature, layer) {
                    const props = feature?.properties || {};
                    const label = props.nama_kabupaten || props.nama || props.label;
                    if (!label) return;

                    layer.bindTooltip(label, {
                        permanent: false,
                        direction: 'center',
                        className: 'sigpala-wilayah-label'
                    });
                }

                function drawBatasWilayah() {
                    if (!batasWilayah || !batasWilayah.type) return;

                    batasLayer = L.geoJSON(batasWilayah, {
                        interactive: false,
                        style: styleKabupaten,
                        onEachFeature: labelWilayah
                    }).addTo(batasKabupatenGroup);

                    try {
                        map.fitBounds(batasLayer.getBounds(), { padding: [18, 18] });
                    } catch (e) {
                        map.fitBounds(batolaBounds);
                    }
                }

                function warnaKecamatan(feature) {
                    const props = feature?.properties || {};
                    const id = Number(props.kecamatan_id || props.id || 1);

                    return props.warna_peta || props.fill_color || kecamatanPalette[(Math.max(id, 1) - 1) % kecamatanPalette.length];
                }

                function styleKecamatan(feature) {
                    const color = warnaKecamatan(feature);

                    return {
                        color,
                        weight: 2.2,
                        opacity: 0.96,
                        fillOpacity: 0.07,
                        fillColor: color,
                        dashArray: '7 5'
                    };
                }

                function labelKecamatan(feature, layer) {
                    const props = feature?.properties || {};
                    const label = props.nama_kecamatan || props.kecamatan || props.label;
                    if (!label) return;

                    layer.bindTooltip(label, {
                        permanent: true,
                        direction: 'center',
                        className: 'sigpala-kecamatan-label'
                    });
                }

                function drawBatasKecamatan() {
                    const collection = batasKecamatan?.data || batasKecamatan;
                    if (!collection || !collection.type) return;

                    L.geoJSON(collection, {
                        interactive: false,
                        style: styleKecamatan,
                        onEachFeature: labelKecamatan
                    }).addTo(batasKecamatanGroup);
                }

                function getLahanColor(status) {
                    if (status === 'Selesai') return '#65bd00';
                    if (status === 'Sedang') return '#eab308';
                    return '#ef4444'; // Belum
                }

                function drawSemuaLahan() {
                    if (!semuaLahan || semuaLahan.length === 0) return;

                    semuaLahanGroup.clearLayers();
                    
                    semuaLahan.forEach(lahan => {
                        let geojsonStr = lahan.polygon_geojson || lahan.geojson || lahan.polygon_area;
                        if (!geojsonStr) return;

                        try {
                            const geoData = typeof geojsonStr === 'string' ? JSON.parse(geojsonStr) : geojsonStr;
                            const status = lahan.status_verifikasi || 'Belum';
                            const color = getLahanColor(status);

                            const layer = L.geoJSON(geoData, {
                                style: {
                                    color: color,
                                    weight: 2,
                                    fillColor: color,
                                    fillOpacity: 0.4
                                },
                                onEachFeature: function(feature, layer) {
                                    // Tooltip
                                    const tooltipContent = `
                                        <div class="font-sans text-xs">
                                            <strong class="text-sm block mb-1">${lahan.nama_lahan || 'Tanpa Nama'}</strong>
                                            <span class="block">Pemilik: ${lahan.pemilik?.nama || lahan.pemilik_lahan?.nama_petani || '-'}</span>
                                            <span class="block">Luas: ${lahan.luas_lahan_hektar ? parseFloat(lahan.luas_lahan_hektar).toFixed(2) : '-'} Ha</span>
                                            <span class="block">Status: ${status}</span>
                                        </div>
                                    `;
                                    layer.bindTooltip(tooltipContent, {
                                        sticky: true,
                                        className: 'bg-white border-0 shadow-lg rounded-xl p-2'
                                    });

                                    // Hover effect
                                    layer.on({
                                        mouseover: function(e) {
                                            const l = e.target;
                                            l.setStyle({ weight: 4, fillOpacity: 0.7 });
                                            l.bringToFront();
                                        },
                                        mouseout: function(e) {
                                            const l = e.target;
                                            l.setStyle({ weight: 2, fillOpacity: 0.4 });
                                        },
                                        click: function(e) {
                                            // Focus to this lahan in list & map
                                            if(lahanIdInput) lahanIdInput.value = lahan.id;
                                            fillForm(lahan, 'lama');
                                            
                                            // Additional panel info (if implemented)
                                            showSidePanelInfo(lahan);
                                        }
                                    });
                                }
                            });

                            semuaLahanGroup.addLayer(layer);
                        } catch(e) {
                            console.error('Invalid geojson for lahan:', lahan.id, e);
                        }
                    });
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
                    if (selectedSourceLabel) selectedSourceLabel.textContent = source === 'baru' ? 'Lahan belum dipetakan' : (source === 'termonitor' ? 'Lahan termonitor' : 'Lahan sudah dipetakan');
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
                    if (selectedMapLabel) selectedMapLabel.textContent = 'Batas Kabupaten Barito Kuala dan layer batas kecamatan tersedia di kontrol peta. Pilih lahan untuk mulai mengatur titik dan batas area.';
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
                drawSemuaLahan();

                // Legend Control
                const legend = L.control({ position: 'bottomright' });
                legend.onAdd = function (map) {
                    const div = L.DomUtil.create('div', 'bg-white p-3 rounded-xl shadow-lg text-xs font-sans border border-slate-200');
                    div.innerHTML = `
                        <h4 class="font-bold text-slate-700 mb-2">Status Verifikasi Lahan</h4>
                        <div class="flex items-center mb-1"><span class="w-3 h-3 rounded-full mr-2" style="background-color: #65bd00; opacity: 0.7"></span> Selesai</div>
                        <div class="flex items-center mb-1"><span class="w-3 h-3 rounded-full mr-2" style="background-color: #eab308; opacity: 0.7"></span> Sedang</div>
                        <div class="flex items-center"><span class="w-3 h-3 rounded-full mr-2" style="background-color: #ef4444; opacity: 0.7"></span> Belum</div>
                    `;
                    return div;
                };
                legend.addTo(map);

                function showSidePanelInfo(lahan) {
                    // Update form title and meta for rich info
                    if (selectedLahanTitle) selectedLahanTitle.textContent = lahan.nama_lahan || 'Detail Lahan';
                    if (selectedLahanMeta) selectedLahanMeta.innerHTML = `
                        <div class="mt-2 text-xs">
                            <span class="block text-slate-600 mb-1"><strong>Luas:</strong> ${lahan.luas_lahan_hektar ? parseFloat(lahan.luas_lahan_hektar).toFixed(2) : '-'} Hektar</span>
                            <span class="block text-slate-600 mb-1"><strong>Status:</strong> ${lahan.status_verifikasi || 'Belum'}</span>
                            <span class="block text-slate-600"><strong>Pemilik:</strong> ${lahan.pemilik?.nama || lahan.pemilik_lahan?.nama_petani || '-'}</span>
                        </div>
                    `;
                }

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
                renderSpatialList(); // Initial render for pagination
            });
        </script>
    @endpush
@endif
