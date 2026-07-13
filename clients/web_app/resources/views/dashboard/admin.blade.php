@extends('layouts.app')

@section('title', isset($tableNames) ? 'Manajemen Data Master' : 'Dashboard Admin')

@section('content')

@push('styles')
<style>
    .admin-section { animation: fadeUp .22s ease-out; }
    @keyframes fadeUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
    .admin-section table { width:100%; }
    .admin-section thead th { white-space:nowrap; }
    .admin-section .overflow-x-auto,
    .admin-section table { scrollbar-width: thin; }
    .admin-section a,
    .admin-section button { transition: all .2s ease; }
    .admin-section a:hover,
    .admin-section button:hover { transform: translateY(-1px); }
    @media (max-width: 768px) {
        .admin-section table { min-width: 860px; }
        .admin-section .grid { gap: 1rem; }
    }
</style>
@endpush



@php
    // Deteksi mode aktif berdasarkan parameter data master dari MasterDataController
    $isMasterMode = isset($tableNames);
    $users = $users ?? [];
    $kecamatan = $kecamatan ?? [];
    $kelurahan = $kelurahan ?? [];
    $komunitas = $komunitas ?? [];
    $roleCounts = collect($users)->countBy('role_id');
    $petugasCount = $roleCounts->get(2, 0);
    $adminCount = $roleCounts->get(4, 0);
    $pejabatCount = $roleCounts->get(3, 0);
    $petaniCount = $roleCounts->get(1, 0) + $roleCounts->get(5, 0);
    $roleLabels = [
        1 => 'Kelompok Tani',
        2 => 'Petugas',
        3 => 'Pejabat',
        4 => 'Admin',
        5 => 'Brigade Pangan',
    ];
@endphp

{{-- ========================================== --}}
{{-- COMPONENT 1: DYNAMIC PAGE HEADER          --}}
{{-- ========================================== --}}
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-7">
    @if(!$isMasterMode)
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#022c22] tracking-tight" id="main-page-title">
                {{ request()->get('section') === 'komunitas' ? 'Manajemen Komunitas' : 'Manajemen Pengguna' }}
            </h1>
            <p class="text-sm text-slate-500 mt-1 leading-relaxed" id="main-page-desc">
                {{ request()->get('section') === 'komunitas' ? 'Kelola data kelompok tani, brigade pangan, dan petugas BPP' : 'Kelola akses akun dan pantau aktivitas pengguna sistem' }}
            </p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2">
            @if(request()->get('section') === 'komunitas')
                
            @else
                <button onclick="switchSection('create-section')" 
                        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-4 py-2 rounded-[26px] transition shadow-[0_14px_38px_rgba(37,99,235,.15)]">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    Tambah Pengguna
                </button>
            @endif
        </div>
    @else
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#022c22] tracking-tight">Manajemen Data Master (DBA)</h1>
            <p class="text-sm text-slate-500 mt-1 leading-relaxed">Kendali penuh struktur tabel, kolom, dan isi basis data secara real-time</p>
        </div>
        
        {{-- AKSI DINAMIS HEADER DATA MASTER --}}
        <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
            @if(!$tableName)
                {{-- JIKA DI HALAMAN UTAMA (ALL TABLES): Sediakan tombol export masal seluruh database --}}
                <a href="/admin/master/export/excel" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 py-2 rounded-[26px] transition shadow-[0_14px_38px_rgba(4,120,87,.06)] flex items-center gap-1.5">
                    💾 Export Semua Tabel (Excel)
                </a>
                <a href="/admin/master/export/sql" class="bg-[#047857] hover:bg-[#065f46] text-white text-xs font-semibold px-4 py-2 rounded-[26px] transition shadow-[0_14px_38px_rgba(4,120,87,.06)] flex items-center gap-1.5">
                    📄 Export Semua Tabel (SQL)
                </a>
                <button onclick="switchSection('sql-section')" class="bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold px-4 py-2 rounded-[26px] transition shadow-[0_14px_38px_rgba(4,120,87,.06)]">
                    &#9881; Eksekusi SQL / Import
                </button>
            @else
                {{-- JIKA SEDANG MASUK DI SALAH SATU TABEL: Tampilkan tombol aksi normal bawaan tabel tersebut --}}
                <button onclick="switchSection('sql-section')" class="bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold px-4 py-2 rounded-[26px] transition shadow-[0_14px_38px_rgba(4,120,87,.06)]">
                    &#9881; Eksekusi SQL / Import
                </button>
                <a href="/admin/master/export/sql" class="bg-[#047857] hover:bg-[#065f46] text-white text-xs font-semibold px-4 py-2 rounded-[26px] transition shadow-[0_14px_38px_rgba(4,120,87,.06)]">
                    &#8681; Export Full Database (SQL)
                </a>
            @endif
        </div>
    @endif
</div>

{{-- Sesi Global Notifikasi Berhasil / Gagal --}}
@if(session('success'))
    <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 text-xs px-4 py-3 rounded-[26px] mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-red-100 border border-red-200 text-red-800 text-xs px-4 py-3 rounded-[26px] mb-4">{{ session('error') }}</div>
@endif

