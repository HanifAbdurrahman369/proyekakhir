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

<div class="glass-card rounded-[28px] p-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
        <div>
            <h3 class="font-extrabold text-[#14280b] text-xl">
                Informasi Lahan Utama
            </h3>

            <p class="text-xs text-slate-500 mt-1">
                Data ringkas lahan yang terdaftar pada sistem.
            </p>
        </div>
    </div>

 <div class="space-y-4">

    @forelse($lahan['data'] ?? [] as $item)

    <div class="rounded-2xl border border-[#e7efd8] p-3">

        <div class="flex justify-between items-center mb-3">

            <h3 class="font-bold text-lg text-[#14280b]">
                {{ $item['nama_lahan'] }}
            </h3>

            @if($item['status_verifikasi'] == 'DITERIMA')

                <span class="w-fit px-2.5 py-1 rounded-full text-[9px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    Terverifikasi
                </span>

            @elseif($item['status_verifikasi'] == 'PENDING')

                <span class="px-2.5 py-1 rounded-full text-[9px] font-bold bg-yellow-50 text-yellow-700 border border-yellow-200">
                    Menunggu
                </span>

            @else

                <span class="px-2.5 py-1 rounded-full text-[9px] font-bold bg-red-50 text-red-700 border border-red-200">
                    Ditolak
                </span>

            @endif

        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">

            {{-- Luas Lahan --}}
            <div class="rounded-2xl p-3 bg-[#f7fced] border border-[#e7efd8]">

                <p class="text-xs text-slate-500 font-bold uppercase tracking-wide">
                    Luas Lahan
                </p>

                <p class="text-lg font-extrabold text-[#14280b] mt-1">
                    {{ $item['luas_lahan_hektar'] }}
                    <span class="text-[10px] text-slate-400">Ha</span>
                </p>

            </div>

            {{-- Pemilik --}}
            <div class="rounded-2xl p-3 bg-white border border-[#e7efd8]">

                <p class="text-xs text-slate-500 font-bold uppercase tracking-wide">
                    Pemilik
                </p>

                <p class="text-base font-bold text-[#14280b] mt-1">
                    {{ $item['pemilik_lahan'] ?? '-' }}
                </p>

            </div>

            {{-- Lokasi lebih panjang --}}
            <div class="md:col-span-2 rounded-2xl p-3 bg-white border border-[#e7efd8]">

                <p class="text-xs text-slate-500 font-bold uppercase tracking-wide">
                    Lokasi
                </p>

                <p class="text-sm leading-4 font-semibold text-[#14280b] mt-1">
                    {{ $item['alamat_detail'] }}
                </p>

            </div>

        </div>

    </div>

    @empty

    <div class="text-center py-8 text-sm text-gray-500">
        Belum ada data lahan.
    </div>

    @endforelse

</div>


<div class="flex justify-between items-center mt-6">

    @if(!empty($lahan['prev_page_url']))
        <a href="{{ url()->current() }}?page={{ $lahan['current_page'] - 1 }}"
           class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-sm">
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
           class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700 text-sm">
            Selanjutnya →
        </a>
    @endif

</div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-10">
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
@endsection
