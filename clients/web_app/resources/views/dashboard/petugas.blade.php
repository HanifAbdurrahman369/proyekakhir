@extends('layouts.app')

@section('title', 'Dashboard Petugas')

@php
    $page = $page ?? 'dashboard';

    $toCollection = function ($value) {
        if ($value instanceof \Illuminate\Support\Collection) {
            return $value;
        }

        if (is_array($value) && isset($value['data']) && is_array($value['data'])) {
            return collect($value['data']);
        }

        if (is_array($value)) {
            return collect($value);
        }

        return collect();
    };

    $stats = $stats ?? [];

    $pendingLahanList = $toCollection($antreanLahan ?? $pendingLahan ?? []);
    $pendingPanenList = $toCollection($antreanPanen ?? $pendingPanen ?? $antrean ?? []);
    $notifikasiList = $toCollection($notifikasi ?? []);
    $lahanList = $toCollection($lahan ?? []);
    $monitoringList = $toCollection($monitoring ?? []);

    $referensi = $referensi ?? [];
    $petaniList = $toCollection(data_get($referensi, 'petani', []));
    $kecamatanList = $toCollection(data_get($referensi, 'kecamatan', []));
    $kelurahanList = $toCollection(data_get($referensi, 'kelurahan', []));
    $tipeLahanList = $toCollection(data_get($referensi, 'tipe_lahan', []));

    $totalPendingLahan = data_get($stats, 'pending_lahan', $pendingLahanList->count());
    $totalPendingPanen = data_get($stats, 'pending_panen', $pendingPanenList->count());
    $totalPending = data_get($stats, 'total_pending', $totalPendingLahan + $totalPendingPanen);
    $totalNotifikasi = data_get($stats, 'notifikasi', $notifikasiList->count());

    $koleksiLahan = $koleksiLahan ?? [
        'type' => 'FeatureCollection',
        'features' => []
    ];
@endphp

@push('styles')
    @if($page === 'manajemen-data-spasial')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css">
    @endif

    <style>
        #map {
            min-height: 650px;
            box-shadow: inset 0 0 0 1px rgba(231,239,216,.9);
        }

        .leaflet-container {
            font-family: 'Poppins', sans-serif !important;
            border-radius: 22px;
        }

        .leaflet-popup-content-wrapper {
            border-radius: 18px;
            box-shadow: 0 18px 50px rgba(32,60,16,.14);
        }

        .leaflet-draw-toolbar a {
            border-radius: 10px !important;
        }

        .table-responsive-custom {
            overflow-x: auto;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .status-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .status-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        @media (max-width: 768px) {
            #map {
                height: 460px !important;
                min-height: 460px;
            }

            .leaflet-control-container .leaflet-top,
            .leaflet-control-container .leaflet-bottom {
                transform: scale(.92);
                transform-origin: left top;
            }
        }
    </style>
@endpush

@section('content')

