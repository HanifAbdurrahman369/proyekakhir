@extends('layouts.app')

@section('title', 'Tambah Lahan')

@section('content')

<div class="max-w-4xl mx-auto px-4 sm:px-6 py-6">

    <div class="bg-white rounded-3xl shadow-xl overflow-hidden">

        {{-- HEADER --}}
        <div class="bg-primary-700 text-white px-5 sm:px-6 py-4">

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

                <div>
                    <h1 class="text-xl sm:text-2xl font-bold">
                        Tambah Lahan Baru
                    </h1>

                    <p class="text-sm text-primary-100 mt-1">
                        Lengkapi data lahan untuk diajukan kepada petugas.
                    </p>
                </div>

            </div>

        </div>

        {{-- FORM --}}
        <div class="p-5 sm:p-6">
                {{-- Alert Success --}}
                @if(session('success'))
                    <div class="mb-5 p-4 rounded-xl bg-green-100 border border-green-300 text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Alert Error --}}
                @if(session('error'))
                    <div class="mb-5 p-4 rounded-xl bg-red-100 border border-red-300 text-red-700">
                        {{ session('error') }}
                    </div>
                @endif

            <form method="POST" action="{{ url('/lahan/store') }}">
            @csrf

                 <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                    {{-- Nama Lahan --}}
                    <div>
                        <label class="block mb-2 font-medium text-gray-700">
                            Nama Lahan
                        </label>

                        <input type="text"
                            name="nama_lahan"
                            placeholder="Masukkan nama lahan"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary-500"
                            required>
                    </div>

                    {{-- Kecamatan --}}
                    <div>
                        <label class="block mb-2 font-medium text-gray-700">
                            Kecamatan
                        </label>

                        <select name="kecamatan_id"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary-500"
                                required>
                            <option value="">Pilih Kecamatan</option>

                            @foreach($kecamatan as $item)
                                <option value="{{ $item['id'] }}">
                                    {{ $item['nama_kecamatan'] ?? $item['nama'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Kelurahan --}}
                    <div>
                        <label class="block mb-2 font-medium text-gray-700">
                            Kelurahan
                        </label>

                        <select name="kelurahan_id"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary-500"
                                required>
                            <option value="">Pilih Kelurahan</option>

                            @foreach($kelurahan as $item)
                                <option value="{{ $item['id'] }}">
                                    {{ $item['nama_kelurahan'] ?? $item['nama'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tipe Lahan --}}
                    <div>
                        <label class="block mb-2 font-medium text-gray-700">
                            Tipe Lahan
                        </label>

                        <select name="tipe_lahan_id"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary-500"
                                required>
                            <option value="">Pilih Tipe Lahan</option>

                            @foreach($tipeLahan as $item)
                                <option value="{{ $item['id'] }}">
                                    {{ $item['nama_tipe'] ?? $item['nama'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status --}}
                    <div class="lg:col-span-2">
                        <label class="block mb-2 font-medium text-gray-700">
                            Status Pengajuan
                        </label>

                        <input type="text"
                            value="Menunggu Persetujuan"
                            readonly
                            class="w-full bg-yellow-50 border border-yellow-300 rounded-xl px-4 py-3 text-yellow-700">
                    </div>

                </div>

                {{-- Alamat --}}
                <div class="mt-5">
                    <label class="block mb-2 font-medium text-gray-700">
                        Alamat Lengkap
                    </label>

                    <textarea name="alamat_detail"
                            rows="5"
                            placeholder="Masukkan alamat lengkap lahan"
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary-500"
                            required></textarea>
                </div>

                {{-- Informasi --}}
                <div class="mt-6 p-4 sm:p-5 bg-blue-50 border border-blue-100 rounded-2xl">

                    <h4 class="font-semibold text-blue-800 mb-3">
                        Informasi Pengajuan Lahan
                    </h4>

                    <ul class="text-sm text-blue-700 space-y-2">
                        <li>• Petani hanya mengisi data identitas lahan.</li>
                        <li>• Titik koordinat (latitude dan longitude) akan ditentukan oleh petugas.</li>
                        <li>• Data lahan harus diverifikasi sebelum digunakan untuk pelaporan hasil panen.</li>
                        <li>• Setelah disetujui, lahan akan muncul pada daftar lahan milik petani.</li>
                    </ul>

                </div>

                {{-- Tombol --}}
                <div class="flex flex-col sm:flex-row justify-end gap-3 mt-6">

                    <a href="{{ url('/dashboard-petani') }}"
                       class="w-full sm:w-auto text-center px-5 py-3 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50 transition">
                        Batal
                    </a>

                    <button type="submit"
                            class="w-full sm:w-auto px-6 py-3 rounded-xl bg-primary-700 text-white hover:bg-primary-800 transition">
                        Simpan Pengajuan
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection