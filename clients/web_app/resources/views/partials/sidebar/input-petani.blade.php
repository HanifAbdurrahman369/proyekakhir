@extends('layouts.app')

@section('title', 'Input Aktivitas Tanam')

@section('content')

<div class="max-w-4xl mx-auto bg-white p-6 rounded-xl shadow">

    {{-- HEADER --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-primary-900">
            Form Input Aktivitas Tanam
        </h1>

        <p class="text-sm text-gray-500 mt-1">
            Silakan isi data aktivitas tanam dengan benar.
            Status verifikasi akan diproses oleh petugas.
        </p>
    </div>

    {{-- ALERT SUCCESS --}}
    @if(session('success'))
        <div class="mb-4 p-4 rounded-lg bg-green-100 text-green-700 border border-green-200">
            {{ session('success') }}
        </div>
    @endif

    {{-- ALERT ERROR --}}
    @if(session('error'))
        <div class="mb-4 p-4 rounded-lg bg-red-100 text-red-700 border border-red-200">
            {{ session('error') }}
        </div>
    @endif

    {{-- VALIDATION ERROR --}}
    @if($errors->any())
        <div class="mb-4 p-4 rounded-lg bg-red-100 text-red-700 border border-red-200">
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

        {{-- PILIH LAHAN --}}
        <div class="mb-5">

            <label class="block text-sm font-semibold text-gray-700 mb-2">
                Pilih Lahan
            </label>

            <select name="lahan_id"
                    class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500 focus:outline-none"
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
        <div class="mb-5">

            <label class="block text-sm font-semibold text-gray-700 mb-2">
                Jenis Bibit
            </label>

            <select name="bibit_id"
                    class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500 focus:outline-none"
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
        <div class="mb-5">

            <label class="block text-sm font-semibold text-gray-700 mb-2">
                Tanggal Tanam
            </label>

            <input type="date"
                   name="tanggal_tanam"
                   class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500 focus:outline-none"
                   required>

        </div>

        {{-- ESTIMASI PANEN --}}
        <div class="mb-5">

            <label class="block text-sm font-semibold text-gray-700 mb-2">
                Estimasi Panen (Hari)
            </label>

            <input type="number"
                   name="estimasi_panen"
                   class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500 focus:outline-none"
                   placeholder="Contoh: 90"
                   required>

        </div>

        {{-- TANGGAL PANEN --}}
        <div class="mb-5">

            <label class="block text-sm font-semibold text-gray-700 mb-2">
                Tanggal Panen
            </label>

            <input type="date"
                   name="tanggal_panen"
                   class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">

        </div>

        {{-- HASIL PANEN --}}
        <div class="mb-5">

            <label class="block text-sm font-semibold text-gray-700 mb-2">
                Hasil Panen (Ton)
            </label>

            <input type="number"
                   step="0.01"
                   name="hasil_panen"
                   class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-green-500 focus:outline-none"
                   placeholder="Contoh: 4.5">

        </div>

        {{-- STATUS OTOMATIS --}}
        <div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">

            <h3 class="text-sm font-semibold text-gray-700 mb-2">
                Informasi Status Sistem
            </h3>

            <div class="text-sm text-gray-600 space-y-1">

                <p>
                    <span class="font-semibold">
                        Status Aktivitas:
                    </span>

                    AKTIF
                </p>

                <p>
                    <span class="font-semibold">
                        Status Verifikasi:
                    </span>

                    PENDING
                </p>

                <p class="text-xs text-gray-500 mt-2">
                    Data akan diverifikasi oleh petugas sebelum
                    hasil panen masuk ke total produksi lahan.
                </p>

            </div>

        </div>

        {{-- BUTTON --}}
        <div class="flex justify-end">

            <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white font-medium px-6 py-3 rounded-lg transition">

                Simpan Aktivitas

            </button>

        </div>

    </form>

</div>

@endsection