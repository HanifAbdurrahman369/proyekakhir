@extends('layouts.app')

@section('title', 'Dashboard Pejabat')

@section('content')

{{-- Page Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-lg font-bold text-primary-900">Statistik Eksekutif</h1>
        <p class="text-xs text-gray-400 mt-0.5">Analisis Data Komoditas Daerah &mdash; {{ now()->translatedFormat('F Y') }}</p>
    </div>
    <button onclick="window.print()" class="text-xs font-semibold text-primary-700 px-4 py-2 border border-primary-200 rounded-lg hover:bg-primary-50 transition">
        Cetak Laporan
    </button>
</div>

{{-- Stats Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <div class="bg-white rounded-xl border border-primary-100 p-4">
        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-2">Total Produksi (Ton)</p>
        <p class="text-2xl font-bold text-primary-900">12.450</p>
        <div class="w-full bg-gray-100 h-1.5 mt-3 rounded-full overflow-hidden"><div class="bg-emerald-500 h-full w-[75%]"></div></div>
    </div>
    <div class="bg-white rounded-xl border border-primary-100 p-4">
        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-2">Lahan Aktif (Ha)</p>
        <p class="text-2xl font-bold text-primary-900">4.218</p>
        <div class="w-full bg-gray-100 h-1.5 mt-3 rounded-full overflow-hidden"><div class="bg-blue-500 h-full w-[60%]"></div></div>
    </div>
    <div class="bg-white rounded-xl border border-primary-100 p-4">
        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-2">Komoditas Unggul</p>
        <p class="text-xl font-bold text-primary-900 mt-1 uppercase">Padi Sawah</p>
    </div>
    <div class="bg-white rounded-xl border border-primary-100 p-4">
        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-2">Prediksi Panen</p>
        <p class="text-2xl font-bold text-emerald-600">Surplus</p>
    </div>
</div>

<div class="bg-white rounded-xl border border-primary-100 p-6 mb-4">
    <h3 class="text-sm font-semibold text-primary-900 mb-4">Tren Produksi Bulanan</h3>
    <div class="h-40 flex items-end gap-2">
        @foreach (['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'] as $m)
            <div class="flex-1 flex flex-col items-center gap-2">
                <div class="w-full bg-primary-100 rounded-t-lg transition hover:bg-primary-500" style="height: {{ rand(30, 100) }}px"></div>
                <span class="text-[9px] text-gray-400 font-bold uppercase">{{ $m }}</span>
            </div>
        @endforeach
    </div>
</div>

@endsection