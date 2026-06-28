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
    <style>
        .public-priority-map-grid {
            display: grid;
            min-height: 640px;
        }

        .public-priority-map-canvas {
            height: min(560px, 68vh);
            min-height: 430px;
        }

        .public-priority-map-panel {
            min-height: 520px;
        }

        @media (min-width: 1024px) {
            .public-priority-map-grid {
                grid-template-columns: minmax(0, 1.45fr) minmax(360px, .95fr);
            }

            .public-priority-map-left {
                border-right: 1px solid #ded5c7;
            }
        }

        @media (max-width: 640px) {
            .public-priority-map-grid {
                min-height: 0;
            }

            .public-priority-map-canvas {
                height: 54vh;
                min-height: 360px;
            }
        }
    </style>
    
    <div class="max-w-7xl w-full flex flex-col md:flex-row justify-between items-end gap-6 mb-8">
        <div class="space-y-3 max-w-2xl">
            <div class="flex items-center gap-3">
                <div class="w-1 h-5 bg-emerald-500 rounded-full"></div>
                <p class="text-emerald-600 text-[11px] font-bold tracking-[0.2em] uppercase">Peta Geospasial</p>
            </div>
            <h2 class="text-slate-900 text-4xl sm:text-5xl font-extrabold tracking-tight">
                Map <span class="text-emerald-600">Interaktif</span>
            </h2>
            <p class="text-slate-500 text-sm sm:text-base font-medium leading-relaxed">
                Peta ini membantu masyarakat melihat sebaran lahan sawah, batas kecamatan, dan gambaran produktivitas wilayah Barito Kuala secara mudah. Warna kecamatan mengikuti tingkat produktivitas dari data yang tersedia, sedangkan titik hijau menandai lokasi lahan sawah yang dapat dibuka untuk melihat detailnya.
            </p>
        </div>
        <div class="md:text-right max-w-sm">
            <p class="text-slate-500 text-sm font-medium leading-relaxed">
                Klik area kecamatan untuk rekap produktivitas, atau klik titik lahan untuk melihat informasi lahan sawah.
            </p>
        </div>
    </div>

    <div class="max-w-7xl w-full grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)]">
            <p class="text-[10px] font-extrabold tracking-[0.22em] uppercase text-emerald-600 mb-2">Warna Wilayah</p>
            <p class="text-sm font-medium leading-relaxed text-slate-500">Area kecamatan diberi warna berdasarkan produktivitas agregat yang dihitung dari data backend GIS.</p>
        </div>
        <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)]">
            <p class="text-[10px] font-extrabold tracking-[0.22em] uppercase text-emerald-600 mb-2">Titik Lahan</p>
            <p class="text-sm font-medium leading-relaxed text-slate-500">Titik hijau menunjukkan koordinat lahan sawah sehingga lokasi tetap terlihat walau peta sedang diperbesar jauh.</p>
        </div>
        <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)]">
            <p class="text-[10px] font-extrabold tracking-[0.22em] uppercase text-emerald-600 mb-2">Detail Informasi</p>
            <p class="text-sm font-medium leading-relaxed text-slate-500">Panel detail menampilkan data kecamatan atau lahan secara bergantian agar informasi tetap jelas.</p>
        </div>
    </div>

    <div class="relative w-full max-w-7xl overflow-hidden rounded-[2.5rem] border border-[#ebe2d4] bg-[#f8f3ea] shadow-[0_22px_70px_rgba(45,43,35,0.12)]">
        <div class="public-priority-map-grid">
            <div class="public-priority-map-left p-5 sm:p-7 lg:p-8">
                <div class="mb-6 flex items-center justify-between gap-4 text-[10px] font-bold uppercase tracking-[0.32em] text-slate-600">
                    <span class="inline-flex items-center gap-3">
                        <span class="h-2 w-2 rounded-full bg-[#d4a43d]"></span>
                        Live Preview
                    </span>
                    <span>17 Kecamatan</span>
                </div>

                <div class="public-priority-map-canvas relative overflow-hidden rounded-2xl border border-[#d8d0c2] bg-white">
                    <div id="map" class="h-full w-full z-0"></div>
                </div>
            </div>

            <div class="public-priority-map-panel bg-[#fbf7ef] p-5 sm:p-7 lg:p-8">
                <aside id="map-insight-panel" data-map-priority-panel="embedded" class="h-full"></aside>
            </div>
        </div>

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

    <script>
        window.GATEWAY_URL = "{{ env('GATEWAY_URL', 'http://127.0.0.1:8003') }}";
    </script>
    <script src="{{ asset('js/map-sigpala.js') }}"></script>

@endsection
