@extends('layouts.app')

@section('title', 'Riwayat Aktivitas')

@section('content')

<div class="max-w-7xl mx-auto space-y-8">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#14280b] tracking-tight">Riwayat Aktivitas</h1>
            <p class="text-sm text-slate-500 mt-1">Daftar riwayat pengajuan lahan, pemupukan, hingga hasil panen Anda.</p>
        </div>
    </div>

        {{-- ALERT SUCCESS --}}
        @if(session('success'))

            <div class="mb-4 p-3.5 rounded-2xl bg-green-100 text-green-700 border border-green-200 text-xs font-semibold">

                {{ session('success') }}

            </div>

        @endif

        {{-- ALERT ERROR --}}
        @if(session('error'))
            <div class="mb-4 p-3.5 rounded-2xl bg-red-100 text-red-700 border border-red-200 text-xs font-semibold">
                {{ session('error') }}
            </div>
        @endif

    {{-- CARD BARU: RIWAYAT LAHAN --}}
    <div class="bg-white p-6 rounded-xl shadow">
        <div class="mb-6">
            <h2 class="text-lg font-bold text-primary-900">Riwayat Pengajuan Lahan</h2>
            <p class="text-xs text-gray-500 mt-0.5">Catatan riwayat pengajuan lahan sawah baru Anda beserta statusnya.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-green-50 text-left text-xs uppercase tracking-wider text-gray-600">
                        <th class="p-3 border-b">No</th>
                        <th class="p-3 border-b">Nama Lahan</th>
                        <th class="p-3 border-b">Alamat Detail</th>
                        <th class="p-3 border-b">Luas (Ha)</th>
                        <th class="p-3 border-b">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($riwayatLahan['data'] ?? []) as $lahan)
                        @php
                            $statusRaw = $lahan['status_verifikasi'] ?? 'PENDING';
                            $statusSpasial = $lahan['status_spasial'] ?? 'BELUM_DIPETAKAN';
                            $statusText = $statusRaw;
                            if ($statusRaw === 'DITERIMA') {
                                $statusText = $statusSpasial === 'SUDAH_DIPETAKAN' ? 'TERVERIFIKASI' : 'DISETUJUI';
                            }
                            $statusClass = $statusRaw === 'DITERIMA' ? 'bg-emerald-50 text-emerald-700' : ($statusRaw === 'DITOLAK' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700');
                        @endphp
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-3 border-b text-xs text-gray-700">{{ (($riwayatLahan['current_page'] ?? 1) - 1) * ($riwayatLahan['per_page'] ?? 10) + $loop->iteration }}</td>
                            <td class="p-3 border-b text-xs font-semibold text-gray-800">{{ $lahan['nama_lahan'] }}</td>
                            <td class="p-3 border-b text-xs text-gray-700">{{ $lahan['alamat_detail'] ?? '-' }}</td>
                            <td class="p-3 border-b text-xs text-gray-700">{{ $lahan['luas_lahan_hektar'] ?? 0 }}</td>
                            <td class="p-3 border-b text-xs"><span class="px-2 py-1 rounded-full font-bold {{ $statusClass }}">{{ str_replace('_', ' ', $statusText) }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-xs text-gray-500">Belum ada catatan pengajuan lahan baru.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(!empty($riwayatLahan) && isset($riwayatLahan['current_page']))
            <div class="flex justify-between items-center mt-6">
                @if($riwayatLahan['current_page'] > 1)
                    <a href="{{ route('riwayat.panen', ['lahan_page' => $riwayatLahan['current_page'] - 1, 'pupuk_page' => request('pupuk_page', 1), 'page' => request('page', 1)]) }}" class="px-3.5 py-1.5 rounded-xl bg-gray-200 hover:bg-gray-300 text-xs font-semibold">← Sebelumnya</a>
                @else
                    <div></div>
                @endif
                <span class="text-xs text-gray-500">Halaman {{ $riwayatLahan['current_page'] }} dari {{ $riwayatLahan['last_page'] }}</span>
                @if($riwayatLahan['current_page'] < $riwayatLahan['last_page'])
                    <a href="{{ route('riwayat.panen', ['lahan_page' => $riwayatLahan['current_page'] + 1, 'pupuk_page' => request('pupuk_page', 1), 'page' => request('page', 1)]) }}" class="px-3.5 py-1.5 rounded-xl bg-green-600 text-white hover:bg-green-700 text-xs font-semibold">Selanjutnya →</a>
                @else
                    <div></div>
                @endif
            </div>
        @endif
    </div>

    {{-- CARD: RIWAYAT PANEN --}}
    <div class="bg-white p-6 rounded-xl shadow">
        <div class="mb-6">
            <h2 class="text-lg font-bold text-primary-900">Riwayat Panen</h2>
            <p class="text-xs text-gray-500 mt-0.5">Daftar aktivitas tanam dan hasil panen petani.</p>
        </div>
        <div class="overflow-x-auto">

            <table class="w-full border-collapse">

                <thead>

                    <tr class="bg-green-50 text-left text-xs uppercase tracking-wider text-gray-600">

                        <th class="p-3 border-b">
                            No
                        </th>

                        <th class="p-3 border-b">
                            Nama Lahan
                        </th>

                        <th class="p-3 border-b">
                            Bibit
                        </th>

                        <th class="p-3 border-b">
                            Tanggal Tanam
                        </th>

                        <th class="p-3 border-b">
                            Tanggal Panen
                        </th>

                        <th class="p-3 border-b">
                            Hasil Panen
                        </th>

                        <th class="p-3 border-b">
                            Status
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse(($riwayat['data'] ?? []) as $item)

                        <tr class="hover:bg-gray-50 transition">

                            <td class="p-3 border-b text-xs text-gray-700">
                                {{ (($riwayat['current_page'] ?? 1) - 1) * ($riwayat['per_page'] ?? 5) + $loop->iteration }}
                            </td>

                            <td class="p-3 border-b">

                                <div class="text-xs font-semibold text-gray-800">
                                    {{ $item['lahan']['nama_lahan'] ?? '-' }}
                                </div>

                                <div class="text-[10px] text-gray-500 mt-0.5">
                                    {{ $item['lahan']['luas_lahan_hektar'] ?? '-' }} Ha
                                </div>

                            </td>

                            <td class="p-3 border-b text-xs text-gray-700">
                                {{ $item['bibit']['nama_bibit'] ?? '-' }}
                            </td>

                            <td class="p-3 border-b text-xs text-gray-700">
                                {{ !empty($item['tanggal_tanam'])
                                    ? \Carbon\Carbon::parse($item['tanggal_tanam'])->format('d M Y')
                                    : '-' }}
                            </td>

                            <td class="p-3 border-b text-xs text-gray-700">
                                {{ !empty($item['tanggal_panen'])
                                    ? \Carbon\Carbon::parse($item['tanggal_panen'])->format('d M Y')
                                    : '-' }}
                            </td>

                            <td class="p-3 border-b">
                                <span class="text-xs font-bold text-green-700">
                                    {{ $item['hasil_panen'] ?? 0 }} Ton
                                </span>
                            </td>

                            <td class="p-3 border-b">

                                @if(($item['status_verifikasi'] ?? '') === 'DITERIMA')

                                    <div class="flex flex-col gap-1">
                                        <span class="w-fit px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-700">
                                            DITERIMA
                                        </span>
                                        <span class="text-[10px] text-gray-500 max-w-[200px] break-words mt-1">
                                            Catatan: {{ !empty($item['catatan_verifikasi']) ? $item['catatan_verifikasi'] : 'Belum ada catatan verifikasi.' }}
                                        </span>
                                    </div>

                                @elseif(($item['status_verifikasi'] ?? '') === 'DITOLAK')

                                    <div class="flex flex-col gap-1">
                                        <span class="w-fit px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">
                                            DITOLAK
                                        </span>
                                        <span class="text-[10px] text-red-600 font-medium max-w-[200px] break-words mt-1">
                                            Catatan: {{ !empty($item['catatan_verifikasi']) ? $item['catatan_verifikasi'] : 'Belum ada catatan verifikasi.' }}
                                        </span>
                                        <div>
                                            <a href="{{ route('panen.edit', $item['id']) }}"
                                               class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold transition duration-200 shadow-sm hover:shadow mt-1">
                                                Perbaiki
                                            </a>
                                        </div>
                                    </div>

                                @else

                                    <div class="flex flex-col gap-1">
                                        <span class="w-fit px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-yellow-100 text-yellow-700">
                                            PENDING
                                        </span>
                                        <span class="text-[10px] text-gray-500 max-w-[200px] break-words mt-1">
                                            Catatan: {{ !empty($item['catatan_verifikasi']) ? $item['catatan_verifikasi'] : 'Belum ada catatan verifikasi.' }}
                                        </span>
                                    </div>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="7" class="p-6 text-center text-xs text-gray-500">
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
                        'search' => request('search'),
                        'pupuk_page' => request('pupuk_page', 1),
                        'lahan_page' => request('lahan_page', 1)
                    ]) }}"
                       class="px-3.5 py-1.5 rounded-xl bg-gray-200 hover:bg-gray-300 text-xs font-semibold">

                        ← Sebelumnya

                    </a>

                @else

                    <div></div>

                @endif

                {{-- Informasi Halaman --}}
                <span class="text-xs text-gray-500">
                    Halaman {{ $riwayat['current_page'] }}
                    dari {{ $riwayat['last_page'] }}
                </span>

                {{-- Tombol Selanjutnya --}}
                @if($riwayat['current_page'] < $riwayat['last_page'])

                    <a href="{{ route('riwayat.panen', [
                        'page' => $riwayat['current_page'] + 1,
                        'search' => request('search'),
                        'pupuk_page' => request('pupuk_page', 1),
                        'lahan_page' => request('lahan_page', 1)
                    ]) }}"
                       class="px-3.5 py-1.5 rounded-xl bg-green-600 text-white hover:bg-green-700 text-xs font-semibold">

                        Selanjutnya →

                    </a>

                @else

                    <div></div>

                @endif

            </div>

        @endif

    </div>

    {{-- CARD BARU: RIWAYAT PEMUPUKAN --}}
    <div class="bg-white p-6 rounded-xl shadow">

        <div class="mb-6">
            <h2 class="text-lg font-bold text-primary-900">
                Riwayat Tanam & Pemupukan
            </h2>

            <p class="text-xs text-gray-500 mt-0.5">
                Catatan riwayat pemberian pupuk pada lahan sawah yang dikelola.
            </p>
        </div>

        <div class="overflow-x-auto">

            <table class="w-full border-collapse">

                <thead>

                    <tr class="bg-green-50 text-left text-xs uppercase tracking-wider text-gray-600">

                        <th class="p-3 border-b">
                            No
                        </th>

                        <th class="p-3 border-b">
                            Nama Lahan
                        </th>

                        <th class="p-3 border-b">
                            Jenis Pupuk
                        </th>

                        <th class="p-3 border-b">
                            Tipe Pupuk
                        </th>

                        <th class="p-3 border-b">
                            Tanggal Pemupukan
                        </th>

                        <th class="p-3 border-b">
                            Takaran
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse(($riwayatPupuk['data'] ?? []) as $pupuk)

                        <tr class="hover:bg-gray-50 transition">

                            <td class="p-3 border-b text-xs text-gray-700">
                                {{ (($riwayatPupuk['current_page'] ?? 1) - 1) * ($riwayatPupuk['per_page'] ?? 3) + $loop->iteration }}
                            </td>

                            <td class="p-3 border-b text-xs font-semibold text-gray-800">
                                {{ $pupuk['nama_lahan'] }}
                            </td>

                            <td class="p-3 border-b text-xs text-gray-700">
                                {{ $pupuk['nama_pupuk'] }}
                            </td>

                            <td class="p-3 border-b text-xs text-gray-700">
                                {{ $pupuk['tipe_pupuk'] }}
                            </td>

                            <td class="p-3 border-b text-xs text-gray-700">
                                {{ !empty($pupuk['tanggal_pemupukan'])
                                    ? \Carbon\Carbon::parse($pupuk['tanggal_pemupukan'])->format('d M Y')
                                    : '-' }}
                            </td>

                            <td class="p-3 border-b text-xs font-bold text-emerald-700">
                                {{ number_format($pupuk['takaran'], 2, ',', '.') }} Kg
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="p-6 text-center text-xs text-gray-500">
                                Belum ada catatan pemupukan yang disimpan.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        @if(!empty($riwayatPupuk) && isset($riwayatPupuk['current_page']))

            <div class="flex justify-between items-center mt-6">

                {{-- Tombol Sebelumnya --}}
                @if($riwayatPupuk['current_page'] > 1)

                    <a href="{{ route('riwayat.panen', [
                        'pupuk_page' => $riwayatPupuk['current_page'] - 1,
                        'page' => request('page', 1),
                        'lahan_page' => request('lahan_page', 1)
                    ]) }}"
                       class="px-3.5 py-1.5 rounded-xl bg-gray-200 hover:bg-gray-300 text-xs font-semibold">

                        ← Sebelumnya

                    </a>

                @else

                    <div></div>

                @endif

                {{-- Informasi Halaman --}}
                <span class="text-xs text-gray-500">
                    Halaman {{ $riwayatPupuk['current_page'] }}
                    dari {{ $riwayatPupuk['last_page'] }}
                </span>

                {{-- Tombol Selanjutnya --}}
                @if($riwayatPupuk['current_page'] < $riwayatPupuk['last_page'])

                    <a href="{{ route('riwayat.panen', [
                        'pupuk_page' => $riwayatPupuk['current_page'] + 1,
                        'page' => request('page', 1),
                        'lahan_page' => request('lahan_page', 1)
                    ]) }}"
                       class="px-3.5 py-1.5 rounded-xl bg-green-600 text-white hover:bg-green-700 text-xs font-semibold">

                        Selanjutnya →

                    </a>

                @else

                    <div></div>

                @endif

            </div>

        @endif

    </div>

</div>

@endsection