@extends('layouts.app')

@section('title', isset($tableNames) ? 'Manajemen Data Master' : 'Dashboard Admin')

@section('content')

@php
    // Deteksi mode aktif berdasarkan parameter data master dari MasterDataController
    $isMasterMode = isset($tableNames);
@endphp

{{-- ========================================== --}}
{{-- COMPONENT 1: DYNAMIC PAGE HEADER          --}}
{{-- ========================================== --}}
<div class="flex items-center justify-between mb-6">
    @if(!$isMasterMode)
        <div>
            <h1 class="text-lg font-bold text-primary-900">Panel Kendali Administrator</h1>
            <p class="text-xs text-gray-400 mt-0.5">Kelola akses akun dan pantau aktivitas sistem terintegrasi</p>
        </div>
        <button onclick="switchSection('create-section')" 
                class="flex items-center gap-2 bg-primary-500 hover:bg-primary-600 text-white text-xs font-semibold px-4 py-2 rounded-lg transition shadow-sm shadow-primary-200">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            Tambah Pengguna Baru
        </button>
    @else
        <div>
            <h1 class="text-lg font-bold text-primary-900">Manajemen Data Master (DBA)</h1>
            <p class="text-xs text-gray-400 mt-0.5">Kendali penuh struktur tabel, kolom, dan isi basis data secara real-time</p>
        </div>
        
        {{-- AKSI DINAMIS HEADER DATA MASTER --}}
        <div class="flex gap-2">
            @if(!$tableName)
                {{-- JIKA DI HALAMAN UTAMA (ALL TABLES): Sediakan tombol export masal seluruh database --}}
                <a href="/admin/master/export/excel" class="bg-green-600 hover:bg-green-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition shadow-sm flex items-center gap-1.5">
                    💾 Export Semua Tabel (Excel)
                </a>
                <a href="/admin/master/export/sql" class="bg-primary-500 hover:bg-primary-600 text-white text-xs font-semibold px-4 py-2 rounded-lg transition shadow-sm flex items-center gap-1.5">
                    📄 Export Semua Tabel (SQL)
                </a>
                <button onclick="switchSection('sql-section')" class="bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold px-4 py-2 rounded-lg transition shadow-sm">
                    &#9881; Eksekusi SQL / Import
                </button>
            @else
                {{-- JIKA SEDANG MASUK DI SALAH SATU TABEL: Tampilkan tombol aksi normal bawaan tabel tersebut --}}
                <button onclick="switchSection('sql-section')" class="bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold px-4 py-2 rounded-lg transition shadow-sm">
                    &#9881; Eksekusi SQL / Import
                </button>
                <a href="/admin/master/export/sql" class="bg-primary-500 hover:bg-primary-600 text-white text-xs font-semibold px-4 py-2 rounded-lg transition shadow-sm">
                    &#8681; Export Full Database (SQL)
                </a>
            @endif
        </div>
    @endif
</div>

{{-- Sesi Global Notifikasi Berhasil / Gagal --}}
@if(session('success'))
    <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 text-xs px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-red-100 border border-red-200 text-red-800 text-xs px-4 py-3 rounded-lg mb-4">{{ session('error') }}</div>
@endif


