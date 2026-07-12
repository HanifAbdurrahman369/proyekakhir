@extends('layouts.app')

@section('title', 'Dashboard Pejabat')

@section('content')
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-7">
    <div>
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-bold bg-[#ecfdf5] text-[#047857] border border-[#d1fae5]">Dashboard Pejabat</span>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#022c22] mt-3 tracking-tight">Statistik Eksekutif</h1>
        <p class="text-sm text-slate-500 mt-1">Analisis Data Komoditas Daerah — {{ now()->translatedFormat('F Y') }}</p>
    </div>

    <a href="{{ route('pejabat.cetak') }}" target="_blank"
       class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl text-sm font-bold text-white bg-gradient-to-r from-[#047857] to-[#065f46] shadow-md hover:scale-105 transition">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
        </svg>
        Cetak Laporan
    </a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-6 items-stretch">

    {{-- Total Produksi --}}
    <a href="{{ route('produksi.kecamatan') }}"
       class="glass-card rounded-[26px] p-5 flex flex-col justify-between min-h-[180px] hover:scale-[1.02] transition">

        <div>
            <p class="text-[11px] text-slate-500 font-bold uppercase tracking-wide">
                Total Produksi
            </p>

            <p class="text-3xl font-extrabold text-[#022c22] mt-3">
                {{ number_format($produksiPejabat ?? 0, 2) }}
                <span class="text-sm text-slate-400">Ton</span>
            </p>
        </div>

        <div>
            <div class="w-full bg-slate-100 h-2 mt-4 rounded-full overflow-hidden">
                <div class="bg-[#047857] h-full w-[75%]"></div>
            </div>

            <p class="text-xs text-emerald-700 mt-3 font-medium">
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

            <p class="text-3xl font-extrabold text-[#022c22] mt-3">
                {{ number_format($totalLahan ?? 0, 2) }}
                <span class="text-sm text-slate-400">Ha</span>
            </p>
        </div>

        <div>
            <div class="w-full bg-slate-100 h-2 mt-4 rounded-full overflow-hidden">
                <div class="bg-emerald-500 h-full w-[60%]"></div>
            </div>

            <p class="text-xs text-emerald-700 mt-3 font-medium">
                Klik untuk luas lahan per kecamatan →
            </p>
        </div>

    </a>

</div>

@php
$maxProduksi = 1.0;
foreach($produksiKecamatan as $item) {
    $val = (float)($item['produksi_pejabat'] ?? 0);
    if($val > $maxProduksi) $maxProduksi = $val;
}
@endphp

{{-- Tren Produksi per Kecamatan --}}
<div class="glass-card rounded-[28px] p-5 sm:p-6 flex flex-col justify-between">
    <div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-6">
            <div>
                <h3 class="text-base font-extrabold text-[#022c22]">Tren Produksi per Kecamatan</h3>
                <p class="text-xs text-slate-500 mt-1">Produksi komoditas per kecamatan dalam 1 tahun.</p>
            </div>
        </div>

        <div class="space-y-4">
            @forelse($produksiKecamatan as $index => $item)
                @php
                    $total = (float)($item['produksi_pejabat'] ?? 0);
                    $percent = $maxProduksi > 0 ? ($total / $maxProduksi) * 100 : 0;
                    $isHidden = $index >= 5 ? 'hidden kecamatan-extra-item' : '';
                @endphp
                <div class="space-y-2 {{ $isHidden }}">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-bold text-slate-700">{{ $item['nama_kecamatan'] ?? '-' }}</span>
                        <span class="text-sm font-extrabold text-[#047857]">{{ number_format($total, 2) }} Ton</span>
                    </div>
                    <div class="w-full bg-slate-100 h-3 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-[#047857] to-[#065f46] rounded-full transition-all duration-500" style="width: {{ $percent }}%"></div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8 text-slate-400">Belum ada data produksi.</div>
            @endforelse
        </div>

        @if(count($produksiKecamatan) > 5)
            <div class="text-center mt-6">
                <button id="toggleKecamatanBtn" onclick="toggleExtraKecamatan()" class="px-5 py-2.5 rounded-xl text-xs font-bold text-[#047857] bg-[#ecfdf5] hover:bg-[#d1fae5] transition-all border border-[#d1fae5]">
                    Tampilkan Seluruh Kecamatan
                </button>
            </div>
        @endif
    </div>
</div>

<script>
function toggleExtraKecamatan() {
    const extraItems = document.querySelectorAll('.kecamatan-extra-item');
    const btn = document.getElementById('toggleKecamatanBtn');
    if (extraItems.length === 0) return;
    
    const isHidden = extraItems[0].classList.contains('hidden');
    extraItems.forEach(el => {
        if (isHidden) {
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    });
    
    if (isHidden) {
        btn.innerText = 'Sembunyikan Kecamatan';
    } else {
        btn.innerText = 'Tampilkan Seluruh Kecamatan';
    }
}
</script>
@endsection
