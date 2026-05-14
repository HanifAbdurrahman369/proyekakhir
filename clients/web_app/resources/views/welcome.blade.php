@extends('layouts.public')

@section('content')

<div class="relative w-full h-screen font-['Poppins']">
    
    <img src="{{ asset('storage/bg.png') }}" class="absolute inset-0 w-full h-full object-cover" alt="Background Lahan">
    
    <div class="absolute inset-0 bg-gradient-to-b from-black/80 via-black/50 to-emerald-950/90"></div>
    
    <div class="relative z-10 flex h-full flex-col items-center justify-center text-center px-6">
        <div class="mb-8 px-5 py-2 rounded-full border border-emerald-500/30 bg-emerald-900/50 backdrop-blur-sm flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="text-emerald-300 text-[10px] sm:text-xs font-bold tracking-[0.2em] uppercase">Platform Geospasial Pertanian</span>
        </div>

        <h1 class="text-6xl sm:text-7xl md:text-9xl font-black tracking-tight mb-4">
            <span class="text-white drop-shadow-lg">SIG</span><span class="text-emerald-400 drop-shadow-[0_0_20px_rgba(52,211,153,0.4)]"> - PALA</span>
        </h1>
        
        <div class="w-24 h-1.5 bg-emerald-500 rounded-full mb-8 shadow-[0_0_15px_rgba(16,185,129,0.5)]"></div>

        <p class="text-emerald-50 text-sm sm:text-lg md:text-xl max-w-2xl font-medium tracking-wide leading-relaxed drop-shadow-md">
            SISTEM INFORMASI GEOGRAFIS PRODUKTIVITAS <br class="hidden sm:block"> PADA LAHAN RAWA BARITO KUALA
        </p>
    </div>

    <div class="absolute bottom-10 left-1/2 -translate-x-1/2 flex flex-col items-center gap-3 animate-bounce cursor-pointer opacity-80 hover:opacity-100 transition-opacity">
        <span class="text-emerald-200 text-xs font-bold tracking-[0.2em] uppercase">Jelajahi</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
        </svg>
    </div>
</div>

