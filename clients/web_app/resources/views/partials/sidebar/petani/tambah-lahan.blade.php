@extends('layouts.app')

@php
    $isEdit = isset($editLahan) && is_array($editLahan);
    $field = fn($key, $default = '') => old($key, data_get($editLahan ?? [], $key, $default));
@endphp

@section('title', $isEdit ? 'Perbaiki Pengajuan Lahan' : 'Tambah Lahan')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-6">
    <!-- Alur Pengajuan (Animated Flow) -->
    <div class="mb-8">
        <h2 class="text-lg font-bold text-slate-800 text-center mb-6">Panduan 4 Langkah Mudah Mendaftarkan Sawah Anda</h2>
        
        <div class="relative flex flex-col md:flex-row justify-between items-center md:items-start gap-6 md:gap-4">
            <!-- Line connector for desktop -->
            <div class="hidden md:block absolute top-6 left-[10%] right-[10%] h-1 bg-slate-200 -z-10 rounded-full overflow-hidden">
                <div class="h-full bg-primary-500 w-full animate-[shimmer_3s_infinite] origin-left" style="background: linear-gradient(90deg, transparent, rgba(34,197,94,0.5), transparent); background-size: 200% 100%;"></div>
            </div>

            <!-- Step 1 -->
            <div class="flex flex-col items-center text-center w-full md:w-1/4 group">
                <div class="w-12 h-12 rounded-2xl bg-white border-2 border-primary-500 text-primary-600 shadow-lg flex items-center justify-center font-black text-xl mb-3 relative group-hover:-translate-y-1 transition-transform duration-300">
                    1
                    <div class="absolute inset-0 bg-primary-100 rounded-2xl scale-0 group-hover:scale-100 transition-transform duration-300 -z-10"></div>
                </div>
                <h3 class="font-bold text-slate-800 text-sm mb-1">Isi Formulir</h3>
                <p class="text-xs text-slate-500">Isi data nama lahan, luas (Hektar), dan alamat lengkap di bawah ini.</p>
            </div>

            <!-- Step 2 -->
            <div class="flex flex-col items-center text-center w-full md:w-1/4 group">
                <div class="w-12 h-12 rounded-2xl bg-white border-2 border-primary-500 text-primary-600 shadow-lg flex items-center justify-center font-black text-xl mb-3 relative group-hover:-translate-y-1 transition-transform duration-300">
                    2
                    <div class="absolute inset-0 bg-primary-100 rounded-2xl scale-0 group-hover:scale-100 transition-transform duration-300 -z-10"></div>
                </div>
                <h3 class="font-bold text-slate-800 text-sm mb-1">Persetujuan</h3>
                <p class="text-xs text-slate-500">Tunggu Petugas menyetujui pengajuan Anda. Cek berkala di menu Lahan Saya.</p>
            </div>

            <!-- Step 3 -->
            <div class="flex flex-col items-center text-center w-full md:w-1/4 group">
                <div class="w-12 h-12 rounded-2xl bg-white border-2 border-primary-500 text-primary-600 shadow-lg flex items-center justify-center font-black text-xl mb-3 relative group-hover:-translate-y-1 transition-transform duration-300">
                    3
                    <div class="absolute inset-0 bg-primary-100 rounded-2xl scale-0 group-hover:scale-100 transition-transform duration-300 -z-10"></div>
                </div>
                <h3 class="font-bold text-slate-800 text-sm mb-1">Hubungi Petugas</h3>
                <p class="text-xs text-slate-500">Jika disetujui, hubungi atau temui Petugas secara langsung untuk pemetaan lahan.</p>
            </div>

            <!-- Step 4 -->
            <div class="flex flex-col items-center text-center w-full md:w-1/4 group">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/30 flex items-center justify-center mb-3 relative group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h3 class="font-bold text-emerald-600 text-sm mb-1">Terverifikasi</h3>
                <p class="text-xs text-slate-500">Lahan terpetakan (Terverifikasi) dan siap digunakan untuk lapor panen.</p>
            </div>
        </div>
    </div>
    
    <style>
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>

    <!-- Form Card -->
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-[#e7efd8] px-5 sm:px-6 py-4">
            <h2 class="text-sm font-bold text-[#14280b]">
                {{ $isEdit ? 'Perbaiki Pengajuan Lahan' : 'Tambah Lahan Baru' }}
            </h2>
            <p class="mt-1 text-[11px] text-slate-500">
                {{ $isEdit ? 'Perbarui data sesuai catatan petugas lalu ajukan ulang.' : 'Lengkapi data lahan untuk diajukan kepada petugas.' }}
            </p>
        </div>

        <div class="p-5 sm:p-6">
            @if(session('success'))
                <div class="mb-5 p-4 rounded-xl bg-green-100 border border-green-300 text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 p-4 rounded-xl bg-red-100 border border-red-300 text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if($isEdit && !empty($editLahan['alasan_penolakan']))
                <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-red-600">Alasan Penolakan Petugas</p>
                    <p class="text-sm text-red-700 mt-2 leading-relaxed">{{ $editLahan['alasan_penolakan'] }}</p>
                </div>
            @endif

            <form method="POST" action="{{ $isEdit ? route('lahan.resubmit', $editLahan['id']) : route('lahan.store') }}">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div>
                        <label class="block mb-2 font-medium text-gray-700">Nama Lahan</label>
                        <input type="text"
                            name="nama_lahan"
                            value="{{ $field('nama_lahan') }}"
                            placeholder="Masukkan nama lahan"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-800 shadow-sm transition-all hover:border-primary-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                            required>
                    </div>

                    <div>
                        <label class="block mb-2 font-medium text-gray-700">Kecamatan</label>
                        <select name="kecamatan_id" id="kecamatan_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-800 shadow-sm transition-all hover:border-primary-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                required>
                            <option value="">Pilih Kecamatan</option>
                            @foreach($kecamatan as $item)
                                <option value="{{ $item['id'] }}" @selected((string)$field('kecamatan_id') === (string)$item['id'])>
                                    {{ $item['nama_kecamatan'] ?? $item['nama'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-2 font-medium text-gray-700">Kelurahan</label>
                        <select name="kelurahan_id" id="kelurahan_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-800 shadow-sm transition-all hover:border-primary-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                required>
                            <option value="">Pilih Kelurahan</option>
                            @foreach($kelurahan as $item)
                                <option value="{{ $item['id'] }}" data-kecamatan-id="{{ $item['kecamatan_id'] ?? '' }}" @selected((string)$field('kelurahan_id') === (string)$item['id'])>
                                    {{ $item['nama_kelurahan'] ?? $item['nama'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-2 font-medium text-gray-700">Tipe Lahan</label>
                        <select name="tipe_lahan_id"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-800 shadow-sm transition-all hover:border-primary-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                                required>
                            <option value="">Pilih Tipe Lahan</option>
                            @foreach($tipeLahan as $item)
                                <option value="{{ $item['id'] }}" @selected((string)$field('tipe_lahan_id') === (string)$item['id'])>
                                    {{ $item['nama_tipe'] ?? $item['nama'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block mb-2 font-medium text-gray-700">Luas Lahan (Hektar)</label>
                        <input type="number"
                            step="0.01"
                            min="0.01"
                            name="luas_lahan_hektar"
                            value="{{ $field('luas_lahan_hektar') }}"
                            placeholder="Contoh: 1.5"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-800 shadow-sm transition-all hover:border-primary-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                            required>
                    </div>



                    <div>
                        <label class="block mb-2 font-medium text-gray-700">Status Pengajuan</label>
                        <input type="text"
                            value="{{ $isEdit ? 'Akan Diajukan Ulang' : 'Menunggu Persetujuan' }}"
                            readonly
                            class="w-full bg-yellow-50 border border-yellow-300 rounded-xl px-4 py-3 text-yellow-700">
                    </div>
                </div>

                <div class="mt-5">
                    <label class="block mb-2 font-medium text-gray-700">Alamat Lengkap</label>
                    <textarea name="alamat_detail"
                            rows="5"
                            placeholder="Masukkan alamat lengkap lahan"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-800 shadow-sm transition-all hover:border-primary-500 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
                            required>{{ $field('alamat_detail') }}</textarea>
                </div>

                <div class="mt-6 p-4 sm:p-5 bg-blue-50 border border-blue-100 rounded-2xl">
                    <h4 class="font-semibold text-blue-800 mb-3">Informasi Pengajuan Lahan</h4>
                    <ul class="text-sm text-blue-700 space-y-2">
                        <li>- Setelah disetujui, Anda **wajib menemui petugas lapangan** untuk koordinasi pemetaan area sawah Anda secara langsung.</li>
                        <li>- Data lahan harus terpetakan sebelum digunakan untuk pelaporan hasil panen.</li>
                    </ul>
                </div>

                <div class="flex flex-col sm:flex-row justify-end gap-3 mt-6">
                    <a href="{{ url('/dashboard-petani') }}"
                       class="w-full sm:w-auto text-center px-5 py-3 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50 transition">
                        Batal
                    </a>

                    <button type="submit"
                            class="w-full sm:w-auto px-6 py-3 rounded-xl bg-primary-700 text-white hover:bg-primary-800 transition">
                        {{ $isEdit ? 'Ajukan Ulang' : 'Simpan Pengajuan' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const kecamatanSelect = document.getElementById('kecamatan_id');
        const kelurahanSelect = document.getElementById('kelurahan_id');
        
        if (!kecamatanSelect || !kelurahanSelect) return;

        // Simpan semua opsi kelurahan (clone) untuk filter
        const allKelurahanOptions = Array.from(kelurahanSelect.options).filter(opt => opt.value !== "");
        
        // Simpan nilai awal kelurahan (saat edit atau validasi form error)
        const initialKelurahanId = "{{ $field('kelurahan_id') }}";

        function filterKelurahan() {
            const selectedKecamatanId = kecamatanSelect.value;
            
            // Kosongkan daftar kelurahan dan tambahkan opsi default
            kelurahanSelect.innerHTML = '<option value="">Pilih Kelurahan</option>';
            
            if (selectedKecamatanId) {
                // Saring kelurahan yang sesuai dengan kecamatan
                const filteredOptions = allKelurahanOptions.filter(option => {
                    return option.getAttribute('data-kecamatan-id') === selectedKecamatanId;
                });
                
                // Masukkan opsi yang difilter kembali ke select
                filteredOptions.forEach(option => {
                    kelurahanSelect.appendChild(option.cloneNode(true));
                });

                // Pilih kembali kelurahan jika ada initial value dan masih valid dalam opsi terfilter
                if (initialKelurahanId) {
                    const isExist = Array.from(kelurahanSelect.options).some(opt => opt.value === initialKelurahanId);
                    if (isExist) {
                        kelurahanSelect.value = initialKelurahanId;
                    }
                }
            }
        }

        // Panggil filter saat kecamatan diubah
        kecamatanSelect.addEventListener('change', filterKelurahan);

        // Panggil filter saat halaman dimuat (untuk handle mode edit)
        filterKelurahan();
    });
</script>
@endpush
