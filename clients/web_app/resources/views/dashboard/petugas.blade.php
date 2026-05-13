@extends('layouts.app')

@section('title', 'Dashboard Petugas')

@section('content')

{{-- Page Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-lg font-bold text-primary-900">Panel Petugas Lapangan</h1>
        <p class="text-xs text-gray-400 mt-0.5">
            Monitor wilayah binaan Anda — {{ now()->translatedFormat('l, d F Y') }}
        </p>
    </div>
    <a href="/peta-lahan/tambah"
       class="flex items-center gap-2 bg-primary-500 hover:bg-primary-600 text-white text-xs font-semibold px-4 py-2 rounded-lg transition shadow-sm shadow-primary-200">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
        Daftar Lahan Baru
    </a>
</div>

{{-- Stats Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <div class="bg-white rounded-xl border border-primary-100 p-4">
        <div class="w-9 h-9 rounded-lg bg-primary-100 flex items-center justify-center mb-3">
            <svg class="w-4 h-4 text-primary-700" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
        </div>
        <p class="text-xs text-gray-400 font-medium mb-1">Lahan Terdata</p>
        <p class="text-2xl font-bold text-primary-900">142</p>
    </div>

    <div class="bg-white rounded-xl border border-primary-100 p-4">
        <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center mb-3">
            <svg class="w-4 h-4 text-amber-600" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
        </div>
        <p class="text-xs text-gray-400 font-medium mb-1">Perlu Verifikasi</p>
        <p class="text-2xl font-bold text-primary-900 text-amber-600">8</p>
    </div>

    <div class="bg-white rounded-xl border border-primary-100 p-4">
        <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center mb-3">
            <svg class="w-4 h-4 text-blue-600" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3z"/></svg>
        </div>
        <p class="text-xs text-gray-400 font-medium mb-1">Petani Binaan</p>
        <p class="text-2xl font-bold text-primary-900">56</p>
    </div>

    <div class="bg-white rounded-xl border border-primary-100 p-4">
        <div class="w-9 h-9 rounded-lg bg-green-50 flex items-center justify-center mb-3">
            <svg class="w-4 h-4 text-green-600" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
        </div>
        <p class="text-xs text-gray-400 font-medium mb-1">Status Wilayah</p>
        <p class="text-sm font-bold text-emerald-600 uppercase mt-2">Sangat Baik</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
    <div class="lg:col-span-2 bg-white rounded-xl border border-primary-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-primary-50"><h3 class="text-sm font-semibold text-primary-900">Daftar Verifikasi Panen</h3></div>
        <div class="p-4 text-center text-xs text-gray-400 italic">Belum ada permintaan verifikasi hari ini.</div>
    </div>
    <div class="bg-white rounded-xl border border-primary-100 p-4 text-center">
        <p class="text-xs font-semibold text-primary-900 mb-2">Peta Cepat Wilayah</p>
        <div class="h-32 bg-gray-100 rounded-lg flex items-center justify-center text-[10px] text-gray-400 uppercase tracking-widest italic font-bold">Preview Peta</div>
    </div>
</div>

@endsection