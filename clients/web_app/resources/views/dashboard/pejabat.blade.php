@extends('layouts.app')

@section('title', 'Dashboard Pejabat')

@section('content')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-7">
    <div>
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-bold bg-[#edf8dc] text-[#3E7D00] border border-[#dfeccc]">Dashboard Pejabat</span>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#14280b] mt-3 tracking-tight">Statistik Eksekutif</h1>
        <p class="text-sm text-slate-500 mt-1">Analisis Data Komoditas Daerah — {{ now()->translatedFormat('F Y') }}</p>
    </div>

    <button onclick="window.print()" class="inline-flex items-center justify-center px-5 py-3 rounded-2xl text-sm font-bold border border-[#dfeccc] text-[#3E7D00] bg-white hover:bg-[#f7fced] transition">
        Cetak Laporan
    </button>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-6 items-stretch">

    {{-- Total Produksi --}}
    <a href="{{ route('produksi.kecamatan') }}"
       class="glass-card rounded-[26px] p-5 flex flex-col justify-between min-h-[180px] hover:scale-[1.02] transition">

        <div>
            <p class="text-[11px] text-slate-500 font-bold uppercase tracking-wide">
                Total Produksi
            </p>

            <p class="text-3xl font-extrabold text-[#14280b] mt-3">
                {{ number_format($produksiPejabat ?? 0, 2) }}
                <span class="text-sm text-slate-400">Ton</span>
            </p>
        </div>

        <div>
            <div class="w-full bg-slate-100 h-2 mt-4 rounded-full overflow-hidden">
                <div class="bg-[#3E7D00] h-full w-[75%]"></div>
            </div>

            <p class="text-xs text-green-700 mt-3 font-medium">
                Klik untuk detail per kecamatan →
            </p>
        </div>

    </a>

    {{-- Lahan Aktif --}}
    <a href="{{ route('lahan.kecamatan') }}"
       class="glass-card rounded-[26px] p-5 flex flex-col justify-between min-h-[180px] hover:scale-[1.02] transition">

        <div>
            <p class="text-[11px] text-slate-500 font-bold uppercase tracking-wide">
                Lahan Aktif
            </p>

            <p class="text-3xl font-extrabold text-[#14280b] mt-3">
                {{ number_format($totalLahan ?? 0, 2) }}
                <span class="text-sm text-slate-400">Ha</span>
            </p>
        </div>

        <div>
            <div class="w-full bg-slate-100 h-2 mt-4 rounded-full overflow-hidden">
                <div class="bg-emerald-500 h-full w-[60%]"></div>
            </div>

            <p class="text-xs text-green-700 mt-3 font-medium">
                Klik untuk luas lahan per kecamatan →
            </p>
        </div>

    </a>

</div>

    @php
    $bulanLabel = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agt',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];

    $maxProduksi = max($produksiBulanan ?: [1]);
    @endphp

    <div class="glass-card rounded-[28px] p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-6">
            <div>
                <h3 class="text-base font-extrabold text-[#14280b]">Tren Produksi Bulanan</h3>
                <p class="text-xs text-slate-500 mt-1">Visualisasi cepat untuk pembacaan eksekutif.</p>
            </div>
        </div>

        <div class="h-52 flex items-end gap-2 sm:gap-3 overflow-x-auto pb-2">

        @foreach($produksiBulanan as $bulan => $total)

            @php
                $height = $maxProduksi > 0
                    ? max(20, ($total / $maxProduksi) * 145)
                    : 20;
            @endphp

            <div class="min-w-10 flex-1 flex flex-col items-center gap-2">
                <div class="w-full rounded-t-2xl transition hover:opacity-80 flex items-start justify-center text-white text-[10px] font-bold pt-1"
                    style="height:{{ $height }}px; background:linear-gradient(180deg,#65bd00,#3E7D00);">

                    {{ number_format($total, 0) }}
                </div>

                <span class="text-[10px] text-slate-400 font-bold uppercase">
                    {{ $bulanLabel[$bulan] ?? '-' }}
                </span>

            </div>

        @endforeach

    </div>
@endsection
