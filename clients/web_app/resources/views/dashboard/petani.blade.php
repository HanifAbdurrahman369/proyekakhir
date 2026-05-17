@extends('layouts.app')

@section('title', 'Lahan Saya')

@section('content')

{{-- Page Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-lg font-bold text-primary-900">Dashboard Petani</h1>
        <p class="text-xs text-gray-400 mt-0.5">Kelola lahan dan hasil panen Anda</p>
    </div>
    <a href="{{ route('input.panen') }}"
       class="flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold px-4 py-2 rounded-lg transition">
        Lapor Hasil Panen
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-4">
        {{-- Card Lahan Utama --}}
        <div class="bg-white rounded-2xl border border-primary-100 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-primary-900 text-sm">Informasi Lahan Utama</h3>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Terverifikasi</span>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div><p class="text-[10px] text-gray-400 font-bold uppercase mb-1">Luas Lahan</p><p class="text-lg font-bold text-primary-900">1.2 Ha</p></div>
                <div><p class="text-[10px] text-gray-400 font-bold uppercase mb-1">Komoditas</p><p class="text-lg font-bold text-primary-900">Padi</p></div>
                <div><p class="text-[10px] text-gray-400 font-bold uppercase mb-1">Lokasi</p><p class="text-sm font-bold text-primary-900">Blok 4C, Kec. Suka Maju</p></div>
            </div>
        </div>

        {{-- Riwayat Panen --}}
        <div class="bg-white rounded-2xl border border-primary-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-primary-50 font-bold text-primary-900 text-sm">Riwayat Produksi Terakhir</div>
            <div class="p-5 text-center text-xs text-gray-400 italic">Belum ada riwayat panen yang diinput.</div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="bg-primary-900 rounded-2xl p-6 text-white shadow-xl">
            <p class="text-[10px] text-white/60 font-bold uppercase tracking-wider mb-1">Total Produksi Tahun Ini</p>
            <p class="text-3xl font-bold mb-4">4.8 <span class="text-sm font-normal opacity-60">Ton</span></p>
            <div class="pt-4 border-t border-white/10 flex items-center justify-between text-xs font-medium">
                <span>Peringkat Wilayah</span>
                <span class="text-emerald-400 font-bold">#3</span>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-primary-100 p-5 text-center italic text-xs text-gray-400">
            Hubungi petugas pendamping jika ada kendala data lahan.
        </div>
    </div>
</div>

@endsection