<div class="w-full py-24 px-6 md:px-12 lg:px-20 bg-white flex justify-center font-['Poppins']">
    <div class="max-w-7xl w-full grid grid-cols-1 lg:grid-cols-2 gap-16 lg:gap-24 items-center">
        
        <div class="space-y-8">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-1 h-6 bg-emerald-500 rounded-full"></div>
                    <p class="text-emerald-600 text-xs font-bold tracking-[0.2em] uppercase">Layanan Informasi Publik</p>
                </div>
                <h2 class="text-slate-900 text-4xl sm:text-5xl font-extrabold leading-[1.15] tracking-tight">
                    Transparansi Data & <br>
                    <span class="text-emerald-600">Visualisasi Lahan Rawa</span>
                </h2>
            </div>
            
            <p class="text-slate-500 text-lg leading-relaxed font-medium">
                SIG-PALA adalah platform geospasial yang dirancang khusus untuk memetakan ketersediaan lahan pertanian rawa di Kabupaten Barito Kuala secara akurat, modern, dan transparan bagi publik.
            </p>

            <div class="grid grid-cols-3 gap-6 pt-6 border-t border-slate-100">
                <div>
                    <p class="text-3xl font-black text-emerald-600 mb-1">17+</p>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider leading-snug">Kecamatan<br>Terpetakan</p>
                </div>
                <div>
                    <p class="text-3xl font-black text-emerald-600 mb-1">Real<span class="text-emerald-400">-time</span></p>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider leading-snug">Pembaruan<br>Data API</p>
                </div>
                <div>
                    <p class="text-3xl font-black text-emerald-600 mb-1">Open</p>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider leading-snug">Akses Data<br>Publik</p>
                </div>
            </div>
        </div>
        
        <div class="flex flex-col gap-5">
            <div class="flex items-start gap-6 p-6 bg-white rounded-3xl shadow-[0_8px_30px_rgba(0,0,0,0.04)] border border-slate-100 hover:-translate-y-1.5 hover:shadow-[0_15px_40px_rgba(16,185,129,0.1)] transition-all duration-300">
                <div class="w-14 h-14 shrink-0 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A2 2 0 013 15.382V7.618a2 2 0 011.096-1.789L9 3m0 17l6-3m-6 3V3m6 14l5.447 2.724A2 2 0 0021 17.618V9.882a2 2 0 00-1.096-1.789L15 3m0 14V3" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800 text-lg mb-1.5">Peta Interaktif</h4>
                    <p class="text-sm text-slate-500 font-medium leading-relaxed">Eksplorasi batas wilayah hingga detail blok lahan rawa secara visual dan real-time.</p>
                </div>
            </div>
            
            <div class="flex items-start gap-6 p-6 bg-white rounded-3xl shadow-[0_8px_30px_rgba(0,0,0,0.04)] border border-slate-100 hover:-translate-y-1.5 hover:shadow-[0_15px_40px_rgba(16,185,129,0.1)] transition-all duration-300">
                <div class="w-14 h-14 shrink-0 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 00-2-2H5a2 2 0 00-2 2v10m0 0h10m-10 0V5a2 2 0 012-2h2a2 2 0 012 2v14m0 0h10m-10 0V7a2 2 0 012-2h2a2 2 0 012 2v14m0 0h10" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800 text-lg mb-1.5">Analisis Statistik</h4>
                    <p class="text-sm text-slate-500 font-medium leading-relaxed">Data akumulasi luasan lahan sawah yang disajikan secara transparan dengan grafik visual.</p>
                </div>
            </div>

            <div class="flex items-start gap-6 p-6 bg-white rounded-3xl shadow-[0_8px_30px_rgba(0,0,0,0.04)] border border-slate-100 hover:-translate-y-1.5 hover:shadow-[0_15px_40px_rgba(16,185,129,0.1)] transition-all duration-300">
                <div class="w-14 h-14 shrink-0 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800 text-lg mb-1.5">Informasi Produktivitas</h4>
                    <p class="text-sm text-slate-500 font-medium leading-relaxed">Lacak potensi dan realisasi produktivitas lahan rawa berdasarkan data akurat (Ton/Ha).</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="w-full py-24 px-6 md:px-12 lg:px-20 bg-slate-50/50 flex flex-col items-center font-['Poppins']">
    
    <div class="max-w-7xl w-full flex flex-col md:flex-row justify-between items-end gap-6 mb-10">
        <div class="space-y-3">
            <div class="flex items-center gap-3">
                <div class="w-1 h-5 bg-emerald-500 rounded-full"></div>
                <p class="text-emerald-600 text-[11px] font-bold tracking-[0.2em] uppercase">Peta Geospasial</p>
            </div>
            <h2 class="text-slate-900 text-4xl sm:text-5xl font-extrabold tracking-tight">
                Map <span class="text-emerald-600">Interaktif</span>
            </h2>
        </div>
        <div class="md:text-right max-w-sm">
            <p class="text-slate-500 text-sm font-medium leading-relaxed">
                Klik pada blok lahan di peta untuk melihat detail informasi wilayah, pemilik, dan data produktivitas terkait.
            </p>
        </div>
    </div>

    <div class="relative w-full max-w-7xl h-[650px] rounded-[3rem] shadow-[0_20px_60px_rgba(0,0,0,0.12)] overflow-hidden bg-white">
        
        <div id="map" class="w-full h-full z-0"></div>

        <div id="side-panel" class="absolute top-0 right-[-420px] w-[380px] h-full bg-white/95 backdrop-blur-xl z-[1000] shadow-[-10px_0_40px_rgba(0,0,0,0.1)] transition-all duration-500 ease-in-out p-8 overflow-y-auto border-l border-slate-100">
            <div class="flex justify-between items-center mb-8">
                <h3 class="text-xl font-bold text-slate-800 tracking-tight">Detail Lahan</h3>
                <button onclick="closeSidePanel()" class="w-10 h-10 flex items-center justify-center rounded-2xl bg-slate-50 hover:bg-red-50 text-slate-400 hover:text-red-500 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div id="panel-content" class="space-y-4">
                <div class="flex flex-col items-center justify-center h-40 opacity-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                    </svg>
                    <p class="text-slate-500 font-medium text-center text-sm">
                        Pilih area lahan pada peta<br>untuk melihat informasi
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@include('statistik', ['showTable' => false])


<script src="{{ asset('js/map-sigpala.js') }}"></script>

@endsection