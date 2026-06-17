@extends('layouts.app')

@section('title', 'Sebaran Lahan')

@section('content')

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
    <div class="bg-white p-6 sm:p-8 rounded-3xl shadow-lg">
        <div class="mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-primary-900">Sebaran Lahan</h1>
            <p class="text-sm sm:text-base text-gray-500 mt-2 max-w-3xl">
                Menampilkan sebaran lahan sawah dalam bentuk poligon pada peta interaktif.
            </p>
        </div>

        <div class="overflow-visible rounded-3xl border border-slate-200 shadow-sm">
            <div id="map" class="w-full h-[52vh] sm:h-[64vh] lg:h-[72vh] min-h-[420px]"></div>
        </div>
    </div>
</div>

<div id="side-panel" class="fixed top-0 right-[-110%] w-full sm:w-[420px] h-full bg-white/95 backdrop-blur-xl z-[9999] shadow-[-10px_0_40px_rgba(0,0,0,0.15)] transition-all duration-500 ease-in-out p-6 overflow-y-auto border-l border-slate-200 max-h-screen">
    <div class="flex justify-between items-start gap-4 mb-6">
        <div>
            <h3 class="text-lg font-bold text-slate-800 tracking-wide">Detail Lahan Sawah</h3>
            <p class="text-sm text-slate-500">Klik tombol "Lihat Detail Lahan" pada popup lahan untuk melihat informasi lengkap.</p>
        </div>
        <button onclick="closeSidePanel()" class="w-10 h-10 rounded-2xl bg-slate-100 hover:bg-red-100 transition flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-500 hover:text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <div id="panel-content" class="space-y-4 text-sm text-slate-700">
        <div class="flex flex-col items-center justify-center min-h-[50vh] text-center opacity-60">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 mb-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A2 2 0 013 15.382V7.618a2 2 0 011.096-1.789L9 3m0 17l6-3m-6 3V3m6 14l5.447 2.724A2 2 0 0021 17.618V9.882a2 2 0 00-1.096-1.789L15 3m0 14V3" />
            </svg>
            <p class="text-sm font-medium">Silakan pilih sebuah lahan, lalu klik "Lihat Detail Lahan" untuk menampilkan detail area.</p>
        </div>
    </div>
</div>

<script>
    window.GATEWAY_URL = "{{ env('GATEWAY_URL', 'http://127.0.0.1:8003') }}";
</script>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('js/map-sigpala.js') }}"></script>

@endsection
