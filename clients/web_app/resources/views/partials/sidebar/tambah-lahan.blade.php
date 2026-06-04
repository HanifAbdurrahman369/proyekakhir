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

            <form>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                    {{-- Nama Lahan --}}
                    <div>
                        <label class="block mb-2 font-medium text-gray-700">
                            Nama Lahan
                        </label>

                        <input type="text"
                               placeholder="Masukkan nama lahan"
                               class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>

                    {{-- Kecamatan --}}
                    <div>
                        <label class="block mb-2 font-medium text-gray-700">
                            Kecamatan
                        </label>

                        <select class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <option>Pilih Kecamatan</option>
                        </select>
                    </div>

                    {{-- Kelurahan --}}
                    <div>
                        <label class="block mb-2 font-medium text-gray-700">
                            Kelurahan
                        </label>

                        <select class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <option>Pilih Kelurahan</option>
                        </select>
                    </div>

                    {{-- Status --}}
                    <div>
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

                    <textarea rows="5"
                              placeholder="Masukkan alamat lengkap lahan"
                              class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>
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