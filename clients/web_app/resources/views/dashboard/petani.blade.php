@php
    $totalLahan = $lahan['total'] ?? count($lahan['data'] ?? []);
    $lahanTitle = $totalLahan > 1 ? 'Lahan Bersama' : 'Lahan Saya';
@endphp

@extends('layouts.app')

@section('title', $lahanTitle)

@section('content')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-7">
    <div>
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-bold bg-[#edf8dc] text-[#3E7D00] border border-[#dfeccc]">Dashboard Petani</span>
        <h1 class="text-lg font-bold text-[#14280b] mt-2 sm:text-xl tracking-tight">{{ $lahanTitle }}</h1>
        <p class="text-xs text-slate-500 mt-0.5">Pantau data lahan, riwayat produksi, dan pelaporan hasil panen Anda.</p>
    </div>

    <div class="flex flex-wrap gap-2.5">
        <a href="{{ route('tambah.lahan') }}"
            class="btn-green inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition">
            <span>🌾</span> Tambah Lahan
        </a>

        <a href="{{ route('lapor.tanam') }}"
           class="btn-green inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition">
            <span>🌾</span> Lapor Hasil Tanam
        </a>

        <a href="{{ route('lapor.panen') }}"
           class="btn-green inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition">
            <span>🌾</span> Lapor Hasil Panen
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
    <!-- Card 1: Total Lahan Terdaftar -->
    <div class="glass-card rounded-[20px] p-5 flex items-center justify-between">
        <div>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Total Lahan Terdaftar</p>
            <p class="text-xl font-bold text-[#14280b] mt-1.5">
                {{ $lahan['total'] ?? count($lahan['data'] ?? []) }}
                <span class="text-xs font-semibold text-slate-500">Lahan</span>
            </p>
            <p class="text-[9px] text-slate-500 mt-1">Terdaftar pada sistem</p>
        </div>
        <div class="w-10 h-10 rounded-xl bg-[#edf8dc] text-[#3E7D00] flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
        </div>
    </div>

    <!-- Card 2: Total Produksi Tahun Ini -->
    <div class="rounded-[20px] p-5 text-white overflow-hidden relative flex items-center justify-between"
         style="background:linear-gradient(145deg,#203c10,#3E7D00); box-shadow:0 10px 25px rgba(32,60,16,.15);">
        <div class="absolute -right-6 -bottom-6 w-20 h-20 rounded-full bg-white/10"></div>
        <div class="relative z-10">
            <p class="text-[10px] text-white/75 font-bold uppercase tracking-wider">Total Produksi Tahun Ini</p>
            <p class="text-xl font-bold text-white mt-1.5">
                {{ number_format($totalProduksi, 2) }}
                <span class="text-xs font-semibold text-white/70">Ton</span>
            </p>
            <p class="text-[9px] text-white/65 mt-1">Akumulasi hasil panen</p>
        </div>
        <div class="relative z-10 w-10 h-10 rounded-xl bg-white/20 text-white flex items-center justify-center shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
            </svg>
        </div>
    </div>

    <!-- Card 3: Catatan Pendampingan -->
    <div class="glass-card rounded-[20px] p-5 flex items-center justify-between">
        <div>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Catatan Pendampingan</p>
            <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">
                Hubungi petugas jika ada kendala data lahan atau laporan hasil panen.
            </p>
        </div>
        <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center shrink-0 ml-3">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
    </div>
</div>

<div class="glass-card rounded-[20px] p-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
        <div>
            <h3 class="font-bold text-[#14280b] text-base">
                Informasi Lahan Utama
            </h3>

            <p class="text-xs text-slate-500 mt-0.5">
                Data ringkas lahan yang terdaftar pada sistem.
            </p>
        </div>
    </div>

 <div class="space-y-4">

    @forelse($lahan['data'] ?? [] as $item)

    <div class="rounded-xl border border-[#e7efd8] p-3">

        <div class="flex justify-between items-center mb-3">

            <h3 class="font-semibold text-sm text-[#14280b]">
                {{ $item['nama_lahan'] }}
            </h3>

            @if($item['status_verifikasi'] == 'DITERIMA')

                <span class="w-fit px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    Terverifikasi
                </span>

            @elseif($item['status_verifikasi'] == 'PENDING')

                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-yellow-50 text-yellow-700 border border-yellow-200">
                    Menunggu
                </span>

            @else

                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-red-50 text-red-700 border border-red-200">
                    Ditolak
                </span>

            @endif

        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-2.5">

            {{-- Luas Lahan --}}
            <div class="rounded-xl p-2.5 bg-[#f7fced] border border-[#e7efd8]">

                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">
                    Luas Lahan
                </p>

                <p class="text-xs font-bold text-[#14280b] mt-0.5">
                    {{ $item['luas_lahan_hektar'] }}
                    <span class="text-[9px] text-slate-400">Ha</span>
                </p>

            </div>

            {{-- Pemilik --}}
            <div class="rounded-xl p-2.5 bg-white border border-[#e7efd8]">

                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">
                    Pemilik
                </p>

                <p class="text-xs font-semibold text-[#14280b] mt-0.5">
                    {{ $item['pemilik_lahan'] ?? '-' }}
                </p>

            </div>

            {{-- Lokasi lebih panjang --}}
            <div class="md:col-span-2 rounded-xl p-2.5 bg-white border border-[#e7efd8]">

                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">
                    Lokasi
                </p>

                <p class="text-xs leading-4 font-medium text-[#14280b] mt-0.5">
                    {{ $item['alamat_detail'] }}
                </p>

            </div>

            {{-- Catatan Verifikasi & Alasan Penolakan --}}
            @if(($item['status_verifikasi'] ?? '') === 'DITOLAK')
                <div class="md:col-span-2 rounded-xl p-2.5 bg-white border border-[#e7efd8]">
                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">
                        Catatan Verifikasi
                    </p>
                    <p class="text-xs font-medium text-[#14280b] mt-0.5 leading-relaxed">
                        {{ $item['catatan_verifikasi'] ?? '-' }}
                    </p>
                </div>

                <div class="md:col-span-2 rounded-xl p-2.5 bg-red-50 border border-red-200">
                    <p class="text-[9px] text-red-500 font-bold uppercase tracking-wider">
                        Alasan Penolakan
                    </p>
                    <p class="text-xs text-red-700 mt-0.5 leading-relaxed">
                        {{ $item['alasan_penolakan'] ?? 'Petugas belum menambahkan alasan penolakan.' }}
                    </p>
                    <div class="mt-2">
                        <a href="{{ route('lahan.edit', $item['id']) }}"
                           class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg bg-white text-red-700 border border-red-200 text-[10px] font-bold hover:bg-red-700 hover:text-white transition-all duration-300 shadow-sm hover:shadow">
                            Perbaiki Pengajuan
                        </a>
                    </div>
                </div>
            @else
                <div class="md:col-span-4 rounded-xl p-2.5 bg-white border border-[#e7efd8]">
                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">
                        Catatan Verifikasi
                    </p>
                    <p class="text-xs font-medium text-[#14280b] mt-0.5 leading-relaxed">
                        {{ $item['catatan_verifikasi'] ?? 'Belum ada catatan verifikasi.' }}
                    </p>
                </div>
            @endif

        </div>

    </div>

    @empty

    <div class="text-center py-8 text-xs text-gray-500">
        Belum ada data lahan.
    </div>

    @endforelse

 </div>


 <div class="flex justify-between items-center mt-6">

    @if(!empty($lahan['prev_page_url']))
        <a href="{{ url()->current() }}?page={{ $lahan['current_page'] - 1 }}"
           class="px-3.5 py-1.5 rounded-xl bg-gray-200 hover:bg-gray-300 text-xs font-semibold">
            ← Sebelumnya
        </a>
    @else
        <div></div>
    @endif

    <span class="text-xs text-gray-500">
        Halaman {{ $lahan['current_page'] }}
        dari {{ $lahan['last_page'] }}
    </span>

    @if(!empty($lahan['next_page_url']))
        <a href="{{ url()->current() }}?page={{ $lahan['current_page'] + 1 }}"
           class="px-3.5 py-1.5 rounded-xl bg-green-600 text-white hover:bg-green-700 text-xs font-semibold">
            Selanjutnya →
        </a>
    @endif

 </div>

</div>
@endsection