@if(session('success'))
    <div class="mb-4 bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded-[22px] shadow-[0_14px_38px_rgba(32,60,16,.06)] text-sm">
        <span class="font-bold">Berhasil!</span> {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-[22px] shadow-[0_14px_38px_rgba(32,60,16,.06)] text-sm">
        <span class="font-bold">Gagal!</span> {{ session('error') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-[22px] shadow-[0_14px_38px_rgba(32,60,16,.06)] text-sm">
        <span class="font-bold">Validasi gagal!</span>
        <ul class="mt-2 list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if($page === 'dashboard')

    <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 mb-7">
        <div>
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-bold bg-[#edf8dc] text-[#3E7D00] border border-[#dfeccc]">
                Dashboard Petugas
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#14280b] mt-3 tracking-tight">
                Beranda Petugas
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Ringkasan antrean verifikasi, pemetaan lahan, dan monitoring kondisi lapangan.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-5 lg:gap-6 mb-7">
        <div class="glass-card p-6 rounded-[28px] border border-[#e7efd8]">
            <div class="w-12 h-12 rounded-2xl bg-yellow-50 text-yellow-700 flex items-center justify-center mb-5">🌾</div>
            <p class="text-xs text-slate-500 font-bold uppercase tracking-wide">Lahan Pending</p>
            <h3 class="font-extrabold text-[#14280b] text-3xl mt-2">{{ $totalPendingLahan }}</h3>
        </div>

        <div class="glass-card p-6 rounded-[28px] border border-[#e7efd8]">
            <div class="w-12 h-12 rounded-2xl bg-orange-50 text-orange-700 flex items-center justify-center mb-5">📦</div>
            <p class="text-xs text-slate-500 font-bold uppercase tracking-wide">Panen Pending</p>
            <h3 class="font-extrabold text-[#14280b] text-3xl mt-2">{{ $totalPendingPanen }}</h3>
        </div>

        <div class="glass-card p-6 rounded-[28px] border border-[#e7efd8]">
            <div class="w-12 h-12 rounded-2xl bg-[#edf8dc] text-[#3E7D00] flex items-center justify-center mb-5">✅</div>
            <p class="text-xs text-slate-500 font-bold uppercase tracking-wide">Total Antrean</p>
            <h3 class="font-extrabold text-[#14280b] text-3xl mt-2">{{ $totalPending }}</h3>
        </div>

        <div class="glass-card p-6 rounded-[28px] border border-[#e7efd8]">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-700 flex items-center justify-center mb-5">🔔</div>
            <p class="text-xs text-slate-500 font-bold uppercase tracking-wide">Notifikasi</p>
            <h3 class="font-extrabold text-[#14280b] text-3xl mt-2">{{ $totalNotifikasi }}</h3>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 lg:gap-6">
        <a href="/verifikasi-data-petani" class="glass-card p-6 rounded-[28px] border border-[#e7efd8] hover:-translate-y-1 transition group">
            <div class="w-12 h-12 rounded-2xl bg-[#edf8dc] text-[#3E7D00] flex items-center justify-center mb-5">✅</div>
            <h3 class="font-extrabold text-[#14280b] text-base">Verifikasi Data Petani</h3>
            <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                Validasi lahan baru dan laporan hasil panen dari petani.
            </p>
        </a>

        <a href="/manajemen-data-spasial" class="glass-card p-6 rounded-[28px] border border-[#e7efd8] hover:-translate-y-1 transition group">
            <div class="w-12 h-12 rounded-2xl bg-[#edf8dc] text-[#3E7D00] flex items-center justify-center mb-5">🗺️</div>
            <h3 class="font-extrabold text-[#14280b] text-base">Manajemen Data Spasial</h3>
            <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                Kelola pemetaan polygon dan titik koordinat lahan legal.
            </p>
        </a>

        <a href="/input-parameter-lingkungan" class="glass-card p-6 rounded-[28px] border border-[#e7efd8] hover:-translate-y-1 transition group">
            <div class="w-12 h-12 rounded-2xl bg-[#edf8dc] text-[#3E7D00] flex items-center justify-center mb-5">🌱</div>
            <h3 class="font-extrabold text-[#14280b] text-base">Input Parameter Lingkungan</h3>
            <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                Catat pH air, tinggi muka air, status air, dan catatan lapangan.
            </p>
        </a>
    </div>

    <div class="glass-card rounded-[28px] border border-[#e7efd8] mt-7 overflow-hidden">
        <div class="p-6 border-b border-[#e7efd8]">
            <h3 class="font-extrabold text-[#14280b] text-lg">Notifikasi Terbaru</h3>
            <p class="text-xs text-slate-500 mt-1">Antrean aktivitas petani yang membutuhkan perhatian petugas.</p>
        </div>

        <div class="table-responsive-custom">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase">Judul</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase">Pesan</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($notifikasiList->take(8) as $item)
                        <tr class="hover:bg-[#f7fced]/50 transition">
                            <td class="px-6 py-4 font-bold text-[#14280b]">{{ data_get($item, 'judul', '-') }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ data_get($item, 'pesan', '-') }}</td>
                            <td class="px-6 py-4 text-slate-500">{{ data_get($item, 'created_at', '-') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-10 text-sm text-slate-400 italic">
                                Belum ada notifikasi baru.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@elseif($page === 'manajemen-data-spasial')

    <div class="mb-7">
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-bold bg-[#edf8dc] text-[#3E7D00] border border-[#dfeccc]">
            Petugas Lapangan
        </span>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#14280b] mt-3 tracking-tight">
            Manajemen Data Spasial
        </h1>
        <p class="text-sm text-slate-500 mt-1">
            Pemetaan batas polygon, titik koordinat, dan data identitas lahan yang sudah legal.
        </p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 glass-card rounded-[28px] border border-[#e7efd8] p-4 relative">
            <div id="map" class="w-full h-[650px] rounded-[22px] z-10 border border-slate-200"></div>

            <div class="absolute bottom-6 left-6 z-[400] bg-white/90 backdrop-blur-sm p-3 rounded-[26px] shadow-lg border border-slate-100 text-xs text-slate-700">
                <span class="font-bold text-[#14280b]">Peralatan Peta:</span><br>
                <span class="inline-block mt-1">⬟ <b>Polygon:</b> tarik garis batas luas lahan</span><br>
                <span class="inline-block mt-1">📍 <b>Marker:</b> tandai titik koordinat lahan</span><br>
                <span class="inline-block mt-1 italic text-slate-500">Data peta hanya menampilkan lahan berstatus DITERIMA.</span>
            </div>
        </div>

        <div class="glass-card rounded-[28px] border border-[#e7efd8] p-5 sm:p-6 overflow-y-auto max-h-[680px]">
            <h3 id="formTitle" class="font-bold text-[#14280b] mb-4 text-sm border-b border-[#e7efd8] pb-2">
                Informasi Detail Lahan Sawah
            </h3>

            <form id="formLahanSpasial" action="/petugas/spasial/simpan" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="_method" id="methodField" value="POST">

                <input type="hidden" id="geojson_data" name="geojson">
                <input type="hidden" id="polygon_geojson" name="polygon_geojson">
                <input type="hidden" id="latitude" name="latitude">
                <input type="hidden" id="longitude" name="longitude">
                <input type="hidden" id="pemilik_lahan" name="pemilik_lahan">

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Nama Lahan</label>
                    <input type="text" name="nama_lahan" class="w-full text-sm p-2.5 rounded-[26px] border-slate-300 focus:ring-[#65bd00]" placeholder="Misal: Sawah Blok A" required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Pilih Petani</label>
                    <select id="user_id" name="user_id" class="w-full text-sm p-2.5 rounded-[26px] border-slate-300 focus:ring-[#65bd00]" required onchange="document.getElementById('pemilik_lahan').value = this.options[this.selectedIndex].text;">
                        <option value="">Pilih Data Petani...</option>
                        @foreach($petaniList as $p)
                            <option value="{{ data_get($p, 'id') }}">
                                {{ data_get($p, 'name', data_get($p, 'nama_lengkap', data_get($p, 'email', 'Petani'))) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Luas (Hektar)</label>
                        <input type="number" step="0.01" id="luas_lahan_hektar" name="luas_lahan_hektar" class="w-full text-sm p-2.5 rounded-[26px] border-slate-300 focus:ring-[#65bd00]" placeholder="Otomatis / manual" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Tipe Rawa</label>
                        <select id="tipe_lahan_id" name="tipe_lahan_id" class="w-full text-sm p-2.5 rounded-[26px] border-slate-300 focus:ring-[#65bd00]">
                            <option value="">Pilih...</option>
                            @foreach($tipeLahanList as $t)
                                <option value="{{ data_get($t, 'id') }}">
                                    {{ data_get($t, 'nama_tipe', data_get($t, 'name', '-')) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Kecamatan</label>
                        <select id="kecamatan_id" name="kecamatan_id" class="w-full text-sm p-2.5 rounded-[26px] border-slate-300 focus:ring-[#65bd00]" required>
                            <option value="">Pilih...</option>
                            @foreach($kecamatanList as $k)
                                <option value="{{ data_get($k, 'id') }}">
                                    {{ data_get($k, 'nama_kecamatan', data_get($k, 'name', '-')) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Kelurahan</label>
                        <select id="kelurahan_id" name="kelurahan_id" class="w-full text-sm p-2.5 rounded-[26px] border-slate-300 focus:ring-[#65bd00]" required>
                            <option value="">Pilih...</option>
                            @foreach($kelurahanList as $kel)
                                <option value="{{ data_get($kel, 'id') }}" data-kecamatan="{{ data_get($kel, 'kecamatan_id') }}">
                                    {{ data_get($kel, 'nama_kelurahan', data_get($kel, 'name', '-')) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Alamat Detail</label>
                    <textarea name="alamat_detail" rows="3" class="w-full text-sm p-2.5 rounded-[22px] border-slate-300 focus:ring-[#65bd00]" placeholder="Alamat lengkap lokasi lahan"></textarea>
                </div>

                <div class="pt-3 flex flex-col gap-2">
                    <button type="submit" id="btnSubmitForm" class="w-full py-2.5 bg-primary-800 hover:bg-primary-900 text-white text-sm font-bold rounded-[26px] transition shadow-md">
                        💾 Simpan Manajemen Data Spasial
                    </button>

                    <button type="button" id="btnResetForm" onclick="resetFormulirKeDefault()" class="hidden w-full py-2 bg-gray-200 hover:bg-gray-300 text-slate-700 text-sm font-bold rounded-[26px] transition">
                        ❌ Batal Edit / Buat Baru
                    </button>
                </div>
            </form>
        </div>
    </div>

@elseif($page === 'input-parameter-lingkungan')

    <div class="mb-7">
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-bold bg-[#edf8dc] text-[#3E7D00] border border-[#dfeccc]">
            Monitoring Lingkungan
        </span>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#14280b] mt-3 tracking-tight">
            Input Parameter Lingkungan
        </h1>
        <p class="text-sm text-slate-500 mt-1">
            Pencatatan kondisi air dan catatan survei hanya untuk lahan yang sudah diterima petugas.
        </p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-1 glass-card rounded-[28px] border border-[#e7efd8] p-6">
            <h3 class="font-extrabold text-[#14280b] text-lg mb-1">Form Monitoring</h3>
            <p class="text-xs text-slate-500 mb-5">Data monitoring akan tersimpan ke riwayat kondisi lahan.</p>

            <form action="/petugas/monitoring/simpan" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Pilih Lahan Legal</label>
                    <select name="lahan_id" class="w-full text-sm p-3 rounded-[26px] border-slate-300 focus:ring-[#65bd00]" required>
                        <option value="">Pilih Lahan...</option>
                        @foreach($lahanList as $item)
                            <option value="{{ data_get($item, 'id') }}">
                                {{ data_get($item, 'nama_lahan', '-') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Tanggal Cek</label>
                    <input type="date" name="tanggal_cek" value="{{ date('Y-m-d') }}" class="w-full text-sm p-3 rounded-[26px] border-slate-300 focus:ring-[#65bd00]" required>
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">pH Air</label>
                        <input type="number" step="0.01" name="ph_air" class="w-full text-sm p-3 rounded-[26px] border-slate-300 focus:ring-[#65bd00]" placeholder="6.5">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Tinggi Air</label>
                        <input type="number" step="0.01" name="tinggi_muka_air" class="w-full text-sm p-3 rounded-[26px] border-slate-300 focus:ring-[#65bd00]" placeholder="35">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Status Air</label>
                    <select name="status_air" class="w-full text-sm p-3 rounded-[26px] border-slate-300 focus:ring-[#65bd00]">
                        <option value="Normal">Normal</option>
                        <option value="Surut">Surut</option>
                        <option value="Pasang">Pasang</option>
                        <option value="Banjir">Banjir</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Kekeruhan Air</label>
                    <input type="text" name="kekeruhan_air" class="w-full text-sm p-3 rounded-[26px] border-slate-300 focus:ring-[#65bd00]" placeholder="Jernih / Keruh">
                </div>

                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Latitude</label>
                        <input type="text" name="latitude" class="w-full text-sm p-3 rounded-[26px] border-slate-300 focus:ring-[#65bd00]" placeholder="-3.10250000">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Longitude</label>
                        <input type="text" name="longitude" class="w-full text-sm p-3 rounded-[26px] border-slate-300 focus:ring-[#65bd00]" placeholder="114.58250000">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1">Catatan Petugas</label>
                    <textarea name="catatan_petugas" rows="3" class="w-full text-sm p-3 rounded-[22px] border-slate-300 focus:ring-[#65bd00]" placeholder="Catatan kondisi lapangan..."></textarea>
                </div>

                <button type="submit" class="w-full py-3 bg-primary-800 hover:bg-primary-900 text-white text-sm font-bold rounded-[26px] transition shadow-md">
                    Simpan Parameter Lingkungan
                </button>
            </form>
        </div>

        <div class="xl:col-span-2 glass-card rounded-[28px] border border-[#e7efd8] overflow-hidden">
            <div class="p-6 border-b border-[#e7efd8]">
                <h3 class="font-extrabold text-[#14280b] text-lg">Riwayat Monitoring Terbaru</h3>
                <p class="text-xs text-slate-500 mt-1">Data kondisi lahan yang sudah dicatat oleh petugas.</p>
            </div>

            <div class="table-responsive-custom">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase">Lahan</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase">Tanggal</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase">pH Air</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase">Tinggi Air</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($monitoringList as $row)
                            <tr class="hover:bg-[#f7fced]/50 transition">
                                <td class="px-6 py-4 font-bold text-[#14280b]">
                                    {{ data_get($row, 'lahan.nama_lahan', data_get($row, 'nama_lahan', '-')) }}
                                </td>
                                <td class="px-6 py-4">{{ data_get($row, 'tanggal_cek', '-') }}</td>
                                <td class="px-6 py-4">{{ data_get($row, 'ph_air', '-') }}</td>
                                <td class="px-6 py-4">{{ data_get($row, 'tinggi_muka_air', '-') }}</td>
                                <td class="px-6 py-4">{{ data_get($row, 'status_air', '-') }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ data_get($row, 'catatan_petugas', '-') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-10 text-sm text-slate-400 italic">
                                    Belum ada data monitoring kondisi lahan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@elseif($page === 'verifikasi-data-petani')

    <div class="mb-7">
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-bold bg-[#edf8dc] text-[#3E7D00] border border-[#dfeccc]">
            Verifikasi Petugas
        </span>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#14280b] mt-3 tracking-tight">
            Verifikasi Data Petani
        </h1>
        <p class="text-sm text-slate-500 mt-1">
            Petugas menerima atau menolak data petani. Data diterima akan menjadi legal dan masuk ke peta/statistik.
        </p>
    </div>

    <div class="glass-card rounded-[28px] border border-[#e7efd8] overflow-hidden mb-7">
        <div class="p-6 border-b border-[#e7efd8]">
            <h3 class="font-extrabold text-[#14280b] text-lg">Antrean Lahan Baru</h3>
            <p class="text-xs text-slate-500 mt-1">Lahan yang diajukan petani dan masih berstatus PENDING.</p>
        </div>

        <div class="table-responsive-custom">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase">Petani</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase">Lahan</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase">Luas</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase">Wilayah</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase">Status</th>
                        <th class="px-6 py-4 text-center text-xs font-bold uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($pendingLahanList as $row)
                        <tr class="hover:bg-[#f7fced]/50 transition">
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-[#14280b]">
                                    {{ data_get($row, 'nama_petani', data_get($row, 'pemilik_lahan', '-')) }}
                                </p>
                                <p class="text-xs text-slate-500 mt-1">{{ data_get($row, 'email_petani', '-') }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-[#14280b]">{{ data_get($row, 'nama_lahan', '-') }}</p>
                                <p class="text-xs text-slate-500 mt-1">{{ data_get($row, 'alamat_detail', '-') }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-emerald-600">
                                {{ number_format((float) data_get($row, 'luas_lahan_hektar', 0), 2) }} Ha
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-700">
                                {{ data_get($row, 'nama_kelurahan', '-') }}<br>
                                <span class="text-xs text-slate-500">{{ data_get($row, 'nama_kecamatan', '-') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="status-badge status-pending">{{ data_get($row, 'status_verifikasi', 'PENDING') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-center gap-2">
                                    <form action="/petugas/verifikasi/lahan/{{ data_get($row, 'id') }}/approve" method="POST" onsubmit="return confirm('Terima pengajuan lahan ini?')">
                                        @csrf
                                        <button type="submit" class="bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-600 hover:text-white px-4 py-2 rounded-[26px] text-xs font-bold transition">
                                            Setujui
                                        </button>
                                    </form>

                                    <form action="/petugas/verifikasi/lahan/{{ data_get($row, 'id') }}/reject" method="POST" onsubmit="return confirm('Tolak pengajuan lahan ini?')">
                                        @csrf
                                        <button type="submit" class="bg-red-50 text-red-700 border border-red-200 hover:bg-red-600 hover:text-white px-4 py-2 rounded-[26px] text-xs font-bold transition">
                                            Tolak
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-sm text-slate-400 italic">
                                Belum ada pengajuan lahan baru yang perlu diverifikasi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="glass-card rounded-[28px] border border-[#e7efd8] overflow-hidden">
        <div class="p-6 border-b border-[#e7efd8]">
            <h3 class="font-extrabold text-[#14280b] text-lg">Antrean Hasil Panen</h3>
            <p class="text-xs text-slate-500 mt-1">Laporan hasil panen petani yang masih berstatus PENDING.</p>
        </div>

        <div class="table-responsive-custom">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase">Lahan</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase">Bibit</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase">Tanggal Tanam</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase">Tanggal Panen</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase">Hasil Panen</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase">Status</th>
                        <th class="px-6 py-4 text-center text-xs font-bold uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($pendingPanenList as $row)
                        <tr class="hover:bg-[#f7fced]/50 transition">
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-[#14280b]">
                                    {{ data_get($row, 'lahan.nama_lahan', data_get($row, 'nama_lahan', '-')) }}
                                </p>
                                <p class="text-xs text-slate-500 mt-1">
                                    {{ data_get($row, 'lahan.pemilik_lahan', '-') }}
                                </p>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-700">
                                {{ data_get($row, 'bibit.nama_bibit', data_get($row, 'bibit.nama_jenis', '-')) }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-700">
                                {{ data_get($row, 'tanggal_tanam', '-') }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-700">
                                {{ data_get($row, 'tanggal_panen', '-') }}
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-emerald-600">
                                {{ number_format((float) data_get($row, 'hasil_panen', data_get($row, 'hasil_panen_ton', 0)), 2) }} Ton
                            </td>
                            <td class="px-6 py-4">
                                <span class="status-badge status-pending">{{ data_get($row, 'status_verifikasi', 'PENDING') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-center gap-2">
                                    <form action="/petugas/verifikasi/panen/{{ data_get($row, 'id') }}/approve" method="POST" onsubmit="return confirm('Terima laporan hasil panen ini?')">
                                        @csrf
                                        <button type="submit" class="bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-600 hover:text-white px-4 py-2 rounded-[26px] text-xs font-bold transition">
                                            Setujui
                                        </button>
                                    </form>

                                    <form action="/petugas/verifikasi/panen/{{ data_get($row, 'id') }}/reject" method="POST" onsubmit="return confirm('Tolak laporan hasil panen ini?')">
                                        @csrf
                                        <button type="submit" class="bg-red-50 text-red-700 border border-red-200 hover:bg-red-600 hover:text-white px-4 py-2 rounded-[26px] text-xs font-bold transition">
                                            Tolak
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-10 text-sm text-slate-400 italic">
                                Belum ada laporan hasil panen yang perlu diverifikasi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endif

@endsection

@push('scripts')
@if($page === 'manajemen-data-spasial')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@turf/turf@6/turf.min.js"></script>

    <script>
        let map;
        let drawnItems;

        document.addEventListener('DOMContentLoaded', function () {
            map = L.map('map').setView([-3.0560, 114.6046], 11);

            L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: '&copy; Esri'
            }).addTo(map);

            drawnItems = new L.FeatureGroup();
            drawnItems.addTo(map);

            const drawControl = new L.Control.Draw({
                edit: {
                    featureGroup: drawnItems
                },
                draw: {
                    polygon: true,
                    marker: true,
                    polyline: false,
                    rectangle: false,
                    circle: false,
                    circlemarker: false
                }
            });

            map.addControl(drawControl);

            const rawKoleksi = @json($koleksiLahan ?? null);

            let koleksi = rawKoleksi;

            if (rawKoleksi && rawKoleksi.data && rawKoleksi.data.type === 'FeatureCollection') {
                koleksi = rawKoleksi.data;
            }

            if (rawKoleksi && rawKoleksi.type === 'FeatureCollection') {
                koleksi = rawKoleksi;
            }

            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '{{ csrf_token() }}';

            if (koleksi && Array.isArray(koleksi.features)) {
                const existingLayer = L.geoJSON(koleksi, {
                    style: {
                        color: '#059669',
                        fillColor: '#34d399',
                        fillOpacity: 0.4,
                        weight: 2
                    },
                    pointToLayer: function (feature, latlng) {
                        return L.marker(latlng);
                    },
                    onEachFeature: function (feature, layer) {
                        const props = feature.properties || {};

                        const popupHtml = `
                            <div class="p-1 min-w-[220px]">
                                <h4 class="font-bold text-[#14280b] border-b border-slate-200 pb-1 mb-2">${props.nama_lahan || '-'}</h4>

                                <div class="text-xs space-y-1 mb-3">
                                    <p><span class="text-slate-500">Petani:</span> <span class="font-semibold">${props.pemilik_lahan || props.pemilik || '-'}</span></p>
                                    <p><span class="text-slate-500">Luas:</span> <span class="font-bold text-emerald-600">${props.luas_lahan_hektar || props.luas_ha || 0} Ha</span></p>
                                    <p><span class="text-slate-500">Tipe:</span> <span class="font-semibold">${props.nama_tipe || props.tipe_rawa || '-'}</span></p>
                                    <p><span class="text-slate-500">Wilayah:</span> <span class="font-semibold">${props.nama_kelurahan || '-'}, ${props.nama_kecamatan || '-'}</span></p>
                                </div>

                                <div class="flex gap-2 pt-2 border-t border-slate-100">
                                    <button type="button" onclick='editLahanSpasial(${JSON.stringify(feature).replace(/'/g, "&#39;")})' class="flex-1 bg-amber-100 text-amber-700 hover:bg-amber-200 py-1.5 rounded text-xs font-bold transition">
                                        ✏️ Edit
                                    </button>

                                    <form action="/petugas/spasial/${props.id}" method="POST" class="flex-1" onsubmit="return confirm('Hapus permanen data spasial lahan ini?');">
                                        <input type="hidden" name="_token" value="${csrfToken}">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="w-full bg-red-100 text-red-700 hover:bg-red-200 py-1.5 rounded text-xs font-bold transition">
                                            🗑️ Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        `;

                        layer.bindPopup(popupHtml);
                    }
                }).addTo(map);

                try {
                    if (existingLayer.getBounds && existingLayer.getBounds().isValid()) {
                        map.fitBounds(existingLayer.getBounds(), { padding: [24, 24] });
                    }
                } catch (error) {
                    console.warn('Bounds peta tidak dapat disesuaikan:', error);
                }
            }

            map.on(L.Draw.Event.CREATED, function (event) {
                const layer = event.layer;
                drawnItems.clearLayers();
                drawnItems.addLayer(layer);

                const geojson = layer.toGeoJSON();

                if (geojson.geometry) {
                    document.getElementById('geojson_data').value = JSON.stringify(geojson.geometry);
                    document.getElementById('polygon_geojson').value = JSON.stringify(geojson.geometry);
                }

                if (event.layerType === 'polygon') {
                    if (window.turf) {
                        document.getElementById('luas_lahan_hektar').value = (turf.area(geojson) / 10000).toFixed(2);
                        const centroid = turf.centroid(geojson);
                        document.getElementById('longitude').value = centroid.geometry.coordinates[0];
                        document.getElementById('latitude').value = centroid.geometry.coordinates[1];
                    }
                }

                if (event.layerType === 'marker') {
                    const latlng = layer.getLatLng();
                    document.getElementById('latitude').value = latlng.lat;
                    document.getElementById('longitude').value = latlng.lng;
                    document.getElementById('luas_lahan_hektar').value = document.getElementById('luas_lahan_hektar').value || 0;
                }
            });

            const kecamatanSelect = document.getElementById('kecamatan_id');
            const kelurahanSelect = document.getElementById('kelurahan_id');

            if (kecamatanSelect && kelurahanSelect) {
                kecamatanSelect.addEventListener('change', function () {
                    const kecId = this.value;
                    kelurahanSelect.value = '';

                    Array.from(kelurahanSelect.options).forEach(function (option) {
                        if (option.value === '') {
                            option.style.display = 'block';
                            return;
                        }

                        option.style.display = option.getAttribute('data-kecamatan') == kecId ? 'block' : 'none';
                    });
                });
            }
        });

        window.editLahanSpasial = function (feature) {
            const props = feature.properties || {};

            document.getElementById('formTitle').innerText = '✏️ Edit Informasi Lahan Sawah';
            document.getElementById('formLahanSpasial').action = `/petugas/spasial/${props.id}`;
            document.getElementById('methodField').value = 'PUT';
            document.getElementById('btnSubmitForm').innerText = '💾 Update Data Spasial Lahan';
            document.getElementById('btnResetForm').classList.remove('hidden');

            document.querySelector('[name="nama_lahan"]').value = props.nama_lahan || '';
            document.querySelector('[name="alamat_detail"]').value = props.alamat_detail || '';
            document.getElementById('luas_lahan_hektar').value = props.luas_lahan_hektar || props.luas_ha || '';
            document.getElementById('latitude').value = props.latitude || '';
            document.getElementById('longitude').value = props.longitude || '';
            document.getElementById('geojson_data').value = JSON.stringify(feature.geometry || {});
            document.getElementById('polygon_geojson').value = JSON.stringify(feature.geometry || {});
            document.getElementById('pemilik_lahan').value = props.pemilik_lahan || props.pemilik || '';

            document.getElementById('user_id').value = props.user_id || props.pemilik_lahan_id || '';
            document.getElementById('tipe_lahan_id').value = props.tipe_lahan_id || '';
            document.getElementById('kecamatan_id').value = props.kecamatan_id || '';

            document.getElementById('kecamatan_id').dispatchEvent(new Event('change'));

            setTimeout(function () {
                document.getElementById('kelurahan_id').value = props.kelurahan_id || '';
            }, 50);

            drawnItems.clearLayers();

            const editLayer = L.geoJSON(feature, {
                style: {
                    color: '#dc2626',
                    fillColor: '#ef4444',
                    fillOpacity: 0.4
                }
            }).getLayers()[0];

            if (editLayer) {
                drawnItems.addLayer(editLayer);

                if (editLayer.getBounds) {
                    map.fitBounds(editLayer.getBounds(), { padding: [24, 24] });
                } else if (editLayer.getLatLng) {
                    map.setView(editLayer.getLatLng(), 15);
                }
            }

            document.getElementById('formTitle').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        };

        window.resetFormulirKeDefault = function () {
            document.getElementById('formTitle').innerText = 'Informasi Detail Lahan Sawah';
            document.getElementById('formLahanSpasial').action = '/petugas/spasial/simpan';
            document.getElementById('methodField').value = 'POST';
            document.getElementById('btnSubmitForm').innerText = '💾 Simpan Manajemen Data Spasial';
            document.getElementById('btnResetForm').classList.add('hidden');
            document.getElementById('formLahanSpasial').reset();
            drawnItems.clearLayers();
        };
    </script>
@endif
@endpush