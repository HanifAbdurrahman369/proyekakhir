@extends('layouts.app')

@section('title', 'Input Aktivitas Tanam')

@section('content')

<div class="max-w-4xl mx-auto px-4 sm:px-6 py-6">

    <div class="bg-white rounded-3xl shadow-xl overflow-hidden">

        {{-- HEADER --}}
        <div class="bg-primary-700 text-white px-6 py-5">

            <h1 class="text-2xl font-bold">
                Form Input Aktivitas Tanam
            </h1>

            <p class="text-sm text-primary-100 mt-1">
                Silakan isi data aktivitas tanam dengan benar.
                Status verifikasi akan diproses oleh petugas.
            </p>

        </div>

        <div class="p-6">

            {{-- ALERT SUCCESS --}}
            @if(session('success'))

                <div class="mb-5 p-4 rounded-2xl bg-green-100 text-green-700 border border-green-200">

                    {{ session('success') }}

                </div>

            @endif

            {{-- ALERT ERROR --}}
            @if(session('error'))

                <div class="mb-5 p-4 rounded-2xl bg-red-100 text-red-700 border border-red-200">

                    {{ session('error') }}

                </div>

            @endif

            {{-- VALIDATION ERROR --}}
            @if($errors->any())

                <div class="mb-5 p-4 rounded-2xl bg-red-100 text-red-700 border border-red-200">

                    <ul class="list-disc pl-5 text-sm">

                        @foreach($errors->all() as $error)

                            <li>{{ $error }}</li>

                        @endforeach

                    </ul>

                </div>

            @endif

            {{-- FORM --}}
            <form action="{{ route('input.panen.store') }}" method="POST">

                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

                    {{-- PILIH LAHAN --}}
                    <div>

                        <label class="block mb-2 font-medium text-gray-700">
                            Pilih Lahan
                        </label>

                        <select name="lahan_id"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary-500"
                                required>

                            <option value="">
                                -- Pilih Lahan --
                            </option>

                            @foreach($lahan as $item)

                                <option value="{{ $item['id'] }}">

                                    {{ $item['nama_lahan'] }}
                                    ({{ $item['luas_lahan_hektar'] ?? '-' }} Ha)

                                </option>

                            @endforeach

                        </select>

                    </div>

                    {{-- JENIS BIBIT --}}
                    <div>

                        <label class="block mb-2 font-medium text-gray-700">
                            Jenis Bibit
                        </label>

                        <select name="bibit_id"
                                class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary-500"
                                required>

                            <option value="">
                                -- Pilih Bibit --
                            </option>

                            @foreach($bibit as $item)

                                <option value="{{ $item['id'] }}">

                                    {{ $item['nama_bibit'] }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                    {{-- TANGGAL TANAM --}}
                    <div>

                        <label class="block mb-2 font-medium text-gray-700">
                            Tanggal Tanam
                        </label>

                        <input type="date"
                               name="tanggal_tanam"
                               class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary-500"
                               required>

                    </div>

                    {{-- ESTIMASI PANEN --}}
                    <div>

                        <label class="block mb-2 font-medium text-gray-700">
                            Estimasi Panen (Hari)
                        </label>

                        <input type="number"
                               name="estimasi_panen"
                               placeholder="Contoh: 90"
                               class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary-500"
                               required>

                    </div>

                    {{-- TANGGAL PANEN --}}
                    <div>

                        <label class="block mb-2 font-medium text-gray-700">
                            Tanggal Panen
                        </label>

                        <input type="date"
                               name="tanggal_panen"
                               class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary-500">

                    </div>

                    {{-- HASIL PANEN --}}
                    <div>

                        <label class="block mb-2 font-medium text-gray-700">
                            Hasil Panen (Ton)
                        </label>

                        <input type="number"
                               step="0.01"
                               name="hasil_panen"
                               placeholder="Contoh: 4.5"
                               class="w-full border border-gray-300 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary-500">

                    </div>

                </div>

                {{-- STATUS SISTEM --}}
                <div class="mt-6 p-5 bg-blue-50 border border-blue-100 rounded-2xl">

                    <h4 class="font-semibold text-blue-800 mb-3">
                        Informasi Status Sistem
                    </h4>

                    <div class="space-y-2 text-sm text-blue-700">

                        <p>
                            <strong>Status Aktivitas:</strong>
                            AKTIF
                        </p>

                        <p>
                            <strong>Status Verifikasi:</strong>
                            PENDING
                        </p>

                        <p class="text-xs text-blue-600 pt-2">
                            Data akan diverifikasi oleh petugas sebelum
                            hasil panen masuk ke total produksi lahan.
                        </p>

                    </div>

                </div>

                {{-- BUTTON --}}
                <div class="flex flex-col sm:flex-row justify-end gap-3 mt-6">

                    <a href="{{ url('/dashboard-petani') }}"
                       class="w-full sm:w-auto text-center px-5 py-3 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-50 transition">

                        Batal

                    </a>

                    <button type="submit"
                            class="w-full sm:w-auto px-6 py-3 rounded-xl bg-primary-700 text-white hover:bg-primary-800 transition">

                        Simpan Aktivitas

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection