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

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="glass-card rounded-[26px] p-5">
        <p class="text-[11px] text-slate-500 font-bold uppercase tracking-wide">Total Produksi</p>
        <p class="text-3xl font-extrabold text-[#14280b] mt-3">12.450 <span class="text-sm text-slate-400">Ton</span></p>
        <div class="w-full bg-slate-100 h-2 mt-4 rounded-full overflow-hidden"><div class="bg-[#3E7D00] h-full w-[75%]"></div></div>
    </div>

    <div class="glass-card rounded-[26px] p-5">
        <p class="text-[11px] text-slate-500 font-bold uppercase tracking-wide">Lahan Aktif</p>
        <p class="text-3xl font-extrabold text-[#14280b] mt-3">4.218 <span class="text-sm text-slate-400">Ha</span></p>
        <div class="w-full bg-slate-100 h-2 mt-4 rounded-full overflow-hidden"><div class="bg-emerald-500 h-full w-[60%]"></div></div>
    </div>

    <div class="glass-card rounded-[26px] p-5">
        <p class="text-[11px] text-slate-500 font-bold uppercase tracking-wide">Komoditas Unggul</p>
        <p class="text-2xl font-extrabold text-[#14280b] mt-4">Padi Sawah</p>
        <p class="text-xs text-slate-500 mt-2">Dominan berdasarkan rekap produksi.</p>
    </div>

    <div class="rounded-[26px] p-5 text-white" style="background:linear-gradient(145deg,#203c10,#3E7D00);">
        <p class="text-[11px] text-white/65 font-bold uppercase tracking-wide">Prediksi Panen</p>
        <p class="text-3xl font-extrabold mt-4 text-[#B7F43B]">Surplus</p>
        <p class="text-xs text-white/65 mt-2">Estimasi tren berjalan positif.</p>
    </div>
</div>

<div class="glass-card rounded-[28px] p-5 sm:p-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-6">
        <div>
            <h3 class="text-base font-extrabold text-[#14280b]">Tren Produksi Bulanan</h3>
            <p class="text-xs text-slate-500 mt-1">Visualisasi cepat untuk pembacaan eksekutif.</p>
        </div>
    </div>

    <div class="h-52 flex items-end gap-2 sm:gap-3 overflow-x-auto pb-2">
        @foreach (['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'] as $m)
            <div class="min-w-[42px] flex-1 flex flex-col items-center gap-2">
                <div class="w-full rounded-t-2xl transition hover:opacity-80" style="height: {{ rand(45, 145) }}px; background:linear-gradient(180deg,#65bd00,#3E7D00);"></div>
                <span class="text-[10px] text-slate-400 font-bold uppercase">{{ $m }}</span>
            </div>
        @endforeach
    </div>
</div>
@endsection
