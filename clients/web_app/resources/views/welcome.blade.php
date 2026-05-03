@extends('layouts.public')

@section('content')

<!-- ============================================== -->
<!-- 1. HERO SECTION -->
<!-- ============================================== -->
<div class="relative w-full h-screen">
    <img src="{{ asset('storage/bg.png') }}" class="absolute inset-0 w-full h-full object-cover" alt="bg">
    <div class="absolute inset-0 bg-gradient-to-b from-black/60 to-black/70"></div>
    <div class="relative z-10 flex h-full flex-col items-center justify-center text-center px-6">
        <p class="text-white text-2xl tracking-wide">SELAMAT DATANG DI</p>
        <p class="text-white text-5xl font-extrabold tracking-wide drop-shadow-lg">SIG-PALA</p>
        <p class="text-white text-lg max-w-xl mt-3 opacity-90 leading-relaxed">
            SISTEM INFORMASI GEOGRAFIS PRODUKTIVITAS PADA LAHAN RAWA BATOLA
        </p>
    </div>
</div>

<!-- ============================================== -->
<!-- 2. LAYANAN INFORMASI PUBLIK -->
<!-- ============================================== -->
<div class="flex w-full py-20 px-4 justify-center bg-slate-50/50">
    <div class="max-w-6xl w-full grid md:grid-cols-2 gap-12 items-center">
        <div class="space-y-6">
            <div class="inline-block px-4 py-1.5 bg-primary-100 text-primary-700 rounded-full text-xs font-bold uppercase tracking-widest">
                Layanan Informasi Publik
            </div>
            <h2 class="text-slate-800 text-4xl font-bold leading-tight">
                Transparansi Data & Visualisasi <br>
                <span class="text-primary-600">Luas Lahan Rawa</span>
            </h2>
            <p class="text-slate-600 text-lg leading-relaxed">
                SIG-PALA merupakan platform geospasial mutakhir yang dirancang untuk memetakan ketersediaan lahan pertanian di Kabupaten Barito Kuala. Melalui pendekatan teknologi digital, kami menyajikan data akurat mengenai distribusi lahan rawa untuk mendukung pengambilan keputusan yang lebih cerdas bagi publik, petani, dan pemangku kepentingan.
            </p>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="p-6 bg-white rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition group">
                <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center mb-4 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A2 2 0 013 15.382V7.618a2 2 0 011.096-1.789L9 3m0 17l6-3m-6 3V3m6 14l5.447 2.724A2 2 0 0021 17.618V9.882a2 2 0 00-1.096-1.789L15 3m0 14V3" />
                    </svg>
                </div>
                <h4 class="font-bold text-slate-800 mb-2">Peta Interaktif</h4>
                <p class="text-sm text-slate-500 leading-snug">Eksplorasi batas wilayah hingga detail blok lahan secara visual.</p>
            </div>
            <div class="p-6 bg-white rounded-2xl shadow-sm border border-slate-100 hover:shadow-md transition group">
                <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center mb-4 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 00-2-2H5a2 2 0 00-2 2v10m0 0h10m-10 0V5a2 2 0 012-2h2a2 2 0 012 2v14m0 0h10m-10 0V7a2 2 0 012-2h2a2 2 0 012 2v14m0 0h10" />
                    </svg>
                </div>
                <h4 class="font-bold text-slate-800 mb-2">Analisis Statistik</h4>
                <p class="text-sm text-slate-500 leading-snug">Data akumulasi lahan sawah yang disajikan secara transparan.</p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- 3. MAP INTERAKTIF SECTION -->
<!-- ============================================== -->
<div class="flex w-full py-12 px-4 flex-col items-center gap-8">
    <p class="text-slate-800 text-3xl text-center font-semibold tracking-wide">MAP INTERAKTIF</p>

    <div class="relative w-full max-w-6xl h-[600px] rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.15)] overflow-hidden bg-white border border-gray-100">
        <div id="map" class="w-full h-full z-0"></div>

        <div id="side-panel" class="absolute top-0 right-[-400px] w-[360px] h-full bg-white/90 backdrop-blur-xl z-[1000] shadow-[0_10px_40px_rgba(0,0,0,0.2)] transition-all duration-500 ease-in-out p-6 overflow-y-auto border-l border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-semibold text-slate-800 tracking-wide">Detail Lahan Sawah</h3>
                <button onclick="closeSidePanel()" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-red-50 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 hover:text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div id="panel-content" class="space-y-4">
                <p class="text-gray-400 italic text-center py-12 text-sm">
                    Pilih "Detail Lahan" pada peta untuk melihat informasi
                </p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================== -->
<!-- 4. DATA STATISTIK & VISUALISASI SECTION -->
<!-- Di-load dari file partials terpisah -->
<!-- ============================================== -->
@include('statistik', ['showTable' => false])

<!-- Panggil Script Map -->
<script src="{{ asset('js/map-sigpala.js') }}"></script>

@endsection