{{-- ====================================================================== --}}
{{-- MODUL A: INTEGRASI FITUR MANAJEMEN PENGGUNA (USER MANAGEMENT)          --}}
{{-- ====================================================================== --}}
@if(!$isMasterMode)
    {{-- A1: TABEL DATA UTAMA PENGGUNA --}}
    <div id="index-section" class="admin-section block">
        <div class="bg-white rounded-xl border border-primary-100 overflow-hidden shadow-sm">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-primary-50 text-primary-900 text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-4">Nama Lengkap</th>
                        <th class="px-6 py-4">Email</th>
                        <th class="px-6 py-4">Hak Akses (Role ID)</th>
                        <th class="px-6 py-4">No. Handphone</th>
                        <th class="px-6 py-4 text-center">Aksi Manajemen</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 divide-y divide-primary-50">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-medium text-primary-900">{{ $user['nama_lengkap'] }}</td>
                        <td class="px-6 py-4">{{ $user['email'] }}</td>
                        <td class="px-6 py-4">
                            @switch($user['role_id'])
                                @case(1) <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded text-[11px] font-bold">Petani</span> @break
                                @case(2) <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded text-[11px] font-bold">Petugas</span> @break
                                @case(3) <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-[11px] font-bold">Pejabat</span> @break
                                @case(4) <span class="px-2 py-0.5 bg-red-100 text-red-800 rounded text-[11px] font-bold">Admin</span> @break
                                @default <span class="px-2 py-0.5 bg-gray-100 text-gray-800 rounded text-[11px] font-bold">Role {{ $user['role_id'] }}</span>
                            @endswitch
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
                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400 text-xs italic">Tidak ada data pengguna terdeteksi dari backend user_service.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- A2: FORM TAMBAH USER BARU --}}
    <div id="create-section" class="admin-section hidden">
        <div class="bg-white rounded-xl border border-primary-100 p-6 shadow-sm max-w-2xl">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100">
                <h3 class="font-bold text-primary-900 text-sm">Formulir Registrasi Pengguna Baru</h3>
                <button onclick="switchSection('index-section')" class="text-xs text-gray-400 hover:text-gray-600">&times; Batalkan</button>
            </div>
            <form action="/admin/users" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Nama Lengkap *</label>
                        <input type="text" name="nama_lengkap" required class="w-full border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Peran Akses (Role) *</label>
                        <select name="role_id" required class="w-full border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">
                            <option value="4">Admin</option>
                            <option value="3">Pejabat</option>
                            <option value="2">Petugas</option>
                            <option value="1">Petani</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Alamat Email Aktif *</label>
                        <input type="email" name="email" required class="w-full border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Kata Sandi Sistem *</label>
                        <input type="password" name="password" required minlength="6" class="w-full border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Nomor Handphone</label>
                        <input type="text" name="no_hp" class="w-full border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Alamat Rumah</label>
                        <textarea name="alamat" rows="2" class="w-full border-gray-300 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500"></textarea>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100 flex justify-end gap-2">
                    <button type="button" onclick="switchSection('index-section')" class="px-4 py-2 border border-gray-200 rounded-lg text-xs font-semibold text-gray-600 hover:bg-gray-50">Kembali</button>
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold px-6 py-2 rounded-lg text-xs transition">Simpan Akun</button>
                </div>
            </form>
        </div>
    </div>

    {{-- A3: FORM EDIT PENGGUNA --}}
    <div id="edit-section" class="admin-section hidden">
        <div class="bg-white rounded-xl border border-primary-100 p-6 shadow-sm max-w-2xl">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100">
                <h3 class="font-bold text-primary-900 text-sm">Perbarui Data Pengguna</h3>
                <button onclick="switchSection('index-section')" class="text-xs text-gray-400 hover:text-gray-600">&times; Batalkan</button>
            </div>
            <form id="edit-form" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Nama Lengkap *</label>
                        <input type="text" id="edit-nama" name="nama_lengkap" required class="w-full border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Peran Akses (Role) *</label>
                        <select id="edit-role" name="role_id" required class="w-full border-gray-300 rounded-lg text-sm">
                            <option value="4">Admin</option>
                            <option value="3">Pejabat</option>
                            <option value="2">Petugas</option>
                            <option value="1">Petani</option>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Alamat Email *</label>
                        <input type="email" id="edit-email" name="email" required class="w-full border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Kata Sandi Baru <span class="text-gray-400 font-normal">(Kosongkan jika tidak diganti)</span></label>
                        <input type="password" name="password" minlength="6" class="w-full border-gray-300 rounded-lg text-sm placeholder-gray-300" placeholder="••••••••">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Nomor Handphone</label>
                        <input type="text" id="edit-hp" name="no_hp" class="w-full border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Alamat Rumah</label>
                        <textarea id="edit-alamat" name="alamat" rows="2" class="w-full border-gray-300 rounded-lg text-sm"></textarea>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100 flex justify-end gap-2">
                    <button type="button" onclick="switchSection('index-section')" class="px-4 py-2 border border-gray-200 rounded-lg text-xs font-semibold text-gray-600 hover:bg-gray-50">Kembali</button>
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold px-6 py-2 rounded-lg text-xs transition">Simpan Perubahan</button>
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
    <div class="bg-white rounded-xl border border-primary-100 p-4 mb-4 shadow-sm flex items-center justify-between flex-wrap gap-4">
        <form action="/admin/master" method="GET" class="flex items-center gap-3">
            <label class="text-xs font-bold text-gray-700">Pilih Tabel Database:</label>
            <select name="table" onchange="this.form.submit()" class="border-gray-300 rounded-lg text-sm px-4 py-1.5 focus:ring-primary-500 min-w-[220px]">
                <option value="">-- Tampilkan Semua Tabel --</option>
                @foreach($tableNames as $name)
                    <option value="{{ $name }}" {{ $tableName == $name ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </form>

        @if($tableName)
        <div class="flex gap-2">
            <a href="/admin/master/export/excel/{{ $tableName }}" class="px-3 py-1.5 bg-green-100 text-green-700 rounded-lg text-xs font-semibold hover:bg-green-200 transition">💾 Export Excel</a>
            <a href="/admin/master/export/sql/{{ $tableName }}" class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-xs font-semibold hover:bg-gray-200 transition">📄 Export SQL Tabel</a>
            {{-- REVISI: Tombol Tambah Baris Data Telah Dihapus --}}
        </div>
        @endif
    </div>

    {{-- KONDISIONAL: JIKA TABEL DIPILIH --}}
    @if($tableName)
        <div id="master-index-section" class="admin-section block">
            <div class="bg-white rounded-xl border border-primary-100 overflow-x-auto shadow-sm">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-primary-50 text-primary-900 text-xs uppercase font-semibold">
                        <tr>
                            @foreach($columns as $col)
                                <th class="px-6 py-4">{{ $col }}</th>
                            @endforeach
                            <th class="px-6 py-4 text-center sticky right-0 bg-primary-50">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 divide-y divide-primary-50">
                        @forelse($rows as $row)
                        @php $rowArray = (array)$row; @endphp
                        <tr class="hover:bg-gray-50 transition">
                            @foreach($columns as $col)
                                <td class="px-6 py-3 max-w-[220px] truncate" title="{{ $rowArray[$col] ?? 'NULL' }}">
                                    {{ $rowArray[$col] ?? 'NULL' }}
                                </td>
                            @endforeach
                            <td class="px-6 py-3 text-center flex justify-center gap-2 sticky right-0 bg-white shadow-[-5px_0_10px_rgba(0,0,0,0.02)]">
                                <button onclick="openMasterEditSection({{ json_encode($rowArray) }})" class="text-blue-600 hover:bg-blue-50 px-3 py-1 rounded-md text-xs font-semibold border border-blue-200">Edit</button>
                                <form action="/admin/master/{{ $tableName }}/{{ $rowArray['id'] ?? '' }}" method="POST" onsubmit="return confirm('Apakah anda yakin ingin menghapus data master ini?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:bg-red-50 px-3 py-1 rounded-md text-xs font-semibold border border-red-200" {{ !isset($rowArray['id']) ? 'disabled' : '' }}>Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="{{ count($columns) + 1 }}" class="px-6 py-8 text-center text-gray-400 text-xs italic">Tabel basis data ini belum memiliki isi data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        {{-- TAMPILAN DEFAULT - GRID ALL TABLES --}}
        <div id="master-all-tables-section" class="admin-section block">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($allTablesWithColumns as $tName => $tCols)
                    <div class="bg-white rounded-xl border border-primary-100 p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-3">
                                <span class="p-2 bg-primary-50 text-primary-700 rounded-lg">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                                </span>
                                <h3 class="font-bold text-gray-800 text-sm tracking-wide uppercase">{{ $tName }}</h3>
                            </div>
                            <p class="text-[11px] font-bold text-gray-400 uppercase mb-1">Daftar Kolom Skema:</p>
                            <div class="flex flex-wrap gap-1.5 mb-6 max-h-[120px] overflow-y-auto pr-1">
                                @foreach($tCols as $cName)
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-[10px] font-mono border border-gray-200">{{ $cName }}</span>
                                @endforeach
                            </div>
                        </div>
                        <div class="pt-3 border-t border-gray-100 flex items-center justify-between gap-2">
                            <a href="/admin/master?table={{ $tName }}" class="text-xs font-semibold text-primary-600 hover:underline">Lihat Data &rarr;</a>
                            <div class="flex gap-1">
                                <a href="/admin/master/export/excel/{{ $tName }}" class="p-1.5 bg-green-50 text-green-600 border border-green-200 rounded-md hover:bg-green-100 transition text-[11px] font-medium" title="Export Excel">Excel</a>
                                <a href="/admin/master/export/sql/{{ $tName }}" class="p-1.5 bg-gray-50 text-gray-600 border border-gray-200 rounded-md hover:bg-gray-100 transition text-[11px] font-medium" title="Export SQL">SQL</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- REVISI: FORM TAMBAH BARIS DATA (master-create-section) TELAH DIHAPUS TOTAL SEHINGGA TIDAK BISA DIAKSES --}}

    {{-- B2: FORM EDIT DATA MASTER (DINAMIS) --}}
    @if($tableName)
    <div id="master-edit-section" class="admin-section hidden">
        <div class="bg-white rounded-xl border border-primary-100 p-6 shadow-sm max-w-3xl">
            <h3 class="font-bold text-primary-900 text-sm mb-4 border-b pb-2">Modifikasi Data Tabel: <span class="text-blue-600">{{ $tableName }}</span></h3>
            <form id="master-edit-form" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @csrf @method('PUT')
                @foreach($columns as $col)
                    @if(!in_array($col, ['created_at', 'updated_at']))
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">{{ $col }} {!! $col == 'id' ? '<span class="text-red-500 text-[10px] font-normal">(Readonly)</span>' : '' !!}</label>
                        <input type="text" id="master_edit_{{ $col }}" name="{{ $col }}" {!! $col == 'id' ? 'readonly class="w-full border-gray-200 bg-gray-100 rounded-lg text-sm p-2"' : 'class="w-full border-gray-300 rounded-lg text-sm p-2"' !!}>
                    </div>
                    @endif
                @endforeach
                <div class="col-span-1 md:col-span-2 pt-4 flex justify-end gap-2 border-t mt-2">
                    <button type="button" onclick="window.location.href='/admin/master?table={{$tableName}}'" class="px-4 py-2 border rounded-lg text-xs font-semibold text-gray-600 hover:bg-gray-50">Batal</button>
                    <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white font-semibold px-6 py-2 rounded-lg text-xs">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- B3: EKSEKUSI RAW SQL COMMAND TERMINAL --}}
    <div id="sql-section" class="admin-section hidden">
        <div class="bg-white rounded-xl border border-primary-100 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4 border-b pb-2">
                <h3 class="font-bold text-primary-900 text-sm">Konsol Eksekusi SQL Mentah (Manipulasi Kolom & Tabel)</h3>
                <button onclick="window.location.href='/admin/master'" class="text-xs text-gray-400 hover:text-gray-600">&times; Tutup</button>
            </div>
            <form action="/admin/master/execute-sql" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-600 mb-2">Masukkan Perintah SQL Query (Mendukung: CREATE TABLE, ALTER TABLE, DROP, atau INSERT):</label>
                    <textarea name="sql" rows="8" required class="w-full border-gray-300 rounded-lg text-sm p-3 font-mono text-gray-800 bg-gray-50 focus:ring-amber-500" placeholder="ALTER TABLE nama_tabel ADD nama_kolom VARCHAR(255) NULL;"></textarea>
                </div>
                <div class="flex justify-end">
                    <button type="submit" onclick="return confirm('Peringatan Keamanan Ekstrim: Perintah SQL mentah ini akan merubah struktur database fisik secara langsung di PA2. Lanjutkan?')" class="bg-amber-600 hover:bg-amber-700 text-white font-semibold px-6 py-2 rounded-lg text-xs transition shadow-sm">⚡ Eksekusi Query SQL</button>
                </div>
            </form>
        </div>
    </div>
@endif


{{-- ====================================================================== --}}
{{-- COMPONENT 3: MOTOR ENGINE ROUTING INTERFASE GRAPHIC (JAVASCRIPT)       --}}
{{-- ====================================================================== --}}
<script>
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
            switchSection('edit-section');
        }
    }

    /**
     * Memetakan data dinamis basis data master ke dalam Input Form Edit secara otomatis
     */
    function openMasterEditSection(rowData) {
        const tableName = '{{ $tableName ?? "" }}';
        if (!tableName || !rowData.id) {
            alert('Kesalahan Sistem: Baris data master wajib memiliki primary key [id] untuk dapat diubah.');
            return;
        }
        
        const form = document.getElementById('master-edit-form');
        if (form) {
            form.action = `/admin/master/${tableName}/${rowData.id}`;
            
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
</script>

@endsection