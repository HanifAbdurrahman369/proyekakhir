@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')

{{-- Page Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-lg font-bold text-primary-900">Panel Kendali Administrator</h1>
        <p class="text-xs text-gray-400 mt-0.5">Kelola akses akun dan pantau aktivitas sistem terintegrasi</p>
    </div>
    <button onclick="switchSection('create-section')" 
            class="flex items-center gap-2 bg-primary-500 hover:bg-primary-600 text-white text-xs font-semibold px-4 py-2 rounded-lg transition shadow-sm shadow-primary-200">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
        Tambah Pengguna Baru
    </button>
</div>

{{-- Sesi Notifikasi --}}
@if(session('success'))
    <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 text-xs px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-red-100 border border-red-200 text-red-800 text-xs px-4 py-3 rounded-lg mb-4">{{ session('error') }}</div>
@endif

{{-- ========================================== --}}
{{-- BAGIAN 1: TABEL DATA UTAMA                 --}}
{{-- ========================================== --}}
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
                            @case(1) <span class="px-2 py-0.5 bg-red-100 text-red-800 rounded text-[11px] font-bold">Admin</span> @break
                            @case(2) <span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-[11px] font-bold">Pejabat</span> @break
                            @case(3) <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded text-[11px] font-bold">Petugas</span> @break
                            @case(4) <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded text-[11px] font-bold">Petani</span> @break
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

{{-- ========================================== --}}
{{-- BAGIAN 2: FORM TAMBAH USER BARU            --}}
{{-- ========================================== --}}
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
                        <option value="1">Admin</option>
                        <option value="2">Pejabat</option>
                        <option value="3">Petugas</option>
                        <option value="4">Petani</option>
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

{{-- ========================================== --}}
{{-- BAGIAN 3: FORM EDIT USER (DINAIMS VIA JS)   --}}
{{-- ========================================== --}}
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
                        <option value="1">Admin</option>
                        <option value="2">Pejabat</option>
                        <option value="3">Petugas</option>
                        <option value="4">Petani</option>
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

{{-- Engine Javascript Ringan Manajemen Antarmuka --}}
<script>
    function switchSection(sectionId) {
        document.querySelectorAll('.admin-section').forEach(section => {
            section.classList.remove('block');
            section.classList.add('hidden');
        });
        document.getElementById(sectionId).classList.remove('hidden');
        document.getElementById(sectionId).classList.add('block');
    }

    function openEditSection(user) {
        document.getElementById('edit-form').action = '/admin/users/' + user.id;
        document.getElementById('edit-nama').value = user.nama_lengkap;
        document.getElementById('edit-role').value = user.role_id;
        document.getElementById('edit-email').value = user.email;
        document.getElementById('edit-hp').value = user.no_hp || '';
        document.getElementById('edit-alamat').value = user.alamat || '';
        switchSection('edit-section');
    }
</script>

@endsection