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
@endphp

@if($page === 'manajemen-data-spasial')
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <style>
            #petugasSpasialMap { height: 520px; min-height: 520px; border-radius: 24px; overflow: hidden; z-index: 1; }
            .leaflet-container { font-family: 'Poppins', sans-serif; }
        </style>
    @endpush
@endif

@section('content')
    <div class="space-y-6">
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
                        <h2 class="text-xl font-extrabold text-primary-900">Antrean Pengajuan Lahan Baru</h2>
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
                                    <tr>
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
                                            <div class="flex justify-end gap-2">
                                                <form method="POST" action="{{ url('/petugas/verifikasi-lahan/' . $ambil($item, ['id']) . '/diterima') }}">@csrf<button class="px-4 py-2 rounded-xl bg-green-50 text-green-700 border border-green-200 font-bold hover:bg-green-600 hover:text-white transition">Setujui</button></form>
                                                <form method="POST" action="{{ url('/petugas/verifikasi-lahan/' . $ambil($item, ['id']) . '/ditolak') }}">@csrf<button class="px-4 py-2 rounded-xl bg-red-50 text-red-600 border border-red-200 font-bold hover:bg-red-600 hover:text-white transition">Tolak</button></form>
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
                        <h2 class="text-xl font-extrabold text-primary-900">Antrean Hasil Panen</h2>
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
            </div>
        @endif

        @if($page === 'manajemen-data-spasial')
            <div class="grid xl:grid-cols-5 gap-6">
                <div class="xl:col-span-3 soft-card bg-white rounded-2xl border border-primary-100 p-5">
                    <div class="flex items-center justify-between gap-4 mb-4">
                        <div>
                            <h2 class="text-xl font-extrabold text-primary-900">Peta Manajemen Data Spasial</h2>
                            <p class="text-sm text-slate-500 mt-1">Peta difokuskan ke Kabupaten Batola. Klik peta untuk mengambil titik, dan aktifkan mode polygon untuk menggambar batas lahan.</p>
                        </div>
                        <button type="button" id="btnPolygonMode" class="px-4 py-2 rounded-xl bg-primary-600 text-white font-bold text-sm">Mode Polygon</button>
                    </div>
                    <div id="petugasSpasialMap" class="border border-primary-100"></div>
                    <div class="mt-4 flex flex-wrap gap-2 text-xs text-slate-500">
                        <span class="px-3 py-1 rounded-full bg-primary-50 border border-primary-100">Klik peta: isi latitude/longitude</span>
                        <span class="px-3 py-1 rounded-full bg-primary-50 border border-primary-100">Mode polygon: klik minimal 3 titik</span>
                        <button type="button" id="btnClearPolygon" class="px-3 py-1 rounded-full bg-red-50 text-red-600 border border-red-100 font-bold">Reset Polygon</button>
                    </div>
                </div>

                <div class="xl:col-span-2 soft-card bg-white rounded-2xl border border-primary-100 p-5">
                    <h2 class="text-xl font-extrabold text-primary-900 mb-4">Form Data Spasial Lahan</h2>
                    <form method="POST" action="{{ url('/petugas/spasial/simpan') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-2">Lahan Diterima / Belum Dipetakan</label>
                            <select name="lahan_id" id="lahan_id" class="w-full">
                                <option value="">Tambah data spasial baru</option>
                                @foreach($lahanBelumDipetakan as $lahanItem)
                                    <option value="{{ $ambil($lahanItem, ['id']) }}" data-lahan='@json($lahanItem)'>{{ $ambil($lahanItem, ['nama_lahan']) }} - {{ $ambil($lahanItem, ['pemilik_lahan']) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-slate-500 mb-2">Nama Lahan</label><input name="nama_lahan" id="nama_lahan" class="w-full" required></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-2">Pemilik Lahan</label><input name="pemilik_lahan" id="pemilik_lahan" class="w-full"></div>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-slate-500 mb-2">Kecamatan ID</label><input name="kecamatan_id" id="kecamatan_id" type="number" class="w-full" required></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-2">Kelurahan ID</label><input name="kelurahan_id" id="kelurahan_id" type="number" class="w-full"></div>
                        </div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-slate-500 mb-2">Tipe Lahan ID</label><input name="tipe_lahan_id" id="tipe_lahan_id" type="number" class="w-full"></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-2">Luas Lahan (Ha)</label><input name="luas_lahan_hektar" id="luas_lahan_hektar" type="number" step="0.01" class="w-full" required></div>
                        </div>
                        <div><label class="block text-xs font-bold text-slate-500 mb-2">Alamat Detail</label><textarea name="alamat_detail" id="alamat_detail" rows="2" class="w-full"></textarea></div>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-slate-500 mb-2">Latitude</label><input name="latitude" id="latitude" type="number" step="any" class="w-full" required></div>
                            <div><label class="block text-xs font-bold text-slate-500 mb-2">Longitude</label><input name="longitude" id="longitude" type="number" step="any" class="w-full" required></div>
                        </div>
                        <div><label class="block text-xs font-bold text-slate-500 mb-2">Polygon GeoJSON</label><textarea name="polygon_geojson" id="polygon_geojson" rows="4" class="w-full" placeholder="Polygon akan terisi otomatis dari peta"></textarea></div>
                        <button class="btn-green w-full rounded-2xl py-3 font-extrabold transition">Simpan Data Spasial</button>
                    </form>
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
                            <div><label class="block text-xs font-bold text-slate-500 mb-2">Status Air</label><input name="status_air" class="w-full" placeholder="Normal / Tinggi / Rendah"></div>
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

@if($page === 'manajemen-data-spasial')
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const mapEl = document.getElementById('petugasSpasialMap');
                if (!mapEl || typeof L === 'undefined') return;

                const batolaCenter = [-3.05, 114.62];
                const batolaBounds = L.latLngBounds([[-3.55, 114.20], [-2.45, 115.05]]);
                const map = L.map('petugasSpasialMap', { maxBounds: batolaBounds, maxBoundsViscosity: 0.75 }).setView(batolaCenter, 10);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);

                map.fitBounds(batolaBounds);

                let marker = null;
                let polygonMode = false;
                let polygonPoints = [];
                let polygonLayer = null;

                const latInput = document.getElementById('latitude');
                const lngInput = document.getElementById('longitude');
                const polygonInput = document.getElementById('polygon_geojson');

                function setPoint(latlng) {
                    if (latInput) latInput.value = latlng.lat.toFixed(7);
                    if (lngInput) lngInput.value = latlng.lng.toFixed(7);
                    if (marker) marker.setLatLng(latlng);
                    else marker = L.marker(latlng, { draggable: true }).addTo(map);
                    marker.on('dragend', function(e) {
                        const pos = e.target.getLatLng();
                        if (latInput) latInput.value = pos.lat.toFixed(7);
                        if (lngInput) lngInput.value = pos.lng.toFixed(7);
                    });
                }

                function refreshPolygon() {
                    if (polygonLayer) map.removeLayer(polygonLayer);
                    if (polygonPoints.length >= 3) {
                        polygonLayer = L.polygon(polygonPoints, { color: '#3e7d00', weight: 3, fillOpacity: 0.2 }).addTo(map);
                        const coords = polygonPoints.map(p => [p.lng, p.lat]);
                        coords.push([polygonPoints[0].lng, polygonPoints[0].lat]);
                        if (polygonInput) polygonInput.value = JSON.stringify({ type: 'Polygon', coordinates: [coords] });
                    }
                }

                map.on('click', function(e) {
                    setPoint(e.latlng);
                    if (polygonMode) {
                        polygonPoints.push(e.latlng);
                        refreshPolygon();
                    }
                });

                document.getElementById('btnPolygonMode')?.addEventListener('click', function () {
                    polygonMode = !polygonMode;
                    this.textContent = polygonMode ? 'Mode Polygon Aktif' : 'Mode Polygon';
                    this.classList.toggle('bg-primary-700', polygonMode);
                });

                document.getElementById('btnClearPolygon')?.addEventListener('click', function () {
                    polygonPoints = [];
                    if (polygonLayer) map.removeLayer(polygonLayer);
                    polygonLayer = null;
                    if (polygonInput) polygonInput.value = '';
                });

                document.getElementById('lahan_id')?.addEventListener('change', function() {
                    const option = this.options[this.selectedIndex];
                    if (!option || !option.dataset.lahan) return;
                    try {
                        const data = JSON.parse(option.dataset.lahan);
                        const set = (id, value) => { const el = document.getElementById(id); if (el) el.value = value ?? ''; };
                        set('nama_lahan', data.nama_lahan);
                        set('pemilik_lahan', data.pemilik_lahan);
                        set('kecamatan_id', data.kecamatan_id);
                        set('kelurahan_id', data.kelurahan_id);
                        set('tipe_lahan_id', data.tipe_lahan_id ?? data.kategori_lahan_id);
                        set('luas_lahan_hektar', data.luas_lahan_hektar);
                        set('alamat_detail', data.alamat_detail);
                        set('polygon_geojson', data.polygon_geojson ?? data.geojson ?? '');
                        if (data.latitude && data.longitude) {
                            const latlng = L.latLng(parseFloat(data.latitude), parseFloat(data.longitude));
                            setPoint(latlng);
                            map.setView(latlng, 15);
                        }
                    } catch (e) {}
                });
            });
        </script>
    @endpush
@endif
