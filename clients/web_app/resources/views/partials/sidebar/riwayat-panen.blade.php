@extends('layouts.app')

@section('title', 'Riwayat Panen')

@section('content')

<div class="max-w-7xl mx-auto bg-white p-6 rounded-xl shadow">

    {{-- HEADER --}}
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">

        <div>
            <h1 class="text-2xl font-bold text-primary-900">
                Riwayat Panen
            </h1>

            <p class="text-sm text-gray-500 mt-1">
                Daftar aktivitas tanam dan hasil panen petani.
            </p>
        </div>
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

    {{-- TABLE --}}
    <div class="overflow-x-auto">

        <table class="w-full border-collapse">

            <thead>

                <tr class="bg-green-50 text-left text-sm uppercase tracking-wider text-gray-600">

                    <th class="p-4 border-b">
                        No
                    </th>

                    <th class="p-4 border-b">
                        Nama Lahan
                    </th>

                    <th class="p-4 border-b">
                        Bibit
                    </th>

                    <th class="p-4 border-b">
                        Tanggal Tanam
                    </th>

                    <th class="p-4 border-b">
                        Tanggal Panen
                    </th>

                    <th class="p-4 border-b">
                        Hasil Panen
                    </th>

                    <th class="p-4 border-b">
                        Status
                    </th>

                </tr>

            </thead>

<tbody>

    @forelse(($riwayat['data'] ?? []) as $item)

        <tr class="hover:bg-gray-50 transition">

            <td class="p-4 border-b text-sm text-gray-700">
                {{ (($riwayat['current_page'] ?? 1) - 1) * ($riwayat['per_page'] ?? 5) + $loop->iteration }}
            </td>

            <td class="p-4 border-b">

                <div class="font-medium text-gray-800">
                    {{ $item['lahan']['nama_lahan'] ?? '-' }}
                </div>

                <div class="text-xs text-gray-500">
                    {{ $item['lahan']['luas_lahan_hektar'] ?? '-' }} Ha
                </div>

            </td>

            <td class="p-4 border-b text-gray-700">
                {{ $item['bibit']['nama_bibit'] ?? '-' }}
            </td>

            <td class="p-4 border-b text-gray-700">
                {{ !empty($item['tanggal_tanam'])
                    ? \Carbon\Carbon::parse($item['tanggal_tanam'])->format('d M Y')
                    : '-' }}
            </td>

            <td class="p-4 border-b text-gray-700">
                {{ !empty($item['tanggal_panen'])
                    ? \Carbon\Carbon::parse($item['tanggal_panen'])->format('d M Y')
                    : '-' }}
            </td>

            <td class="p-4 border-b">
                <span class="font-semibold text-green-700">
                    {{ $item['hasil_panen'] ?? 0 }} Ton
                </span>
            </td>

            <td class="p-4 border-b">

                @if(($item['status_verifikasi'] ?? '') === 'DITERIMA')

                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                        DITERIMA
                    </span>

                @elseif(($item['status_verifikasi'] ?? '') === 'DITOLAK')

                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                        DITOLAK
                    </span>

                @else

                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">
                        PENDING
                    </span>

                @endif

            </td>

        </tr>

    @empty

        <tr>
            <td colspan="7" class="p-6 text-center text-gray-500">
                Belum ada riwayat panen yang diinput.
            </td>
        </tr>

    @endforelse

</tbody>

        </table>

    </div>

 @if(!empty($riwayat) && isset($riwayat['current_page']))

<div class="flex justify-between items-center mt-6">

    {{-- Tombol Sebelumnya --}}
    @if($riwayat['current_page'] > 1)

        <a href="{{ route('riwayat.panen', [
            'page' => $riwayat['current_page'] - 1,
            'search' => request('search')
        ]) }}"
           class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300">

            ← Sebelumnya

        </a>

    @else

        <div></div>

    @endif

    {{-- Informasi Halaman --}}
    <span class="text-sm text-gray-500">
        Halaman {{ $riwayat['current_page'] }}
        dari {{ $riwayat['last_page'] }}
    </span>

    {{-- Tombol Selanjutnya --}}
    @if($riwayat['current_page'] < $riwayat['last_page'])

        <a href="{{ route('riwayat.panen', [
            'page' => $riwayat['current_page'] + 1,
            'search' => request('search')
        ]) }}"
           class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700">

            Selanjutnya →

        </a>

    @else

        <div></div>

    @endif

</div>

@endif

@endsection