@extends('layouts.app')

@php
    $user = $user ?? [];
    $roleId = (int) ($user['role_id'] ?? session('role_id'));
    $desaRows = $user['wilayah_kelurahan_nama'] ?? [];
    $desaRows = is_array($desaRows) ? $desaRows : array_filter([(string) $desaRows]);
    $desa = implode(', ', $desaRows);
    $instansi = ($user['instansi_asal'] ?? null) === 'BPP'
        ? ($user['nama_bpp'] ?? 'BPP')
        : 'DINAS PERTANIAN TANAMAN PANGAN DAN HORTIKULTURA';
@endphp

@section('title', 'Edit Profil')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-6 space-y-6">
    <header>
        <p class="text-[11px] font-bold uppercase text-[#047857] tracking-[0.2em]">Profil Pengguna</p>
        <h1 class="mt-1 text-2xl font-extrabold text-[#022c22]">Edit Profil</h1>
        <p class="mt-1 text-sm text-slate-500">Perbarui data diri akun Anda. Password tetap diubah melalui fitur lupa password.</p>
    </header>

    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="rounded-[22px] border border-[#d1fae5] bg-white p-5 sm:p-6 shadow-[0_14px_38px_rgba(4,120,87,.06)]">
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" required
                           value="{{ old('nama_lengkap', $user['nama_lengkap'] ?? '') }}"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-[#047857] focus:ring-[#047857]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Email</label>
                    <input type="email" name="email" required
                           value="{{ old('email', $user['email'] ?? '') }}"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-[#047857] focus:ring-[#047857]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nomor HP</label>
                    <input type="text" name="no_hp"
                           value="{{ old('no_hp', $user['no_hp'] ?? '') }}"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-[#047857] focus:ring-[#047857]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat</label>
                    <textarea name="alamat" rows="3"
                              class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-[#047857] focus:ring-[#047857]">{{ old('alamat', $user['alamat'] ?? '') }}</textarea>
                </div>
                
                @if($roleId === 2)
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Asal Petugas (Instansi)</label>
                        <input type="text" disabled value="{{ $instansi }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm bg-slate-50 text-slate-500">
                    </div>
                    <div class="col-span-1 md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-5 pt-4 border-t border-slate-100">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Kecamatan Wilayah Kerja</label>
                            <select name="wilayah_kecamatan_id" id="kecamatan_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-[#047857] focus:ring-[#047857]">
                                <option value="">Pilih Kecamatan</option>
                                @foreach($kecamatan ?? [] as $item)
                                    @php
                                        $oldKec = old('wilayah_kecamatan_id', $user['wilayah_kecamatan_id'] ?? '');
                                        $oldKec = is_array($oldKec) ? ($oldKec[0] ?? '') : $oldKec;
                                    @endphp
                                    <option value="{{ $item['id'] }}" @selected((string)$oldKec === (string)$item['id'])>
                                        {{ $item['nama_kecamatan'] ?? $item['nama'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Kelurahan Wilayah Kerja</label>
                            <select name="wilayah_kelurahan_id" id="kelurahan_id" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-[#047857] focus:ring-[#047857]">
                                <option value="">Pilih Kelurahan</option>
                                @foreach($kelurahan ?? [] as $item)
                                    @php
                                        $rawKelIds = $user['wilayah_kelurahan_ids'] ?? '';
                                        $kelIds = is_string($rawKelIds) ? json_decode($rawKelIds, true) : $rawKelIds;
                                        $selectedKelurahanId = (is_array($kelIds) && count($kelIds) > 0) ? $kelIds[0] : '';
                                        
                                        $oldKel = old('wilayah_kelurahan_id', $selectedKelurahanId);
                                        $oldKel = is_array($oldKel) ? ($oldKel[0] ?? '') : $oldKel;
                                    @endphp
                                    <option value="{{ $item['id'] }}" data-kecamatan-id="{{ $item['kecamatan_id'] ?? '' }}" @selected((string)$oldKel === (string)$item['id'])>
                                        {{ $item['nama_kelurahan'] ?? $item['nama'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-100 pt-5">
                <a href="{{ $roleId === 2 ? '/dashboard-petugas' : '/' }}" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50">Kembali</a>
                <button type="submit" class="rounded-xl bg-[#047857] px-5 py-2 text-xs font-bold text-white hover:bg-[#065f46]">Simpan Profil</button>
            </div>
        </form>
    </section>
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
        
        // Simpan nilai awal kelurahan
        const initialKelurahanId = kelurahanSelect.value;

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

        // Panggil filter saat halaman dimuat
        filterKelurahan();
    });
</script>
@endpush