@if(!$isMasterMode)
    <div id="statistics-cards" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-[#d1fae5] rounded-[22px] p-5 shadow-[0_14px_38px_rgba(4,120,87,.06)]">
            <p class="text-[11px] uppercase tracking-wide font-bold text-slate-400">Total Pengguna</p>
            <p class="text-3xl font-extrabold text-[#022c22] mt-2">{{ count($users) }}</p>
            <p class="text-xs text-slate-500 mt-1">Akun terdaftar di sistem</p>
        </div>
        <div class="bg-white border border-[#d1fae5] rounded-[22px] p-5 shadow-[0_14px_38px_rgba(4,120,87,.06)]">
            <p class="text-[11px] uppercase tracking-wide font-bold text-slate-400">Petugas Lapangan</p>
            <p class="text-3xl font-extrabold text-amber-700 mt-2">{{ $petugasCount }}</p>
            <p class="text-xs text-slate-500 mt-1">Memegang wilayah desa</p>
        </div>
        <div class="bg-white border border-[#d1fae5] rounded-[22px] p-5 shadow-[0_14px_38px_rgba(4,120,87,.06)]">
            <p class="text-[11px] uppercase tracking-wide font-bold text-slate-400">Petani & Brigade</p>
            <p class="text-3xl font-extrabold text-emerald-700 mt-2">{{ $petaniCount }}</p>
            <p class="text-xs text-slate-500 mt-1">Akses pelaporan lahan</p>
        </div>
        <div class="bg-white border border-[#d1fae5] rounded-[22px] p-5 shadow-[0_14px_38px_rgba(4,120,87,.06)]">
            <p class="text-[11px] uppercase tracking-wide font-bold text-slate-400">Pejabat & Admin</p>
            <p class="text-3xl font-extrabold text-blue-700 mt-2">{{ $pejabatCount + $adminCount }}</p>
            <p class="text-xs text-slate-500 mt-1">Akses supervisi dan kelola</p>
        </div>
    </div>
@endif


{{-- ====================================================================== --}}
{{-- MODUL A: INTEGRASI FITUR MANAJEMEN PENGGUNA (USER MANAGEMENT)          --}}
{{-- ====================================================================== --}}
@if(!$isMasterMode)
    {{-- A1: TABEL DATA UTAMA PENGGUNA --}}
    <div id="index-section" class="admin-section block">
        <div class="bg-white rounded-[22px] border border-[#d1fae5] p-5 mb-5 shadow-[0_4px_20px_rgba(4,120,87,.03)] transition-all">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-[#022c22] text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#047857]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                    Filter & Pencarian
                </h3>
                <span class="text-[11px] bg-[#ecfdf5] text-[#047857] px-3 py-1 rounded-[20px] font-bold tracking-wide"><span id="user-filter-count">{{ count($users) }}</span> akun ditampilkan</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 items-end">
                <div class="xl:col-span-2">
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">Pencarian Akun</label>
                    <input id="user-search" type="search" placeholder="Nama, email, no HP..." class="w-full px-4 py-2 border-slate-200 bg-slate-50 rounded-[20px] text-sm focus:bg-white focus:ring-[#047857] focus:border-[#047857] transition-colors">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">Hak Akses</label>
                    <select id="role-filter" class="w-full py-2 px-4 border-slate-200 bg-slate-50 rounded-[20px] text-sm focus:bg-white focus:ring-[#047857] focus:border-[#047857] transition-colors">
                        <option value="">Semua Role</option>
                        @foreach($roleLabels as $roleId => $roleName)
                            <option value="{{ $roleId }}">{{ $roleName }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">Kecamatan</label>
                    <select id="kecamatan-filter" class="w-full py-2 px-4 border-slate-200 bg-slate-50 rounded-[20px] text-sm focus:bg-white focus:ring-[#047857] focus:border-[#047857] transition-colors">
                        <option value="">Semua Kecamatan</option>
                        @foreach($kecamatan as $item)
                            <option value="{{ $item['id'] }}">{{ $item['nama_kecamatan'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">Asal Petugas</label>
                    <div class="flex gap-2">
                        <select id="instansi-filter" class="w-full py-2 px-4 border-slate-200 bg-slate-50 rounded-[20px] text-sm focus:bg-white focus:ring-[#047857] focus:border-[#047857] transition-colors">
                            <option value="">Semua Asal</option>
                            <option value="DINAS_PERTANIAN">Dinas Pertanian</option>
                            <option value="BPP">BPP</option>
                        </select>
                        <button id="reset-user-filter" type="button" class="p-2 border border-slate-200 bg-white rounded-full text-slate-400 hover:text-slate-600 hover:bg-slate-50 transition tooltip" title="Reset Filter">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="glass-card rounded-[28px] border border-[#d1fae5] overflow-hidden">
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left text-sm whitespace-nowrap min-w-max">
                <thead class="bg-[#ecfdf5] text-[#022c22] text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-4">Nama Lengkap</th>
                        <th class="px-6 py-4">Email</th>
                        <th class="px-6 py-4">Hak Akses (Role ID)</th>
                        <th class="px-6 py-4">Wilayah / Komunitas</th>
                        <th class="px-6 py-4">No. Handphone</th>
                        <th class="px-6 py-4 text-center">Aksi Manajemen</th>
                    </tr>
                </thead>
                <tbody class="text-slate-600 divide-y divide-primary-50">
                    @forelse($users as $user)
                    @php
                        $userRoleId = (int)($user['role_id'] ?? 0);
                        $userInstansi = $user['instansi_asal'] ?? '';
                        $userSearch = strtolower(trim(implode(' ', [
                            $user['nama_lengkap'] ?? '',
                            $user['email'] ?? '',
                            $user['no_hp'] ?? '',
                            $user['komunitas_nama'] ?? '',
                            $user['wilayah_kecamatan_nama'] ?? '',
                            implode(' ', $user['wilayah_kelurahan_nama'] ?? []),
                        ])));
                    @endphp
                    <tr class="hover:bg-slate-50 transition"
                        data-user-row
                        data-search="{{ $userSearch }}"
                        data-role="{{ $userRoleId }}"
                        data-kecamatan="{{ $user['wilayah_kecamatan_id'] ?? '' }}"
                        data-instansi="{{ $userInstansi }}">
                        <td class="px-6 py-4 font-medium text-[#022c22]">{{ $user['nama_lengkap'] }}</td>
                        <td class="px-6 py-4">{{ $user['email'] }}</td>
                        <td class="px-6 py-4">
                            @switch($userRoleId)
                                @case(1) <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded text-[11px] font-bold">Kelompok Tani</span> @break
                                @case(2) <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded text-[11px] font-bold">Petugas</span> @break
                                @case(3) <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-[11px] font-bold">Pejabat</span> @break
                                @case(4) <span class="px-2 py-0.5 bg-red-100 text-red-800 rounded text-[11px] font-bold">Admin</span> @break
                                @case(5) <span class="px-2 py-0.5 bg-cyan-100 text-cyan-800 rounded text-[11px] font-bold">Brigade Pangan</span> @break
                                @default <span class="px-2 py-0.5 bg-slate-100 text-slate-800 rounded text-[11px] font-bold">Role {{ $userRoleId }}</span>
                            @endswitch
                        </td>
                        <td class="px-6 py-4">
                            @if($userRoleId === 2)
                                <div class="text-xs leading-5 text-slate-600">
                                    <div class="font-semibold text-[#022c22]">{{ $user['wilayah_kecamatan_nama'] ?? '-' }}</div>
                                    <div>{{ implode(', ', $user['wilayah_kelurahan_nama'] ?? []) ?: '-' }}</div>
                                    <div class="text-slate-400">{{ ($user['instansi_asal'] ?? '') === 'BPP' ? ($user['nama_bpp'] ?? '-') : 'Dinas Pertanian' }}</div>
                                </div>
                            @elseif(in_array($userRoleId, [1, 5], true))
                                <div class="text-xs leading-5 text-slate-600">
                                    <div class="font-semibold text-[#022c22]">{{ $user['komunitas_nama'] ?? '-' }}</div>
                                    <div class="text-slate-400">ID Komunitas: {{ $user['komunitas_id'] ?? '-' }}</div>
                                </div>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">{{ $user['no_hp'] ?? '-' }}</td>
                        <td class="px-6 py-4 text-center flex justify-center gap-2">
                            <button type="button" 
                                    onclick="openEditSection({{ json_encode($user) }})"
                                    class="text-blue-600 hover:bg-blue-50 px-3 py-1 rounded-md transition text-xs font-semibold border border-blue-200">
                                Edit
                            </button>
                            <form action="/admin/users/{{ $user['id'] }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna ini secara permanen dari sistem?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:bg-red-50 px-3 py-1 rounded-md transition text-xs font-semibold border border-red-200">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-8 text-center text-slate-400 text-xs italic">Tidak ada data pengguna terdeteksi dari backend user_service.</td></tr>
                    @endforelse
                    <tr id="user-empty-filter-row" class="hidden"><td colspan="6" class="px-6 py-8 text-center text-slate-400 text-xs italic">Tidak ada akun yang cocok dengan filter.</td></tr>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    </div>
    
    {{-- A1-B: TABEL DATA KOMUNITAS --}}
    <div id="komunitas-section" class="admin-section hidden">
        <div class="bg-white rounded-[22px] border border-[#d1fae5] p-5 mb-5 shadow-[0_4px_20px_rgba(4,120,87,.03)] transition-all">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-4 gap-4">
                <h3 class="font-bold text-[#022c22] text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#047857]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                    Data Komunitas
                </h3>
                <div class="flex gap-2 flex-wrap">
                    <button onclick="document.getElementById('import-komunitas-modal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-[26px] transition border border-indigo-700 shadow-sm flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        Import XLSX
                    </button>
                    <a href="/admin/komunitas/export" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 py-2 rounded-[26px] transition border border-emerald-700 shadow-sm flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Export XLSX
                    </a>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">Pencarian Komunitas</label>
                    <input id="komunitas-search" type="search" placeholder="Nama komunitas, BPP, dsb..." class="w-full px-4 py-2 border-slate-200 bg-slate-50 rounded-[20px] text-sm focus:bg-white focus:ring-[#047857] focus:border-[#047857] transition-colors">
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">Jenis Komunitas</label>
                    <select id="komunitas-jenis-filter" class="w-full py-2 px-4 border-slate-200 bg-slate-50 rounded-[20px] text-sm focus:bg-white focus:ring-[#047857] focus:border-[#047857] transition-colors">
                        <option value="">Semua Jenis</option>
                        <option value="komunitas_tani">Komunitas Tani</option>
                        <option value="brigade_pangan">Brigade Pangan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wide mb-1.5">Kecamatan</label>
                    <select id="komunitas-kecamatan-filter" class="w-full py-2 px-4 border-slate-200 bg-slate-50 rounded-[20px] text-sm focus:bg-white focus:ring-[#047857] focus:border-[#047857] transition-colors">
                        <option value="">Semua Kecamatan</option>
                        @foreach($kecamatan as $item)
                            <option value="{{ $item['id'] }}">{{ $item['nama_kecamatan'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="glass-card rounded-[28px] border border-[#d1fae5] overflow-hidden">
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left text-sm whitespace-nowrap min-w-max">
                <thead class="bg-[#ecfdf5] text-[#022c22] text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-4">Nama / Jenis</th>
                        <th class="px-6 py-4">Ketua / PIC</th>
                        <th class="px-6 py-4">Kecamatan</th>
                        <th class="px-6 py-4">Kelurahan Terkait</th>
                        <th class="px-6 py-4">Instansi Asal (BPP)</th>
                        <th class="px-6 py-4 text-center">Aksi Manajemen</th>
                    </tr>
                </thead>
                <tbody class="text-slate-600 divide-y divide-primary-50">
                    @forelse($komunitas as $kom)
                    @php
                        $jenis = $kom['jenis_komunitas'] ?? '';
                        $kecId = $kom['wilayah_kecamatan_id'] ?? '';
                        $kecNama = '';
                        foreach($kecamatan as $k) { if($k['id'] == $kecId) $kecNama = $k['nama_kecamatan']; }

                        $kelIds = [];
                        if (isset($kom['wilayah_kelurahan_ids'])) {
                            if (is_array($kom['wilayah_kelurahan_ids'])) {
                                $kelIds = $kom['wilayah_kelurahan_ids'];
                            } elseif (is_string($kom['wilayah_kelurahan_ids'])) {
                                $kelIds = json_decode($kom['wilayah_kelurahan_ids'], true) ?? [];
                            }
                        }
                        $kelNames = [];
                        foreach($kelurahan as $kel) {
                            if(in_array($kel['id'], $kelIds)) $kelNames[] = $kel['nama_kelurahan'];
                        }

                        $searchStr = strtolower(trim(implode(' ', [
                            $kom['nama_komunitas'] ?? '',
                            $kom['nama'] ?? '',
                            $kecNama,
                            implode(' ', $kelNames),
                            $kom['nama_bpp'] ?? ''
                        ])));
                    @endphp
                    <tr class="hover:bg-slate-50 transition"
                        data-komunitas-row
                        data-search="{{ $searchStr }}"
                        data-jenis="{{ $jenis }}"
                        data-kecamatan="{{ $kecId }}">
                        <td class="px-6 py-4">
                            <div class="font-bold text-[#022c22]">{{ $kom['nama_komunitas'] ?? '-' }}</div>
                            <div class="text-[10px] uppercase font-semibold tracking-wider text-slate-400 mt-0.5">{{ str_replace('_', ' ', $jenis) }}</div>
                        </td>
                        <td class="px-6 py-4">{{ $kom['nama'] ?? '-' }}</td>
                        <td class="px-6 py-4">{{ $kecNama ?: '-' }}</td>
                        <td class="px-6 py-4 text-xs">{{ implode(', ', $kelNames) ?: '-' }}</td>
                        <td class="px-6 py-4">
                            @if($jenis === 'BPP')
                                <div class="font-semibold">{{ $kom['instansi_asal'] ?? '-' }}</div>
                                <div class="text-xs text-slate-400">{{ $kom['nama_bpp'] ?? '-' }}</div>
                            @else
                                <span class="text-slate-300">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center flex justify-center gap-2">
                            <button type="button" 
                                    onclick='openEditKomunitasModal(@json($kom))'
                                    class="text-blue-600 hover:bg-blue-50 px-3 py-1 rounded-md transition text-xs font-semibold border border-blue-200">
                                Edit
                            </button>
                            <form action="/admin/komunitas/{{ $kom['id'] }}" method="POST" onsubmit="return confirm('Hapus komunitas ini secara permanen?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:bg-red-50 px-3 py-1 rounded-md transition text-xs font-semibold border border-red-200">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-8 text-center text-slate-400 text-xs italic">Belum ada data komunitas.</td></tr>
                    @endforelse
                    <tr id="komunitas-empty-filter-row" class="hidden"><td colspan="6" class="px-6 py-8 text-center text-slate-400 text-xs italic">Tidak ada komunitas yang cocok.</td></tr>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    {{-- A2: FORM TAMBAH USER BARU --}}
    <div id="create-section" class="admin-section hidden">
        <div class="bg-white rounded-[28px] border border-slate-100/60 p-8 sm:p-10 shadow-[0_8px_30px_rgb(0,0,0,0.02)] max-w-5xl mx-auto relative overflow-hidden">
            <!-- Dekoratif latar belakang -->
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-[#047857] opacity-[0.03] rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-10 pb-6 border-b border-slate-100 relative z-10">
                <div>
                    <h3 class="text-xl sm:text-2xl font-extrabold text-[#022c22] tracking-tight">Pendaftaran Pengguna Baru</h3>
                    <p class="text-xs sm:text-sm text-slate-500 mt-2">Lengkapi informasi di bawah ini untuk memberikan akses sistem kepada pengguna baru.</p>
                </div>
                <button onclick="switchSection('index-section')" class="mt-4 sm:mt-0 flex items-center justify-center gap-2 px-5 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-800 bg-slate-50 hover:bg-slate-100 rounded-full transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali
                </button>
            </div>
            
            <form action="/admin/users" method="POST" class="relative z-10">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
                    
                    <!-- Kolom Kiri: Informasi Dasar & Kontak -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 shadow-sm border border-emerald-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <h4 class="text-base font-bold text-[#022c22]">Informasi Personal</h4>
                        </div>
                        
                        <div class="bg-slate-50/50 p-6 rounded-[20px] border border-slate-100 space-y-5">
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                                <input type="text" name="nama_lengkap" required class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:bg-white focus:ring-2 focus:ring-[#047857]/30 focus:border-[#047857] transition-all outline-none shadow-sm">
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">NIK / ID Pegawai</label>
                                <input type="text" name="nik" class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:bg-white focus:ring-2 focus:ring-[#047857]/30 focus:border-[#047857] transition-all outline-none shadow-sm">
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Alamat Email Aktif <span class="text-red-500">*</span></label>
                                <input type="email" name="email" required class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:bg-white focus:ring-2 focus:ring-[#047857]/30 focus:border-[#047857] transition-all outline-none shadow-sm">
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Nomor Handphone</label>
                                <input type="text" name="no_hp" class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:bg-white focus:ring-2 focus:ring-[#047857]/30 focus:border-[#047857] transition-all outline-none shadow-sm">
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Alamat Rumah</label>
                                <textarea name="alamat" rows="3" class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:bg-white focus:ring-2 focus:ring-[#047857]/30 focus:border-[#047857] transition-all outline-none resize-none shadow-sm"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Akses, Peran & Keamanan -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 shadow-sm border border-blue-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            </div>
                            <h4 class="text-base font-bold text-[#022c22]">Akses & Kredensial</h4>
                        </div>
                        
                        <div class="bg-blue-50/20 p-6 rounded-[20px] border border-blue-100/50 space-y-5">
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Hak Akses Sistem <span class="text-red-500">*</span></label>
                                <select name="role_id" required data-petugas-role="create" class="js-role-select w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:bg-white focus:ring-2 focus:ring-[#047857]/30 focus:border-[#047857] transition-all outline-none cursor-pointer shadow-sm">
                                    <option value="4">Admin</option>
                                    <option value="3">Pejabat</option>
                                    <option value="2">Petugas</option>
                                    <option value="1">Kelompok Tani</option>
                                    <option value="5">Brigade Pangan</option>
                                </select>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Kata Sandi Baru <span class="text-red-500">*</span></label>
                                    <input type="password" name="password" required minlength="6" class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:bg-white focus:ring-2 focus:ring-[#047857]/30 focus:border-[#047857] transition-all outline-none shadow-sm">
                                </div>
                                <div>
                                    <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Konfirmasi Sandi <span class="text-red-500">*</span></label>
                                    <input type="password" name="password_confirmation" required minlength="6" class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:bg-white focus:ring-2 focus:ring-[#047857]/30 focus:border-[#047857] transition-all outline-none shadow-sm">
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic Fields (Komunitas / Petugas) -->
                        <div class="pt-2">

                            <!-- Dynamic Fields (Komunitas / Petugas) -->
                            <div data-wilayah-fields="create" class="hidden transition-all duration-300 mt-4">
                                <div class="p-5 rounded-[20px] bg-indigo-50/50 border border-indigo-100 space-y-4">
                                    <div class="flex items-center gap-2 border-b border-indigo-100 pb-2">
                                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        <h4 class="text-xs font-bold text-indigo-900 uppercase">Wilayah & Komunitas</h4>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Kecamatan Tugas</label>
                                            <select name="wilayah_kecamatan_id" data-wilayah-input="create" disabled onchange="updateKelurahanOptions(this.value, 'create')" class="w-full bg-white border border-indigo-200 rounded-[10px] text-xs px-3 py-2 focus:ring-2 focus:ring-indigo-500/30 outline-none">
                                                <option value="">-- Pilih Kecamatan --</option>
                                                @foreach($kecamatan as $kec)
                                                    <option value="{{ $kec['id'] }}">{{ $kec['nama_kecamatan'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Kelurahan Tugas (Bisa multi)</label>
                                            <select name="wilayah_kelurahan_ids[]" data-wilayah-input="create" data-kelurahan-select="create" disabled multiple class="w-full bg-white border border-indigo-200 rounded-[10px] text-xs px-3 py-2 focus:ring-2 focus:ring-indigo-500/30 outline-none min-h-[80px]">
                                            </select>
                                            <p class="text-[9px] text-slate-400 mt-1">Tahan Ctrl/Cmd untuk pilih banyak</p>
                                        </div>
                                        <div data-petugas-only-fields="create" class="hidden">
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Instansi Asal (Petugas)</label>
                                            <select name="instansi_asal" data-petugas-input="create" disabled class="w-full bg-white border border-indigo-200 rounded-[10px] text-xs px-3 py-2 focus:ring-2 focus:ring-indigo-500/30 outline-none">
                                                <option value="BPP">BPP</option>
                                                <option value="DINAS_PERTANIAN">DINAS PERTANIAN</option>
                                            </select>
                                        </div>
                                        <div data-petugas-only-fields="create" class="hidden">
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Nama BPP / Instansi</label>
                                            <input type="text" name="nama_bpp" data-petugas-input="create" disabled placeholder="Opsional" class="w-full bg-white border border-indigo-200 rounded-[10px] text-xs px-3 py-2 focus:ring-2 focus:ring-indigo-500/30 outline-none">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="mt-10 pt-6 border-t border-slate-100 flex justify-end gap-3">
                    <button type="submit" class="bg-[#047857] hover:bg-[#065f46] text-white font-bold px-8 py-3 rounded-full text-xs transition-all shadow-[0_8px_20px_rgba(4,120,87,.2)] hover:shadow-[0_10px_25px_rgba(4,120,87,.3)] hover:-translate-y-0.5 flex items-center gap-2">
                        Daftarkan Akun
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- A3: FORM EDIT PENGGUNA --}}
    <div id="edit-section" class="admin-section hidden">
        <div class="bg-white rounded-[28px] border border-slate-100/60 p-8 sm:p-10 shadow-[0_8px_30px_rgb(0,0,0,0.02)] max-w-5xl mx-auto relative">
            <!-- Dekoratif latar belakang -->
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-blue-500 opacity-[0.03] rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-10 pb-6 border-b border-slate-100 relative z-10">
                <div>
                    <h3 class="text-xl sm:text-2xl font-extrabold text-[#022c22] tracking-tight">Perbarui Data Pengguna</h3>
                    <p class="text-xs sm:text-sm text-slate-500 mt-2">Sesuaikan kembali informasi detail, hak akses, atau mereset kata sandi akun ini.</p>
                </div>
                <div class="flex flex-row items-center justify-end gap-3 mt-4 sm:mt-0">
                    <button onclick="switchSection('index-section')" class="flex items-center justify-center gap-2 px-5 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-800 bg-slate-50 hover:bg-slate-100 rounded-full transition-all border border-slate-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Batal
                    </button>
                    <button type="submit" form="edit-form" class="bg-[#047857] hover:bg-[#065f46] text-white font-bold px-6 py-2.5 rounded-full text-xs transition-all shadow-[0_4px_10px_rgba(4,120,87,.2)] hover:shadow-[0_6px_15px_rgba(4,120,87,.3)] hover:-translate-y-0.5 flex items-center gap-2">
                        Simpan Perubahan
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </button>
                </div>
            </div>
            
            <form id="edit-form" method="POST" class="relative z-10">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
                    
                    <!-- Kolom Kiri: Informasi Dasar & Kontak -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 shadow-sm border border-emerald-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <h4 class="text-base font-bold text-[#022c22]">Informasi Personal</h4>
                        </div>
                        
                        <div class="bg-slate-50/50 p-6 rounded-[20px] border border-slate-100 space-y-5">
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                                <input type="text" id="edit-nama" name="nama_lengkap" required class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:bg-white focus:ring-2 focus:ring-[#047857]/30 focus:border-[#047857] transition-all outline-none shadow-sm">
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">NIK / ID Pegawai</label>
                                <input type="text" id="edit-nik" name="nik" class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:bg-white focus:ring-2 focus:ring-[#047857]/30 focus:border-[#047857] transition-all outline-none shadow-sm">
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Alamat Email Aktif <span class="text-red-500">*</span></label>
                                <input type="email" id="edit-email" name="email" required class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:bg-white focus:ring-2 focus:ring-[#047857]/30 focus:border-[#047857] transition-all outline-none shadow-sm">
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Nomor Handphone</label>
                                <input type="text" id="edit-hp" name="no_hp" class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:bg-white focus:ring-2 focus:ring-[#047857]/30 focus:border-[#047857] transition-all outline-none shadow-sm">
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Alamat Rumah</label>
                                <textarea id="edit-alamat" name="alamat" rows="3" class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:bg-white focus:ring-2 focus:ring-[#047857]/30 focus:border-[#047857] transition-all outline-none resize-none shadow-sm"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Akses, Peran & Keamanan -->
                    <div class="space-y-6">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 shadow-sm border border-blue-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            </div>
                            <h4 class="text-base font-bold text-[#022c22]">Akses & Keamanan</h4>
                        </div>
                        
                        <div class="bg-blue-50/20 p-6 rounded-[20px] border border-blue-100/50 space-y-5">
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Hak Akses Sistem <span class="text-red-500">*</span></label>
                                <select id="edit-role" name="role_id" required data-petugas-role="edit" class="js-role-select w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:bg-white focus:ring-2 focus:ring-[#047857]/30 focus:border-[#047857] transition-all outline-none cursor-pointer shadow-sm">
                                    <option value="4">Admin</option>
                                    <option value="3">Pejabat</option>
                                    <option value="2">Petugas</option>
                                    <option value="1">Kelompok Tani</option>
                                    <option value="5">Brigade Pangan</option>
                                </select>
                            </div>
                            
                            <div class="p-5 rounded-[16px] bg-slate-50 border border-slate-200 shadow-sm space-y-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Reset Kata Sandi</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1.5">Kata Sandi Baru</label>
                                        <input type="password" id="edit-password" name="password" minlength="6" class="w-full bg-white border border-slate-200 rounded-[10px] text-xs px-3 py-2.5 focus:ring-2 focus:ring-[#047857]/30 focus:border-[#047857] transition-all outline-none placeholder-slate-300" placeholder="Kosongkan jika tetap">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-500 mb-1.5">Konfirmasi Sandi Baru</label>
                                        <input type="password" id="edit-password-confirmation" name="password_confirmation" minlength="6" class="w-full bg-white border border-slate-200 rounded-[10px] text-xs px-3 py-2.5 focus:ring-2 focus:ring-[#047857]/30 focus:border-[#047857] transition-all outline-none placeholder-slate-300" placeholder="Kosongkan jika tetap">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic Fields (Komunitas / Petugas) -->
                        <div class="pt-2">
                            <div data-wilayah-fields="edit" class="hidden transition-all duration-300 mt-4">
                                <div class="p-5 rounded-[20px] bg-indigo-50/50 border border-indigo-100 space-y-4">
                                    <div class="flex items-center gap-2 border-b border-indigo-100 pb-2">
                                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        <h4 class="text-xs font-bold text-indigo-900 uppercase">Wilayah & Komunitas</h4>
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Kecamatan Tugas</label>
                                            <select name="wilayah_kecamatan_id" id="edit-wilayah-kecamatan" data-wilayah-input="edit" disabled onchange="updateKelurahanOptions(this.value, 'edit')" class="w-full bg-white border border-indigo-200 rounded-[10px] text-xs px-3 py-2 focus:ring-2 focus:ring-indigo-500/30 outline-none">
                                                <option value="">-- Pilih Kecamatan --</option>
                                                @foreach($kecamatan as $kec)
                                                    <option value="{{ $kec['id'] }}">{{ $kec['nama_kecamatan'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Kelurahan Tugas (Bisa multi)</label>
                                            <select name="wilayah_kelurahan_ids[]" data-wilayah-input="edit" data-kelurahan-select="edit" disabled multiple class="w-full bg-white border border-indigo-200 rounded-[10px] text-xs px-3 py-2 focus:ring-2 focus:ring-indigo-500/30 outline-none min-h-[80px]">
                                            </select>
                                            <p class="text-[9px] text-slate-400 mt-1">Tahan Ctrl/Cmd untuk pilih banyak</p>
                                        </div>
                                        <div data-petugas-only-fields="edit" class="hidden">
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Instansi Asal (Petugas)</label>
                                            <select name="instansi_asal" id="edit-instansi-asal" data-petugas-input="edit" disabled class="w-full bg-white border border-indigo-200 rounded-[10px] text-xs px-3 py-2 focus:ring-2 focus:ring-indigo-500/30 outline-none">
                                                <option value="BPP">BPP</option>
                                                <option value="DINAS_PERTANIAN">DINAS PERTANIAN</option>
                                            </select>
                                        </div>
                                        <div data-petugas-only-fields="edit" class="hidden">
                                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Nama BPP / Instansi</label>
                                            <input type="text" name="nama_bpp" id="edit-nama-bpp" data-petugas-input="edit" disabled placeholder="Opsional" class="w-full bg-white border border-indigo-200 rounded-[10px] text-xs px-3 py-2 focus:ring-2 focus:ring-indigo-500/30 outline-none">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                </div>
            </form>
        </div>
    </div>
@endif


{{-- ====================================================================== --}}
{{-- MODUL B: INTEGRASI FITUR DATA MASTER DINAMIS (DBA TOOLS)                --}}
{{-- ====================================================================== --}}
@if($isMasterMode)
    {{-- B1: PEMILIH TABEL BAR --}}
    <div class="bg-white rounded-[22px] border border-[#d1fae5] p-4 mb-4 shadow-[0_14px_38px_rgba(4,120,87,.06)] flex items-center justify-between flex-wrap gap-4">
        <form action="/admin/master" method="GET" class="flex items-center gap-3">
            <label class="text-xs font-bold text-slate-700">Pilih Tabel Database:</label>
            <select name="table" onchange="this.form.submit()" class="border-slate-300 rounded-[26px] text-sm px-4 py-1.5 focus:ring-[#047857] min-w-[220px]">
                <option value="">-- Tampilkan Semua Tabel --</option>
                @foreach($tableNames as $name)
                    <option value="{{ $name }}" {{ $tableName == $name ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </form>

        @if($tableName)
        <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
            <a href="/admin/master/export/excel/{{ $tableName }}" class="px-3 py-1.5 bg-emerald-100 text-emerald-700 rounded-[26px] text-xs font-semibold hover:bg-emerald-200 transition">💾 Export Excel</a>
            <a href="/admin/master/export/sql/{{ $tableName }}" class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded-[26px] text-xs font-semibold hover:bg-gray-200 transition">📄 Export SQL Tabel</a>
            {{-- REVISI: Tombol Tambah Baris Data Telah Dihapus --}}
        </div>
        @endif
    </div>

    {{-- KONDISIONAL: JIKA TABEL DIPILIH --}}
    @if($tableName)
        <div id="master-index-section" class="admin-section block">
            <div class="bg-white rounded-[22px] border border-[#d1fae5] overflow-x-auto shadow-[0_14px_38px_rgba(4,120,87,.06)]">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-[#ecfdf5] text-[#022c22] text-xs uppercase font-semibold">
                        <tr>
                            @foreach($columns as $col)
                                <th class="px-6 py-4">{{ $col }}</th>
                            @endforeach
                            <th class="px-6 py-4 text-center sticky right-0 bg-[#ecfdf5]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-600 divide-y divide-primary-50">
                        @forelse($rows as $row)
                        @php
                            $rowArray = (array)$row;
                            $rowPrimaryValue = $rowArray[$primaryKey] ?? $rowArray['id'] ?? null;
                        @endphp
                        <tr class="hover:bg-slate-50 transition">
                            @foreach($columns as $col)
                                <td class="px-6 py-3 max-w-[220px] truncate" title="{{ $rowArray[$col] ?? 'NULL' }}">
                                    {{ $rowArray[$col] ?? 'NULL' }}
                                </td>
                            @endforeach
                            <td class="px-6 py-3 text-center flex justify-center gap-2 sticky right-0 bg-white shadow-[-5px_0_10px_rgba(0,0,0,0.02)]">
                                <button onclick="openMasterEditSection({{ json_encode($rowArray) }})" class="text-blue-600 hover:bg-blue-50 px-3 py-1 rounded-md text-xs font-semibold border border-blue-200">Edit</button>
                                <form action="/admin/master/{{ $tableName }}/{{ $rowPrimaryValue ?? '' }}" method="POST" onsubmit="return confirm('Apakah anda yakin ingin menghapus data master ini?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:bg-red-50 px-3 py-1 rounded-md text-xs font-semibold border border-red-200" {{ $rowPrimaryValue === null ? 'disabled' : '' }}>Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="{{ count($columns) + 1 }}" class="px-6 py-8 text-center text-slate-400 text-xs italic">Tabel basis data ini belum memiliki isi data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        {{-- TAMPILAN DEFAULT - GRID ALL TABLES --}}
        <div id="master-all-tables-section" class="admin-section block">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($allTablesWithColumns as $tName => $tCols)
                    <div class="bg-white rounded-[22px] border border-[#d1fae5] p-5 shadow-[0_14px_38px_rgba(4,120,87,.06)] hover:shadow-md transition flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="p-2 bg-[#ecfdf5] text-[#047857] rounded-[26px]">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                                </span>
                                <h3 class="font-bold text-slate-800 text-sm tracking-wide uppercase">{{ $tName }}</h3>
                            </div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase mb-1">Daftar Kolom Skema:</p>
                            <div class="flex flex-wrap gap-1.5 mb-6 max-h-[120px] overflow-y-auto pr-1">
                                @foreach($tCols as $cName)
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded text-[10px] font-mono border border-slate-200">{{ $cName }}</span>
                                @endforeach
                            </div>
                        </div>
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                            <a href="/admin/master?table={{ $tName }}" class="text-xs font-semibold text-[#047857] hover:underline">Lihat Data &rarr;</a>
                            <div class="flex gap-1">
                                <a href="/admin/master/export/excel/{{ $tName }}" class="p-1.5 bg-emerald-50 text-emerald-600 border border-emerald-200 rounded-md hover:bg-emerald-100 transition text-[11px] font-medium" title="Export Excel">Excel</a>
                                <a href="/admin/master/export/sql/{{ $tName }}" class="p-1.5 bg-slate-50 text-slate-600 border border-slate-200 rounded-md hover:bg-slate-100 transition text-[11px] font-medium" title="Export SQL">SQL</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="md:col-span-2 lg:col-span-3 rounded-[22px] border border-amber-200 bg-amber-50 p-6 text-sm font-semibold text-amber-800">
                        Daftar tabel database belum diterima dari master_service. Periksa koneksi service atau konfigurasi database.
                    </div>
                @endforelse
            </div>
        </div>
    @endif

    {{-- REVISI: FORM TAMBAH BARIS DATA (master-create-section) TELAH DIHAPUS TOTAL SEHINGGA TIDAK BISA DIAKSES --}}

    {{-- B2: FORM EDIT DATA MASTER (DINAMIS) --}}
    @if($tableName)
    <div id="master-edit-section" class="admin-section hidden">
        <div class="bg-white rounded-[22px] border border-[#d1fae5] p-6 shadow-[0_14px_38px_rgba(4,120,87,.06)] max-w-3xl">
            <h3 class="font-bold text-[#022c22] text-sm mb-4 border-b pb-2">Modifikasi Data Tabel: <span class="text-blue-600">{{ $tableName }}</span></h3>
            <form id="master-edit-form" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf @method('PUT')
                @foreach($columns as $col)
                    @if(!in_array($col, ['created_at', 'updated_at']))
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">{{ $col }} {!! $col == 'id' ? '<span class="text-red-500 text-[10px] font-normal">(Readonly)</span>' : '' !!}</label>
                        <input type="text" id="master_edit_{{ $col }}" name="{{ $col }}" {!! $col == 'id' ? 'readonly class="w-full border-slate-200 bg-slate-100 rounded-[26px] text-sm p-2"' : 'class="w-full border-slate-300 rounded-[26px] text-sm p-2"' !!}>
                    </div>
                    @endif
                @endforeach
                <div class="col-span-1 md:col-span-2 pt-4 flex justify-end gap-2 border-t mt-2">
                    <button type="button" onclick="window.location.href='/admin/master?table={{$tableName}}'" class="px-4 py-2 border rounded-[26px] text-xs font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                    <button type="submit" class="bg-[#047857] hover:bg-primary-700 text-white font-semibold px-6 py-2 rounded-[26px] text-xs">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- B3: EKSEKUSI RAW SQL COMMAND TERMINAL --}}
    <div id="sql-section" class="admin-section hidden">
        <div class="glass-card rounded-[28px] border border-[#d1fae5] p-5 sm:p-6">
            <div class="flex items-center justify-between mb-4 border-b pb-2">
                <h3 class="font-bold text-[#022c22] text-sm">Konsol Eksekusi SQL Mentah (Manipulasi Kolom & Tabel)</h3>
                <button onclick="window.location.href='/admin/master'" class="text-xs text-slate-400 hover:text-slate-600">&times; Tutup</button>
            </div>
            <form action="/admin/master/execute-sql" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs font-medium text-slate-600 mb-2">Masukkan Perintah SQL Query (Mendukung: CREATE TABLE, ALTER TABLE, DROP, atau INSERT):</label>
                    <textarea name="sql" rows="8" required class="w-full border-slate-300 rounded-[26px] text-sm p-3 font-mono text-slate-800 bg-slate-50 focus:ring-amber-500" placeholder="ALTER TABLE nama_tabel ADD nama_kolom VARCHAR(255) NULL;"></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" onclick="return confirm('Peringatan Keamanan Ekstrim: Perintah SQL mentah ini akan merubah struktur database fisik secara langsung di PA2. Lanjutkan?')" class="bg-amber-600 hover:bg-amber-700 text-white font-semibold px-6 py-2 rounded-[26px] text-xs transition shadow-[0_14px_38px_rgba(4,120,87,.06)]">⚡ Eksekusi Query SQL</button>
                </div>
            </form>
        </div>
    </div>
@endif

    {{-- MODALS KOMUNITAS --}}
    <!-- Import Modal -->
    <div id="import-komunitas-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
        <div class="bg-white rounded-[24px] shadow-xl w-full max-w-md p-6 relative">
            <button onclick="document.getElementById('import-komunitas-modal').classList.add('hidden')" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <h3 class="text-lg font-bold text-[#022c22] mb-2">Import Data Komunitas</h3>
            <p class="text-xs text-slate-500 mb-6">Unggah file Excel (.xlsx) sesuai dengan format template yang disediakan.</p>
            
            <form action="/admin/komunitas/import" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs font-bold text-slate-600 mb-2">Pilih File Excel</label>
                    <input type="file" name="file" accept=".xlsx,.xls" required class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                </div>
                <div class="flex justify-end gap-2 mt-6 border-t pt-4">
                    <button type="button" onclick="document.getElementById('import-komunitas-modal').classList.add('hidden')" class="px-4 py-2 border rounded-full text-xs font-semibold text-slate-600 hover:bg-slate-50">Batal</button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded-full text-xs">Mulai Import</button>
                </div>
            </form>
        </div>
    </div>

    
    <!-- Create Komunitas Section -->
    <div id="create-komunitas-section" class="admin-section hidden">
        <div class="bg-white rounded-[28px] border border-slate-100/60 p-8 sm:p-10 shadow-[0_8px_30px_rgb(0,0,0,0.02)] max-w-5xl mx-auto relative overflow-hidden">
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-[#047857] opacity-[0.03] rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-10 pb-6 border-b border-slate-100 relative z-10">
                <div>
                    <h3 class="text-xl sm:text-2xl font-extrabold text-[#022c22] tracking-tight">Tambah Komunitas Baru</h3>
                    <p class="text-xs sm:text-sm text-slate-500 mt-2">Isi detail data komunitas. (Misal: Kelompok Tani, BPP, dsb)</p>
                </div>
                <button type="button" onclick="switchSection('komunitas-section')" class="mt-4 sm:mt-0 flex items-center justify-center gap-2 px-5 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-800 bg-slate-50 hover:bg-slate-100 rounded-full transition-all border border-slate-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Batal
                </button>
            </div>
            
            <form action="/admin/komunitas" method="POST" class="relative z-10">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
                    <div class="space-y-6">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 shadow-sm border border-emerald-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <h4 class="text-base font-bold text-[#022c22]">Informasi Dasar</h4>
                        </div>
                        
                        <div class="bg-slate-50/50 p-6 rounded-[20px] border border-slate-100 space-y-5">
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Jenis Komunitas <span class="text-red-500">*</span></label>
                                <select id="kom_jenis_create" name="jenis_komunitas" required onchange="toggleBppFieldsCreate()" class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:ring-2 focus:ring-[#047857]/30 outline-none">
                                    <option value="">-- Pilih Jenis --</option>
                                    <option value="kelompok_tani">Kelompok Tani</option>
                                    <option value="brigade_pangan">Brigade Pangan</option>
                                    <option value="BPP">Petugas BPP</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Nama Komunitas <span class="text-red-500">*</span></label>
                                <input type="text" name="nama_komunitas" required placeholder="Contoh: Makmur Jaya" class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:ring-2 focus:ring-[#047857]/30 outline-none shadow-sm">
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">NIK Ketua / PIC</label>
                                <input type="text" name="nik" placeholder="Opsional" class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:ring-2 focus:ring-[#047857]/30 outline-none shadow-sm">
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Nama Ketua / PIC <span class="text-red-500">*</span></label>
                                <input type="text" name="nama" required placeholder="Nama penanggung jawab" class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:ring-2 focus:ring-[#047857]/30 outline-none shadow-sm">
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Nomor Handphone</label>
                                <input type="text" name="nomor_hp" class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:ring-2 focus:ring-[#047857]/30 outline-none shadow-sm">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 shadow-sm border border-blue-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                            </div>
                            <h4 class="text-base font-bold text-[#022c22]">Wilayah & Instansi</h4>
                        </div>
                        
                        <div class="bg-blue-50/20 p-6 rounded-[20px] border border-blue-100/50 space-y-5">
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Kecamatan <span class="text-red-500">*</span></label>
                                <select id="kom_kecamatan_create" name="wilayah_kecamatan_id" required onchange="updateKomKelurahanOptionsCreate()" class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:ring-2 focus:ring-[#047857]/30 outline-none shadow-sm">
                                    <option value="">-- Pilih Kecamatan --</option>
                                    @foreach($kecamatan as $kec)
                                        <option value="{{ $kec['id'] }}">{{ $kec['nama_kecamatan'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Kelurahan / Desa (Bisa multi)</label>
                                <select id="kom_kelurahan_create" name="wilayah_kelurahan_ids[]" multiple class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:ring-2 focus:ring-[#047857]/30 outline-none shadow-sm min-h-[80px]">
                                </select>
                                <p class="text-[9px] text-slate-400 mt-1">Tahan Ctrl/Cmd untuk memilih banyak</p>
                            </div>
                            <div class="hidden space-y-5 p-5 bg-emerald-50 rounded-[16px] border border-emerald-100" id="bpp-fields-create">
                                <div>
                                    <label class="block text-[12px] font-bold text-emerald-800 uppercase tracking-wider mb-2">Instansi Asal</label>
                                    <select name="instansi_asal" class="w-full bg-white border border-emerald-200 rounded-[12px] text-sm px-4 py-3 focus:ring-2 focus:ring-emerald-500/30 outline-none">
                                        <option value="BPP">BPP</option>
                                        <option value="DINAS_PERTANIAN">DINAS PERTANIAN</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[12px] font-bold text-emerald-800 uppercase tracking-wider mb-2">Nama Instansi</label>
                                    <input type="text" name="nama_bpp" placeholder="Misal: BPP Kec. XYZ" class="w-full bg-white border border-emerald-200 rounded-[12px] text-sm px-4 py-3 focus:ring-2 focus:ring-emerald-500/30 outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Alamat Lengkap</label>
                                <textarea name="alamat" rows="2" class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:ring-2 focus:ring-[#047857]/30 outline-none resize-none shadow-sm"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-10 pt-6 border-t border-slate-100 flex justify-end gap-3">
                    <button type="submit" class="bg-[#047857] hover:bg-[#065f46] text-white font-bold px-8 py-3 rounded-full text-xs transition-all shadow-[0_8px_20px_rgba(4,120,87,.2)] hover:shadow-[0_10px_25px_rgba(4,120,87,.3)] flex items-center gap-2">
                        Simpan Komunitas Baru
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Komunitas Section -->
    <div id="edit-komunitas-section" class="admin-section hidden">
        <div class="bg-white rounded-[28px] border border-slate-100/60 p-8 sm:p-10 shadow-[0_8px_30px_rgb(0,0,0,0.02)] max-w-5xl mx-auto relative overflow-hidden">
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-blue-500 opacity-[0.03] rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-10 pb-6 border-b border-slate-100 relative z-10">
                <div>
                    <h3 class="text-xl sm:text-2xl font-extrabold text-[#022c22] tracking-tight">Perbarui Data Komunitas</h3>
                    <p class="text-xs sm:text-sm text-slate-500 mt-2">Sesuaikan kembali informasi detail komunitas.</p>
                </div>
                <button type="button" onclick="switchSection('komunitas-section')" class="mt-4 sm:mt-0 flex items-center justify-center gap-2 px-5 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-800 bg-slate-50 hover:bg-slate-100 rounded-full transition-all border border-slate-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Batal
                </button>
            </div>
            
            <form id="edit-komunitas-form" method="POST" class="relative z-10">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
                    <div class="space-y-6">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 shadow-sm border border-emerald-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <h4 class="text-base font-bold text-[#022c22]">Informasi Dasar</h4>
                        </div>
                        
                        <div class="bg-slate-50/50 p-6 rounded-[20px] border border-slate-100 space-y-5">
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Jenis Komunitas <span class="text-red-500">*</span></label>
                                <select id="kom_edit_jenis" name="jenis_komunitas" required onchange="toggleBppFieldsEdit()" class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:ring-2 focus:ring-[#047857]/30 outline-none shadow-sm">
                                    <option value="">-- Pilih Jenis --</option>
                                    <option value="kelompok_tani">Kelompok Tani</option>
                                    <option value="brigade_pangan">Brigade Pangan</option>
                                    <option value="BPP">Petugas BPP</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Nama Komunitas <span class="text-red-500">*</span></label>
                                <input type="text" id="kom_edit_nama_komunitas" name="nama_komunitas" required class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:ring-2 focus:ring-[#047857]/30 outline-none shadow-sm">
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">NIK Ketua / PIC</label>
                                <input type="text" id="kom_edit_nik" name="nik" class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:ring-2 focus:ring-[#047857]/30 outline-none shadow-sm">
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Nama Ketua / PIC <span class="text-red-500">*</span></label>
                                <input type="text" id="kom_edit_nama" name="nama" required class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:ring-2 focus:ring-[#047857]/30 outline-none shadow-sm">
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Nomor Handphone</label>
                                <input type="text" id="kom_edit_hp" name="nomor_hp" class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:ring-2 focus:ring-[#047857]/30 outline-none shadow-sm">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 shadow-sm border border-blue-100">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                            </div>
                            <h4 class="text-base font-bold text-[#022c22]">Wilayah & Instansi</h4>
                        </div>
                        
                        <div class="bg-blue-50/20 p-6 rounded-[20px] border border-blue-100/50 space-y-5">
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Kecamatan <span class="text-red-500">*</span></label>
                                <select id="kom_edit_kecamatan" name="wilayah_kecamatan_id" required onchange="updateKomKelurahanOptionsEdit()" class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:ring-2 focus:ring-[#047857]/30 outline-none shadow-sm">
                                    <option value="">-- Pilih Kecamatan --</option>
                                    @foreach($kecamatan as $kec)
                                        <option value="{{ $kec['id'] }}">{{ $kec['nama_kecamatan'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Kelurahan / Desa (Bisa multi)</label>
                                <select id="kom_edit_kelurahan" name="wilayah_kelurahan_ids[]" multiple class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:ring-2 focus:ring-[#047857]/30 outline-none shadow-sm min-h-[80px]">
                                </select>
                                <p class="text-[9px] text-slate-400 mt-1">Tahan Ctrl/Cmd untuk memilih banyak</p>
                            </div>
                            <div class="hidden space-y-5 p-5 bg-emerald-50 rounded-[16px] border border-emerald-100" id="bpp-fields-edit">
                                <div>
                                    <label class="block text-[12px] font-bold text-emerald-800 uppercase tracking-wider mb-2">Instansi Asal</label>
                                    <select id="kom_edit_instansi" name="instansi_asal" class="w-full bg-white border border-emerald-200 rounded-[12px] text-sm px-4 py-3 focus:ring-2 focus:ring-emerald-500/30 outline-none">
                                        <option value="BPP">BPP</option>
                                        <option value="DINAS_PERTANIAN">DINAS PERTANIAN</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[12px] font-bold text-emerald-800 uppercase tracking-wider mb-2">Nama Instansi</label>
                                    <input type="text" id="kom_edit_nama_bpp" name="nama_bpp" placeholder="Misal: BPP Kec. XYZ" class="w-full bg-white border border-emerald-200 rounded-[12px] text-sm px-4 py-3 focus:ring-2 focus:ring-emerald-500/30 outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[12px] font-bold text-slate-500 uppercase tracking-wider mb-2">Alamat Lengkap</label>
                                <textarea id="kom_edit_alamat" name="alamat" rows="2" class="w-full bg-white border border-slate-200 rounded-[12px] text-sm px-4 py-3 focus:ring-2 focus:ring-[#047857]/30 outline-none resize-none shadow-sm"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-10 pt-6 border-t border-slate-100 flex justify-end gap-3">
                    <button type="submit" class="bg-[#047857] hover:bg-[#065f46] text-white font-bold px-8 py-3 rounded-full text-xs transition-all shadow-[0_8px_20px_rgba(4,120,87,.2)] hover:shadow-[0_10px_25px_rgba(4,120,87,.3)] flex items-center gap-2">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>



{{-- ====================================================================== --}}
{{-- COMPONENT 3: MOTOR ENGINE ROUTING INTERFASE GRAPHIC (JAVASCRIPT)       --}}
{{-- ====================================================================== --}}
<script>
    const kecamatanOptions = @json($kecamatan);
    const kelurahanOptions = @json($kelurahan);
    const komunitasOptions = @json($komunitas);
    const isMasterMode = @json($isMasterMode);
    const masterPrimaryKey = @json($primaryKey ?? 'id');

    function byScope(attribute, scope) {
        return document.querySelector(`[${attribute}="${scope}"]`);
    }

    function syncKomunitasFields(scope) {
        const role = byScope('data-petugas-role', scope);
        const roleId = role ? String(role.value) : '';
        const isKomunitasOrPetugas = (roleId === '1' || roleId === '2' || roleId === '5');
        const isPetugas = (roleId === '2');
        
        const wilayahFields = document.querySelector(`[data-wilayah-fields="${scope}"]`);
        if (wilayahFields) wilayahFields.classList.toggle('hidden', !isKomunitasOrPetugas);
        
        document.querySelectorAll(`[data-wilayah-input="${scope}"]`).forEach(input => {
            input.disabled = !isKomunitasOrPetugas;
            if (!isKomunitasOrPetugas) {
                if (input.multiple) {
                    Array.from(input.options).forEach(opt => opt.selected = false);
                } else {
                    input.value = '';
                }
            }
        });

        document.querySelectorAll(`[data-petugas-only-fields="${scope}"]`).forEach(div => {
            div.classList.toggle('hidden', !isPetugas);
            div.classList.toggle('block', isPetugas);
        });

        document.querySelectorAll(`[data-petugas-input="${scope}"]`).forEach(input => {
            input.disabled = !isPetugas;
            if (!isPetugas) {
                input.value = '';
            }
        });
    }

    function updateKelurahanOptions(kecamatanId, scope, selectedIds = []) {
        const select = byScope('data-kelurahan-select', scope);
        if (!select) return;

        select.innerHTML = '';
        if (!kecamatanId) return;

        const filtered = kelurahanOptions.filter(k => k.kecamatan_id == kecamatanId);
        filtered.forEach(kel => {
            const option = document.createElement('option');
            option.value = kel.id;
            option.textContent = kel.nama_kelurahan;
            if (selectedIds.includes(kel.id)) {
                option.selected = true;
            }
            select.appendChild(option);
        });
    }

    function syncKomunitasFields(scope) {
        const role = byScope('data-petugas-role', scope);
        const roleId = role ? String(role.value) : '';
        const enabled = (roleId === '1' || roleId === '2' || roleId === '5');
        
        setKomunitasFieldsEnabled(scope, enabled, roleId);
        setPetugasFieldsEnabled(scope, roleId === '2');
    }

    function syncRoleScopedFields(scope) {
        syncKomunitasFields(scope);
    }

    function normalizeText(value) {
        return (value || '').toString().toLowerCase().trim();
    }

    function applyUserFilters() {
        const rows = Array.from(document.querySelectorAll('[data-user-row]'));
        if (!rows.length) return;

        const search = normalizeText(document.getElementById('user-search')?.value);
        const role = document.getElementById('role-filter')?.value || '';
        const kecamatan = document.getElementById('kecamatan-filter')?.value || '';
        const instansi = document.getElementById('instansi-filter')?.value || '';
        let visibleCount = 0;

        rows.forEach((row) => {
            const matchSearch = !search || normalizeText(row.dataset.search).includes(search);
            const matchRole = !role || row.dataset.role === role;
            const matchKecamatan = !kecamatan || row.dataset.kecamatan === kecamatan;
            const matchInstansi = !instansi || row.dataset.instansi === instansi;
            const isVisible = matchSearch && matchRole && matchKecamatan && matchInstansi;

            row.classList.toggle('hidden', !isVisible);
            if (isVisible) visibleCount += 1;
        });

        const count = document.getElementById('user-filter-count');
        const emptyRow = document.getElementById('user-empty-filter-row');

        if (count) count.textContent = visibleCount;
        if (emptyRow) emptyRow.classList.toggle('hidden', visibleCount > 0);
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-petugas-role]').forEach((select) => {
            const scope = select.getAttribute('data-petugas-role');
            select.addEventListener('change', () => syncRoleScopedFields(scope));
            syncRoleScopedFields(scope);
        });

        ['user-search', 'role-filter', 'kecamatan-filter', 'instansi-filter'].forEach((id) => {
            const element = document.getElementById(id);
            if (element) element.addEventListener('input', applyUserFilters);
            if (element) element.addEventListener('change', applyUserFilters);
        });

        const resetFilter = document.getElementById('reset-user-filter');
        if (resetFilter) {
            resetFilter.addEventListener('click', () => {
                ['user-search', 'role-filter', 'kecamatan-filter', 'instansi-filter'].forEach((id) => {
                    const element = document.getElementById(id);
                    if (element) element.value = '';
                });
                applyUserFilters();
            });
        }

        applyUserFilters();
    });

    /**
     * Mengatur perpindahan visibilitas antar form dan tabel
     */
    function switchSection(sectionId) {
        document.querySelectorAll('.admin-section').forEach(section => {
            section.classList.remove('block');
            section.classList.add('hidden');
        });
        const activeSection = document.getElementById(sectionId);
        if (activeSection) {
            activeSection.classList.remove('hidden');
            activeSection.classList.add('block');
        }
        
        // Hide statistics cards when not in index-section
        const statsCards = document.getElementById('statistics-cards');
        if (statsCards) {
            if (sectionId === 'index-section') {
                statsCards.style.display = '';
            } else {
                statsCards.style.display = 'none';
            }
        }
    }

    /**
     * Memetakan data user terpilih ke dalam kolom Edit User Form
     */
    function openEditSection(user) {
        const form = document.getElementById('edit-form');
        if (form) {
            form.action = '/admin/users/' + user.id;
            document.getElementById('edit-nama').value = user.nama_lengkap;
            document.getElementById('edit-role').value = user.role_id;
            document.getElementById('edit-email').value = user.email;
            document.getElementById('edit-hp').value = user.no_hp || '';
            document.getElementById('edit-alamat').value = user.alamat || '';
            document.getElementById('edit-nik').value = user.nik || '';
            document.getElementById('edit-password').value = '';
            document.getElementById('edit-password-confirmation').value = '';
            
            // Populate Wilayah fields for Role 1, 2, 5
            if (['1', '2', '5'].includes(user.role_id?.toString())) {
                document.getElementById('edit-wilayah-kecamatan').value = user.wilayah_kecamatan_id || '';
                updateKelurahanOptions(user.wilayah_kecamatan_id, 'edit', user.wilayah_kelurahan_ids || []);
                
                if (user.role_id?.toString() === '2') {
                    document.getElementById('edit-instansi-asal').value = user.instansi_asal || '';
                    document.getElementById('edit-nama-bpp').value = user.nama_bpp || '';
                }
            }

            syncRoleScopedFields('edit');
            switchSection('edit-section');
        }
    }

    /**
     * Memetakan data dinamis basis data master ke dalam Input Form Edit secara otomatis
     */
    function openMasterEditSection(rowData) {
        const tableName = '{{ $tableName ?? "" }}';
        const primaryValue = rowData?.[masterPrimaryKey] ?? rowData?.id;
        if (!tableName || primaryValue === undefined || primaryValue === null || primaryValue === '') {
            alert(`Kesalahan Sistem: Baris data master wajib memiliki primary key [${masterPrimaryKey}] untuk dapat diubah.`);
            return;
        }
        
        const form = document.getElementById('master-edit-form');
        if (form) {
            form.action = `/admin/master/${tableName}/${encodeURIComponent(primaryValue)}`;
            
            // Mapping input field otomatis berdasarkan object key database kolom
            for (const key in rowData) {
                const inputElement = document.getElementById(`master_edit_${key}`);
                if (inputElement) {
                    inputElement.value = rowData[key] !== null ? rowData[key] : '';
                }
            }
            switchSection('master-edit-section');
        }
    }

    // --- KOMUNITAS JAVASCRIPT ---
    
    function openEditKomunitasModal(kom) {
        document.getElementById('edit-komunitas-form').action = '/admin/komunitas/' + kom.id;
        
        document.getElementById('kom_edit_nik').value = kom.nik || '';
        document.getElementById('kom_edit_jenis').value = kom.jenis_komunitas || '';
        document.getElementById('kom_edit_nama_komunitas').value = kom.nama_komunitas || '';
        document.getElementById('kom_edit_nama').value = kom.nama || '';
        document.getElementById('kom_edit_hp').value = kom.nomor_hp || '';
        document.getElementById('kom_edit_kecamatan').value = kom.wilayah_kecamatan_id || '';
        document.getElementById('kom_edit_alamat').value = kom.alamat || '';
        
        toggleBppFieldsEdit();
        
        if (kom.jenis_komunitas === 'BPP') {
            document.getElementById('kom_edit_instansi').value = kom.instansi_asal || 'BPP';
            document.getElementById('kom_edit_nama_bpp').value = kom.nama_bpp || '';
        }
        
        let kelIds = [];
        if (kom.wilayah_kelurahan_ids) {
            if (Array.isArray(kom.wilayah_kelurahan_ids)) {
                kelIds = kom.wilayah_kelurahan_ids;
            } else if (typeof kom.wilayah_kelurahan_ids === 'string') {
                try {
                    kelIds = JSON.parse(kom.wilayah_kelurahan_ids);
                } catch(e) {}
            }
        }
        
        updateKomKelurahanOptionsEdit(kelIds);
        
        switchSection('edit-komunitas-section');
    }

    function toggleBppFieldsCreate() {
        const jenis = document.getElementById('kom_jenis_create').value;
        const bppFields = document.getElementById('bpp-fields-create');
        if (bppFields) bppFields.classList.toggle('hidden', jenis !== 'BPP');
    }
    
    function toggleBppFieldsEdit() {
        const jenis = document.getElementById('kom_edit_jenis').value;
        const bppFields = document.getElementById('bpp-fields-edit');
        if (bppFields) bppFields.classList.toggle('hidden', jenis !== 'BPP');
    }

    function updateKomKelurahanOptionsCreate() {
        const kecId = document.getElementById('kom_kecamatan_create').value;
        const select = document.getElementById('kom_kelurahan_create');
        if (!select) return;
        select.innerHTML = '';
        if (!kecId) return;
        const filtered = kelurahanOptions.filter(k => k.kecamatan_id == kecId);
        filtered.forEach(kel => {
            const option = document.createElement('option');
            option.value = kel.id; option.textContent = kel.nama_kelurahan;
            select.appendChild(option);
        });
    }

    function updateKomKelurahanOptionsEdit(selectedIds = []) {
        const kecId = document.getElementById('kom_edit_kecamatan').value;
        const select = document.getElementById('kom_edit_kelurahan');
        if (!select) return;
        select.innerHTML = '';
        if (!kecId) return;
        const filtered = kelurahanOptions.filter(k => k.kecamatan_id == kecId);
        filtered.forEach(kel => {
            const option = document.createElement('option');
            option.value = kel.id; option.textContent = kel.nama_kelurahan;
            if (selectedIds.includes(kel.id) || selectedIds.includes(String(kel.id))) option.selected = true;
            select.appendChild(option);
        });
    }

    function applyKomunitasFilters() {
        const rows = Array.from(document.querySelectorAll('[data-komunitas-row]'));
        if (!rows.length) return;

        const search = normalizeText(document.getElementById('komunitas-search')?.value);
        const jenis = document.getElementById('komunitas-jenis-filter')?.value || '';
        const kecamatan = document.getElementById('komunitas-kecamatan-filter')?.value || '';
        let visibleCount = 0;

        rows.forEach((row) => {
            const matchSearch = !search || normalizeText(row.dataset.search).includes(search);
            const matchJenis = !jenis || row.dataset.jenis === jenis;
            const matchKecamatan = !kecamatan || row.dataset.kecamatan === kecamatan;
            const isVisible = matchSearch && matchJenis && matchKecamatan;

            row.classList.toggle('hidden', !isVisible);
            if (isVisible) visibleCount += 1;
        });

        const emptyRow = document.getElementById('komunitas-empty-filter-row');
        if (emptyRow) emptyRow.classList.toggle('hidden', visibleCount > 0);
    }

    document.addEventListener('DOMContentLoaded', () => {
        ['komunitas-search', 'komunitas-jenis-filter', 'komunitas-kecamatan-filter'].forEach((id) => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', applyKomunitasFilters);
                el.addEventListener('change', applyKomunitasFilters);
            }
        });
        applyKomunitasFilters();

        const urlParams = new URLSearchParams(window.location.search);
        const section = urlParams.get('section');
        if (isMasterMode) {
            const defaultMasterSection = document.getElementById('master-index-section')
                ? 'master-index-section'
                : 'master-all-tables-section';
            switchSection(section === 'sql' ? 'sql-section' : defaultMasterSection);
        } else if (section === 'komunitas') {
            switchSection('komunitas-section');
        } else {
            // Default is index-section, already set by HTML structure usually, but ensure it.
            switchSection('index-section');
        }
    });
</script>

@endsection
