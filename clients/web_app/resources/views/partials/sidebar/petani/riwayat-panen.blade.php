@extends('layouts.app')

@section('title', 'Riwayat Aktivitas')

@section('content')

<div class="max-w-7xl mx-auto space-y-8">

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-[#022c22] tracking-tight">Riwayat Aktivitas</h1>
            <p class="text-sm text-slate-500 mt-1">Daftar riwayat pengajuan lahan, pemupukan, hingga hasil panen Anda.</p>
        </div>
    </div>

        {{-- ALERT SUCCESS --}}
        @if(session('success'))

            <div class="mb-4 p-3.5 rounded-2xl bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-semibold">

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
                    <tr class="bg-emerald-50 text-left text-xs uppercase tracking-wider text-gray-600">
                        <th class="p-3 border-b">No</th>
                        <th class="p-3 border-b">Nama Lahan</th>
                        <th class="p-3 border-b">Alamat Detail</th>
                        <th class="p-3 border-b">Luas (Ha)</th>
                        <th class="p-3 border-b">Status</th>
                        <th class="p-3 border-b text-center">Aksi</th>
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
                            <td class="p-3 border-b text-xs text-center">
                                @if($statusRaw === 'DITERIMA' && $statusSpasial === 'BELUM_DIPETAKAN')
                                    <button onclick="showContactOfficerModal('{{ $lahan['nama_lahan'] }}')" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition shadow-sm hover:scale-105 transform">Hubungi Petugas</button>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-xs text-gray-500">Belum ada catatan pengajuan lahan baru.</td>
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
                    <a href="{{ route('riwayat.panen', ['lahan_page' => $riwayatLahan['current_page'] + 1, 'pupuk_page' => request('pupuk_page', 1), 'page' => request('page', 1)]) }}" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 text-xs font-semibold">Selanjutnya →</a>
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

                    <tr class="bg-emerald-50 text-left text-xs uppercase tracking-wider text-gray-600">

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
                                <span class="text-xs font-bold text-emerald-700">
                                    {{ $item['hasil_panen'] ?? 0 }} Ton
                                </span>
                            </td>

                            <td class="p-3 border-b">

                                @if(($item['status_verifikasi'] ?? '') === 'DITERIMA')

                                    <div class="flex flex-col gap-1">
                                        <span class="w-fit px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">
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
                       class="px-3.5 py-1.5 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 text-xs font-semibold">

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

                    <tr class="bg-emerald-50 text-left text-xs uppercase tracking-wider text-gray-600">

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
                       class="px-3.5 py-1.5 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 text-xs font-semibold">

                        Selanjutnya →

                    </a>

                @else

                    <div></div>

                @endif

            </div>

        @endif

    </div>

</div>

<!-- Modal Hubungi Petugas -->
<div id="contactModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 font-['Poppins']">
    <div class="bg-white rounded-[2rem] shadow-2xl border border-slate-100 w-full max-w-md overflow-hidden transform scale-95 transition-all duration-300" id="contactModalContent">
        <!-- Modal Header -->
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800">Hubungi Petugas Pemetaan</h3>
            <button onclick="closeContactModal()" class="p-2 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 space-y-4 text-center">
            <div class="flex items-center justify-center w-14 h-14 rounded-full bg-emerald-50 text-emerald-600 mx-auto">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-7 h-7">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.446 6.002-3.461a2.25 2.25 0 0 0 1.096-1.943V4.354a2.25 2.25 0 0 0-3.348-1.97L13.75 5.772a2.25 2.25 0 0 1-1.5 0L6.25 2.454a2.25 2.25 0 0 0-3.348 1.97v11.135c0 .712.338 1.378.919 1.839L9.75 21V6.75M9 6.75 13.75 9.75M9 21l4.75-3" />
                </svg>
            </div>
            
            <div>
                <p class="text-sm font-semibold text-slate-700 leading-relaxed">
                    Untuk melanjutkan ke tahap pemetaan lahan <span id="contactLahanName" class="font-bold text-emerald-600"></span>, silakan segera menghubungi petugas dinas pertanian.
                </p>
                <p class="text-xs text-slate-400 mt-3 font-medium">
                    Anda dapat berkoordinasi langsung untuk menjadwalkan kunjungan pemetaan lahan sawah Anda.
                </p>
            </div>

            <div class="pt-4 border-t border-slate-100">
                <a href="https://wa.me/6285753510996" target="_blank" class="w-full flex items-center justify-center gap-2 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-bold transition-all shadow-md shadow-emerald-100 hover:scale-105 transform text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="w-5 h-5">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.746.953 3.71 1.458 5.704 1.459h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                    </svg>
                    Hubungi via WhatsApp
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function showContactOfficerModal(lahanName) {
        const modal = document.getElementById('contactModal');
        const modalContent = document.getElementById('contactModalContent');
        const lahanText = document.getElementById('contactLahanName');
        
        lahanText.innerText = lahanName;
        modal.classList.remove('hidden');
        setTimeout(() => {
            modalContent.classList.remove('scale-95');
            modalContent.classList.add('scale-100');
        }, 10);
    }
    
    function closeContactModal() {
        const modal = document.getElementById('contactModal');
        const modalContent = document.getElementById('contactModalContent');
        
        modalContent.classList.remove('scale-100');
        modalContent.classList.add('scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 150);
    }
    
    document.getElementById('contactModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeContactModal();
        }
    });
</script>
@endsection