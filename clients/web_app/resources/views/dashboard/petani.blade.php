@extends('layouts.app')

@section('title', 'Lahan Saya')

@section('content')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-7">
    <div>
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-bold bg-[#edf8dc] text-[#3E7D00] border border-[#dfeccc]">Dashboard Petani</span>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#14280b] mt-3 tracking-tight">Lahan Saya</h1>
        <p class="text-sm text-slate-500 mt-1">Pantau data lahan, riwayat produksi, dan pelaporan hasil panen Anda.</p>
    </div>

    <a href="{{ route('tambah.lahan') }}"
        class="btn-green inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl text-sm font-bold transition">
        <span>🌾</span> Tambah Lahan
    </a>

    <a href="{{ route('input.panen') }}"
       class="btn-green inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl text-sm font-bold transition">
        <span>🌾</span> Lapor Hasil Panen
    </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5 lg:gap-6">
    <div class="xl:col-span-2 space-y-5">
        <div class="glass-card rounded-[28px] p-5 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
                <div>
                    <h3 class="font-extrabold text-[#14280b] text-base">Informasi Lahan Utama</h3>
                    <p class="text-xs text-slate-500 mt-1">Data ringkas lahan yang terdaftar pada sistem.</p>
                </div>
            </div>

                <div class="space-y-5">

                @forelse($lahan['data'] ?? [] as $item)

                <div class="rounded-3xl border border-[#e7efd8] p-5">

                    <div class="flex justify-between items-center mb-4">

                        <h3 class="font-bold text-lg">
                            {{ $item['nama_lahan'] }}
                        </h3>

                        @if($item['status_verifikasi'] == 'DITERIMA')

                            <span class="w-fit px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Terverifikasi
                            </span>

                        @elseif($item['status_verifikasi'] == 'PENDING')

                            <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-yellow-50 text-yellow-700 border border-yellow-200">
                                Menunggu
                            </span>

                        @else

                            <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-red-50 text-red-700 border border-red-200">
                                Ditolak
                            </span>

                        @endif

                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                        <div class="rounded-3xl p-4 bg-[#f7fced] border border-[#e7efd8]">
                            <p class="text-[11px] text-slate-500 font-bold uppercase">
                                Luas Lahan
                            </p>

                            <p class="text-2xl font-extrabold text-[#14280b] mt-2">
                                {{ $item['luas_lahan_hektar'] }}
                                <span class="text-sm text-slate-400">Ha</span>
                            </p>
                        </div>

                        <div class="rounded-3xl p-4 bg-white border border-[#e7efd8]">
                            <p class="text-[11px] text-slate-500 font-bold uppercase">
                                Pemilik
                            </p>

                            <p class="text-lg font-extrabold text-[#14280b] mt-2">
                                {{ $item['pemilik_lahan'] ?? '-' }}
                            </p>
                        </div>

                        <div class="rounded-3xl p-4 bg-white border border-[#e7efd8]">
                            <p class="text-[11px] text-slate-500 font-bold uppercase">
                                Lokasi
                            </p>

                            <p class="text-sm font-bold text-[#14280b] mt-2">
                                {{ $item['alamat_detail'] }}
                            </p>
                        </div>

                    </div>

                </div>

                @empty

                <div class="text-center py-10 text-gray-500">
                    Belum ada data lahan.
                </div>

                @endforelse

                </div>

        </div>

        <div class="flex justify-between mt-6">

            {{-- Tombol Sebelumnya --}}
            @if(!empty($lahan['prev_page_url']))
                <a href="{{ url()->current() }}?page={{ $lahan['current_page'] - 1 }}"
                class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300">
                    ← Sebelumnya
                </a>
            @else
                <div></div>
            @endif

            {{-- Info halaman --}}
            <span class="text-sm text-gray-500">
                Halaman {{ $lahan['current_page'] }}
                dari {{ $lahan['last_page'] }}
            </span>

            {{-- Tombol Selanjutnya --}}
            @if(!empty($lahan['next_page_url']))
                <a href="{{ url()->current() }}?page={{ $lahan['current_page'] + 1 }}"
                class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700">
                    Selanjutnya →
                </a>
            @endif

        </div>

        <div class="glass-card rounded-[28px] overflow-hidden">
            <div class="px-5 sm:px-6 py-4 border-b border-[#e7efd8] flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-[#14280b] text-base">Riwayat Produksi Terakhir</h3>
                    <p class="text-xs text-slate-500 mt-1">Catatan hasil panen yang pernah dikirim.</p>
                </div>
            </div>
            @if(count($riwayat) > 0)

                <div class="divide-y divide-[#e7efd8]">

                    @foreach($riwayat['data'] ?? [] as $item)

                    <div class="px-5 sm:px-6 py-4 flex justify-between items-center">

                        <div>

                            <p class="font-semibold text-[#14280b]">
                                {{ $item['lahan']['nama_lahan'] ?? '-' }}
                            </p>

                            <p class="text-xs text-slate-500 mt-1">
                                {{ \Carbon\Carbon::parse($item['tanggal_panen'])->format('d M Y') }}
                            </p>

                        </div>

                        <div class="text-right">

                            <p class="font-bold text-green-700">
                                {{ $item['hasil_panen'] }} Ton
                            </p>

                            @if($item['status_verifikasi'] == 'DITERIMA')

                                <span class="text-xs text-green-600">
                                    Diterima
                                </span>

                            @elseif($item['status_verifikasi'] == 'PENDING')

                                <span class="text-xs text-yellow-600">
                                    Pending
                                </span>

                            @else

                                <span class="text-xs text-red-600">
                                    Ditolak
                                </span>

                            @endif

                        </div>

                    </div>

                    @endforeach

                </div>

            @else

                <div class="p-8 text-center">

                    <div class="w-14 h-14 rounded-3xl bg-[#edf8dc] text-[#3E7D00] flex items-center justify-center mx-auto mb-3">

                        📄

                    </div>

                    <p class="text-sm text-slate-500 italic">

                        Belum ada riwayat panen yang diinput.

                    </p>

                </div>

            @endif
        </div>
        <div class="flex justify-between items-center mt-6">

    {{-- Tombol Sebelumnya --}}
    @if(!empty($riwayat['prev_page_url']))
        <a href="{{ url()->current() }}?page={{ request('page', 1) }}&riwayat_page={{ $riwayat['current_page'] - 1 }}"
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
    @if(!empty($riwayat['next_page_url']))
        <a href="{{ url()->current() }}?page={{ request('page', 1) }}&riwayat_page={{ $riwayat['current_page'] + 1 }}"
           class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700">
            Selanjutnya →
        </a>
    @else
        <div></div>
    @endif

</div>
    </div>



    <div class="space-y-5">
        <div class="rounded-[28px] p-6 text-white overflow-hidden relative"
             style="background:linear-gradient(145deg,#203c10,#3E7D00); box-shadow:0 22px 60px rgba(32,60,16,.22);">
            <div class="absolute -right-12 -bottom-12 w-36 h-36 rounded-full bg-white/10"></div>
            <p class="text-[11px] text-white/65 font-bold uppercase tracking-wider">Total Produksi Tahun Ini</p>
            <p class="text-4xl font-extrabold mt-3">
                {{ number_format($totalProduksi, 2) }}
                <span class="text-sm font-semibold text-white/60">
                    Ton
                </span>
            </p>

        </div>

        <div class="glass-card rounded-[28px] p-5">
            <h3 class="font-extrabold text-[#14280b] text-sm">Catatan Pendampingan</h3>
            <p class="text-sm text-slate-500 mt-2 leading-relaxed">Hubungi petugas pendamping jika terdapat kendala pada data lahan atau laporan hasil panen.</p>
        </div>
    </div>
</div>
@endsection
