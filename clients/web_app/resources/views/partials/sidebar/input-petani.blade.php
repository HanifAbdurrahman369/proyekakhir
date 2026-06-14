@extends('layouts.app')

@php
    $isEdit = isset($editPanen) && is_array($editPanen);
    $field = fn($key, $default = '') => old($key, data_get($editPanen ?? [], $key, $default));
    $pupuk = $pupuk ?? [];
    $siklusTanam = $siklusTanam ?? [];
@endphp

@section('title', $isEdit ? 'Perbaiki Laporan Panen' : 'Input Aktivitas Tanam')

@section('content')

<div class="max-w-[1400px] mx-auto px-4 sm:px-6 py-6">

    {{-- ALERT SUCCESS --}}
    @if(session('success'))
        <div class="mb-5 p-3.5 rounded-2xl bg-green-100 text-green-700 border border-green-200 text-xs font-semibold">
            {{ session('success') }}
        </div>
    @endif

    {{-- ALERT ERROR --}}
    @if(session('error'))
        <div class="mb-5 p-3.5 rounded-2xl bg-red-100 text-red-700 border border-red-200 text-xs font-semibold">
            {{ session('error') }}
        </div>
    @endif

    {{-- VALIDATION ERROR --}}
    @if($errors->any())
        <div class="mb-5 p-3.5 rounded-2xl bg-red-100 text-red-700 border border-red-200 text-xs">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($isEdit && !empty($editPanen['catatan_verifikasi']))
        <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-3.5 text-xs">
            <p class="text-[10px] font-bold uppercase tracking-wide text-red-600">Catatan Penolakan Petugas</p>
            <p class="text-red-700 mt-1.5 leading-relaxed">{{ $editPanen['catatan_verifikasi'] }}</p>
        </div>
    @endif

    @if($isEdit)
        {{-- EDIT MODE: CENTERED SINGLE COLUMN --}}
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-[#e7efd8]">
                {{-- HEADER --}}
                <div class="bg-primary-700 text-white px-5 py-4">
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-primary-100 mb-1">
                        Laporan Ditolak
                    </p>
                    <h1 class="text-base font-bold sm:text-lg">
                        Perbaiki Laporan Hasil Panen
                    </h1>
                    <p class="text-xs text-primary-100 mt-0.5">
                        Perbarui data sesuai catatan verifikasi lalu ajukan ulang.
                    </p>
                </div>

                {{-- FORM --}}
                <div class="p-5">
                    <form action="{{ route('panen.update', $editPanen['id']) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- PILIH LAHAN --}}
                            <div>
                                <label class="block mb-1 text-xs font-semibold text-gray-700">
                                    Pilih Lahan <span class="text-red-500">*</span>
                                </label>
                                <select name="lahan_id"
                                        class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500"
                                        required>
                                    <option value="">-- Pilih Lahan Sawah --</option>
                                    @forelse($lahan as $item)
                                        <option value="{{ $item['id'] }}" @selected((string)$field('lahan_id') === (string)$item['id'])>
                                            {{ $item['nama_lahan'] }} | {{ $item['kecamatan'] ?? '-' }} | {{ $item['luas_lahan_hektar'] ?? 0 }} Ha
                                        </option>
                                    @empty
                                        <option disabled>Belum memiliki lahan terdaftar</option>
                                    @endforelse
                                </select>
                                <p class="mt-1 text-[10px] text-gray-500">
                                    Hanya menampilkan lahan milik akun yang sedang login.
                                </p>
                            </div>

                            {{-- JENIS BIBIT --}}
                            <div>
                                <label class="block mb-1 text-xs font-semibold text-gray-700">
                                    Jenis Bibit <span class="text-red-500">*</span>
                                </label>
                                <select name="bibit_id"
                                        class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500"
                                        required>
                                    <option value="">-- Pilih Bibit --</option>
                                    @foreach($bibit as $item)
                                        <option value="{{ $item['id'] }}" @selected((string)$field('bibit_id') === (string)$item['id'])>
                                            {{ $item['nama_bibit'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- TANGGAL TANAM --}}
                            <div>
                                <label class="block mb-1 text-xs font-semibold text-gray-700">
                                    Tanggal Tanam <span class="text-red-500">*</span>
                                </label>
                                <input type="date"
                                       name="tanggal_tanam"
                                       value="{{ $field('tanggal_tanam') }}"
                                       class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500"
                                       required>
                            </div>

                            {{-- ESTIMASI PANEN --}}
                            <div>
                                <label class="block mb-1 text-xs font-semibold text-gray-700">
                                    Estimasi Panen (Hari) <span class="text-red-500">*</span>
                                </label>
                                <input type="number"
                                       name="estimasi_panen"
                                       value="{{ $field('estimasi_panen') }}"
                                       placeholder="Contoh: 90"
                                       class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500"
                                       required>
                            </div>

                            {{-- TANGGAL PANEN --}}
                            <div>
                                <label class="block mb-1 text-xs font-semibold text-gray-700">
                                    Tanggal Panen
                                </label>
                                <input type="date"
                                       name="tanggal_panen"
                                       value="{{ $field('tanggal_panen') }}"
                                       class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500">
                            </div>

                            {{-- HASIL PANEN --}}
                            <div>
                                <label class="block mb-1 text-xs font-semibold text-gray-700">
                                    Hasil Panen (Ton)
                                </label>
                                <input type="number"
                                       step="0.01"
                                       name="hasil_panen"
                                       value="{{ $field('hasil_panen') }}"
                                       placeholder="Contoh: 4.5"
                                       class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500">
                            </div>
                        </div>

                        {{-- STATUS SISTEM --}}
                        <div class="mt-4 p-4 bg-blue-50 border border-blue-100 rounded-2xl">
                            <h4 class="font-semibold text-xs text-blue-800 mb-2">
                                Informasi Status Sistem
                            </h4>
                            <div class="space-y-1.5 text-xs text-blue-700">
                                <p>
                                    <strong>Status Aktivitas:</strong> AKTIF
                                </p>
                                <p>
                                    <strong>Status Verifikasi:</strong> AKAN DIAJUKAN ULANG
                                </p>
                                <p class="text-[10px] text-blue-600 pt-1 leading-relaxed">
                                    Data akan diverifikasi oleh petugas sebelum hasil panen masuk ke total produksi lahan.
                                </p>
                            </div>
                        </div>

                        {{-- BUTTON --}}
                        <div class="flex flex-col sm:flex-row justify-end gap-2.5 mt-5">
                            <a href="{{ route('riwayat.panen') }}"
                               class="w-full sm:w-auto text-center px-8 py-2 rounded-xl border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                                Batal
                            </a>
                            <button type="submit"
                                    class="w-full sm:w-auto px-5 py-2 rounded-xl bg-primary-700 text-xs font-semibold text-white hover:bg-primary-800 transition">
                                Ajukan Ulang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @else
        {{-- CREATE MODE: SIDE-BY-SIDE GRID --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            {{-- LEFT COLUMN: FORM INPUT AKTIVITAS TANAM --}}
            <div class="lg:col-span-7">
                <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-[#e7efd8]">
                    {{-- HEADER --}}
                    <div class="bg-primary-700 text-white px-5 py-4">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-primary-100 mb-1">
                            Laporan Baru
                        </p>
                        <h1 class="text-base font-bold sm:text-lg">
                            Form Input Aktivitas Tanam
                        </h1>
                        <p class="text-xs text-primary-100 mt-0.5">
                            Silakan isi data aktivitas tanam dengan benar. Status verifikasi akan diproses oleh petugas.
                        </p>
                    </div>

                    {{-- FORM BODY --}}
                    <div class="p-5">
                        <form action="{{ route('input.panen.store') }}" method="POST">
                            @csrf

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                {{-- PILIH LAHAN --}}
                                <div>
                                    <label class="block mb-1 text-xs font-semibold text-gray-700">
                                        Pilih Lahan <span class="text-red-500">*</span>
                                    </label>
                                    <select name="lahan_id"
                                            class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500"
                                            required>
                                        <option value="">-- Pilih Lahan Sawah --</option>
                                        @forelse($lahan as $item)
                                            <option value="{{ $item['id'] }}" @selected((string)$field('lahan_id') === (string)$item['id'])>
                                                {{ $item['nama_lahan'] }} | {{ $item['kecamatan'] ?? '-' }} | {{ $item['luas_lahan_hektar'] ?? 0 }} Ha
                                            </option>
                                        @empty
                                            <option disabled>Belum memiliki lahan terdaftar</option>
                                        @endforelse
                                    </select>
                                    <p class="mt-1.5 text-[10px] text-gray-500">
                                        Hanya menampilkan lahan milik akun yang sedang login.
                                    </p>
                                </div>

                                {{-- JENIS BIBIT --}}
                                <div>
                                    <label class="block mb-1 text-xs font-semibold text-gray-700">
                                        Jenis Bibit <span class="text-red-500">*</span>
                                    </label>
                                    <select name="bibit_id"
                                            class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500"
                                            required>
                                        <option value="">-- Pilih Bibit --</option>
                                        @foreach($bibit as $item)
                                            <option value="{{ $item['id'] }}" @selected((string)$field('bibit_id') === (string)$item['id'])>
                                                {{ $item['nama_bibit'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- TANGGAL TANAM --}}
                                <div>
                                    <label class="block mb-1 text-xs font-semibold text-gray-700">
                                        Tanggal Tanam <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date"
                                           name="tanggal_tanam"
                                           value="{{ $field('tanggal_tanam') }}"
                                           class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500"
                                           required>
                                </div>

                                {{-- ESTIMASI PANEN --}}
                                <div>
                                    <label class="block mb-1 text-xs font-semibold text-gray-700">
                                        Estimasi Panen (Hari) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number"
                                           name="estimasi_panen"
                                           value="{{ $field('estimasi_panen') }}"
                                           placeholder="Contoh: 90"
                                           class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500"
                                           required>
                                </div>

                                {{-- TANGGAL PANEN --}}
                                <div>
                                    <label class="block mb-1 text-xs font-semibold text-gray-700">
                                        Tanggal Panen
                                    </label>
                                    <input type="date"
                                           name="tanggal_panen"
                                           value="{{ $field('tanggal_panen') }}"
                                           class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500">
                                </div>

                                {{-- HASIL PANEN --}}
                                <div>
                                    <label class="block mb-1 text-xs font-semibold text-gray-700">
                                        Hasil Panen (Ton)
                                    </label>
                                    <input type="number"
                                           step="0.01"
                                           name="hasil_panen"
                                           value="{{ $field('hasil_panen') }}"
                                           placeholder="Contoh: 4.5"
                                           class="w-full border border-gray-300 rounded-xl px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500">
                                </div>
                            </div>

                            {{-- STATUS SISTEM --}}
                            <div class="mt-4 p-4 bg-blue-50 border border-blue-100 rounded-2xl">
                                <h4 class="font-semibold text-xs text-blue-800 mb-2">
                                    Informasi Status Sistem
                                </h4>
                                <div class="space-y-1.5 text-xs text-blue-700">
                                    <p>
                                        <strong>Status Aktivitas:</strong> AKTIF
                                    </p>
                                    <p>
                                        <strong>Status Verifikasi:</strong> PENDING
                                    </p>
                                    <p class="text-[10px] text-blue-600 pt-1 leading-relaxed">
                                        Data akan diverifikasi oleh petugas sebelum hasil panen masuk ke total produksi lahan.
                                    </p>
                                </div>
                            </div>

                            {{-- BUTTONS --}}
                            <div class="flex flex-col sm:flex-row justify-end gap-2.5 mt-5">
                                <a href="{{ route('riwayat.panen') }}"
                                   class="w-full sm:w-auto text-center px-8 py-2 rounded-xl border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                                    Batal
                                </a>
                                <button type="submit"
                                        class="w-full sm:w-auto px-5 py-2 rounded-xl bg-primary-700 text-xs font-semibold text-white hover:bg-primary-800 transition">
                                    Simpan Aktivitas
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN: CATATAN PEMUPUKAN --}}
            <div class="lg:col-span-5">
                <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-[#e7efd8]">
                    {{-- HEADER --}}
                    <div class="bg-primary-700 text-white px-5 py-4">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-primary-100 mb-1">
                            Pemeliharaan Lahan
                        </p>
                        <h1 class="text-base font-bold sm:text-lg">
                            Catatan Pemupukan
                        </h1>
                        <p class="text-xs text-primary-100 mt-0.5">
                            Catat pemberian pupuk pada lahan sawah yang sedang aktif dalam siklus tanam.
                        </p>
                    </div>

                    {{-- FORM BODY --}}
                    <div class="p-5">
                        <form action="{{ route('input.pemupukan.store') }}" method="POST">
                            @csrf

                            <div class="space-y-4">
                                {{-- Dropdown Siklus Tanam --}}
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                                        Lahan & Tanggal Tanam <span class="text-red-500">*</span>
                                    </label>
                                    <select name="siklus_tanam_id" class="w-full rounded-xl border border-gray-300 px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500" required>
                                        <option value="">-- Pilih Siklus Tanam --</option>
                                        @foreach($siklusTanam ?? [] as $siklus)
                                            <option value="{{ $siklus['id'] }}" {{ old('siklus_tanam_id') == $siklus['id'] ? 'selected' : '' }}>
                                                {{ $siklus['nama_lahan'] }} ({{ !empty($siklus['tanggal_tanam']) ? \Carbon\Carbon::parse($siklus['tanggal_tanam'])->format('d M Y') : '-' }}) - {{ $siklus['nama_bibit'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-[10px] text-gray-500 mt-1">Hanya menampilkan siklus tanam Anda.</p>
                                </div>

                                {{-- Dropdown Jenis Pupuk --}}
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                                        Jenis Pupuk <span class="text-red-500">*</span>
                                    </label>
                                    <select name="pupuk_id" class="w-full rounded-xl border border-gray-300 px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500" required>
                                        <option value="">-- Pilih Jenis Pupuk --</option>
                                        @foreach($pupuk ?? [] as $p)
                                            <option value="{{ $p['id'] }}" {{ old('pupuk_id') == $p['id'] ? 'selected' : '' }}>
                                                {{ $p['nama_pupuk'] }} ({{ $p['tipe'] }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    {{-- Tanggal Pemupukan --}}
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                                            Tanggal Pemupukan <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date" name="tanggal_pemupukan" 
                                               value="{{ old('tanggal_pemupukan', date('Y-m-d')) }}" 
                                               class="w-full rounded-xl border border-gray-300 px-3.5 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500" required>
                                    </div>

                                    {{-- Takaran (Kg) --}}
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                                            Takaran (Kg) <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <input type="number" name="takaran" step="0.01" min="0.01" 
                                                   value="{{ old('takaran') }}" 
                                                   placeholder="Contoh: 50.00" 
                                                   class="w-full rounded-xl border border-gray-300 px-3.5 py-2 pr-10 text-xs focus:outline-none focus:ring-2 focus:ring-primary-500" required>
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-500 text-[10px] font-semibold">
                                                Kg
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- BUTTONS --}}
                            <div class="flex justify-end gap-2.5 mt-5">
                                <button type="submit"
                                        class="w-full sm:w-auto px-5 py-2 rounded-xl bg-primary-700 text-xs font-semibold text-white hover:bg-primary-800 transition font-bold shadow-sm hover:shadow-md">
                                    Simpan Catatan Pemupukan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    @endif

</div>

@endsection