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

        <h1 class="text-5xl sm:text-7xl md:text-9xl font-black tracking-tight mb-4 flex justify-center items-baseline">
            <span class="text-white drop-shadow-lg"><span class="text-[1.2em]">S</span>i</span><span class="text-emerald-400 drop-shadow-[0_0_20px_rgba(52,211,153,0.4)]"><span class="text-[1.2em]">P</span>etani</span>
        </h1>
        
        <div class="w-24 h-1.5 bg-emerald-500 rounded-full mb-8 shadow-[0_0_15px_rgba(16,185,129,0.5)]"></div>

        <p class="text-emerald-50 text-sm sm:text-lg md:text-xl max-w-2xl font-medium tracking-wide leading-relaxed drop-shadow-md">
            Sistem Informasi Pemetaan Padi Dinas Pertanian Batola
        </p>
    </div>

</div>

<style>
.sipetani-feature-section {
    position: relative;
    width: 100%;
    padding: 6rem 1.5rem;
    background: linear-gradient(135deg, #064e3b 0%, #065f46 50%, #1b4317 100%);
    display: flex;
    justify-content: center;
    font-family: 'Poppins', sans-serif;
    overflow: hidden;
}

@media (min-width: 768px) {
    .sipetani-feature-section { padding: 6rem 3rem; }
}

@media (min-width: 1024px) {
    .sipetani-feature-section { padding: 6rem 5rem; }
}

.sipetani-feature-blob-1 {
    position: absolute;
    top: -10%;
    left: -10%;
    width: 50%;
    height: 60%;
    border-radius: 50%;
    background-color: rgba(16, 185, 129, 0.2);
    filter: blur(120px);
    pointer-events: none;
}

.sipetani-feature-blob-2 {
    position: absolute;
    bottom: 0%;
    right: 0%;
    width: 40%;
    height: 50%;
    border-radius: 50%;
    background-color: rgba(163, 230, 53, 0.1);
    filter: blur(100px);
    pointer-events: none;
}

.sipetani-feature-pattern {
    position: absolute;
    inset: 0;
    opacity: 0.03;
    background-image: radial-gradient(#fff 1px, transparent 1px);
    background-size: 32px 32px;
    pointer-events: none;
}

.sipetani-feature-card {
    display: flex;
    align-items: flex-start;
    gap: 1.5rem;
    padding: 1.5rem;
    background-color: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(12px);
    border-radius: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
}

.sipetani-feature-card:hover {
    transform: translateY(-0.375rem);
    background-color: rgba(255, 255, 255, 0.15);
}

.sipetani-feature-icon {
    width: 3.5rem;
    height: 3.5rem;
    flex-shrink: 0;
    background-color: #a3e635;
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #064e3b;
    box-shadow: 0 0 20px rgba(163, 230, 53, 0.4);
}

.sipetani-btn-download {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    background-color: white;
    color: #064e3b;
    padding: 1rem 1.5rem;
    border-radius: 1rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    border: 1px solid white;
    text-decoration: none;
    transition: all 0.3s ease;
}

.sipetani-btn-download:hover {
    transform: translateY(-0.25rem);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
    background-color: #ecfdf5;
}

.sipetani-btn-icon {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.75rem;
    background-color: #d1fae5;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.3s ease;
}

.sipetani-btn-download:hover .sipetani-btn-icon {
    transform: scale(1.1);
}
</style>

<div class="sipetani-feature-section">
    <div class="sipetani-feature-blob-1"></div>
    <div class="sipetani-feature-blob-2"></div>
    <div class="sipetani-feature-pattern"></div>

    <div class="relative z-10 max-w-7xl w-full grid grid-cols-1 lg:grid-cols-2 gap-16 lg:gap-24 items-center">
        
        <div class="space-y-8" style="color: white;">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div style="width: 4px; height: 24px; background-color: #a3e635; border-radius: 9999px; box-shadow: 0 0 10px rgba(163, 230, 53, 0.6);"></div>
                    <p style="color: #bef264; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.2em; text-transform: uppercase; margin: 0;">Layanan Informasi Publik</p>
                </div>
                <h2 style="font-size: clamp(1.875rem, 5vw, 3rem); font-weight: 800; line-height: 1.15; letter-spacing: -0.025em; text-shadow: 0 1px 2px rgba(0,0,0,0.1); margin: 0;">
                    Transparansi Data & <br>
                    <span style="color: #a3e635; text-shadow: 0 0 15px rgba(163, 230, 53, 0.2);">Visualisasi Lahan Rawa</span>
                </h2>
            </div>
            
            <div style="color: #ecfdf5; font-size: clamp(1rem, 2vw, 1.125rem); line-height: 1.75; font-weight: 500; display: flex; flex-direction: column; gap: 1.25rem;">
                <p style="margin: 0;">
                    SiPetani adalah Sistem Informasi Pemetaan Padi Dinas Pertanian Batola yang dirancang khusus untuk memetakan ketersediaan lahan pertanian rawa secara akurat, modern, dan transparan, yang ditujukan untuk Dinas Pertanian, Tanaman Pangan dan Holtikultura.
                </p>
                <p style="margin: 0;">
                    Platform geospasial ini mengintegrasikan pemetaan digital interaktif dengan data statistik luasan panen secara real-time. Dengan memanfaatkan SiPetani, pemerintah daerah dan para stakeholder dapat merumuskan kebijakan berbasis data demi menjaga ketahanan pangan dan memaksimalkan potensi produktivitas lahan rawa di Kabupaten Barito Kuala.
                </p>
            </div>

            <div style="padding-top: 1.5rem; border-top: 1px solid rgba(4, 120, 87, 0.5);">
                <div style="margin-bottom: 1rem;">
                    <p style="font-size: 0.75rem; color: #bef264; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; margin: 0;">Akses Khusus Mitra Lapangan:</p>
                </div>
                <a href="{{ route('mobile-app.download', ['v' => is_file(storage_path('app/SiPetani.apk')) ? filemtime(storage_path('app/SiPetani.apk')) : 'missing']) }}" class="sipetani-btn-download">
                    <div class="sipetani-btn-icon">
                        <svg style="width: 1.5rem; height: 1.5rem; color: #059669;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    </div>
                    <div style="text-align: left;">
                        <div style="font-size: 0.6875rem; font-weight: 700; color: rgba(5, 150, 105, 0.8); text-transform: uppercase; letter-spacing: 0.025em; line-height: 1;">Unduh Aplikasi Mobile</div>
                        <div style="font-size: 0.9375rem; font-weight: 900; letter-spacing: -0.025em; margin-top: 0.125rem; line-height: 1;">Khusus Poktan, Brigade & BPP</div>
                    </div>
                </a>
            </div>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 1.25rem;">
            <div class="sipetani-feature-card">
                <div class="sipetani-feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" style="height: 1.75rem; width: 1.75rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A2 2 0 013 15.382V7.618a2 2 0 011.096-1.789L9 3m0 17l6-3m-6 3V3m6 14l5.447 2.724A2 2 0 0021 17.618V9.882a2 2 0 00-1.096-1.789L15 3m0 14V3" />
                    </svg>
                </div>
                <div>
                    <h4 style="font-weight: 700; color: white; font-size: 1.25rem; margin-bottom: 0.375rem; text-shadow: 0 1px 2px rgba(0,0,0,0.1); letter-spacing: -0.025em; margin-top: 0;">Peta Interaktif</h4>
                    <p style="font-size: 0.875rem; color: #d1fae5; font-weight: 500; line-height: 1.625; margin: 0;">Eksplorasi batas wilayah hingga detail blok lahan rawa secara visual dan real-time.</p>
                </div>
            </div>
            
            <div class="sipetani-feature-card">
                <div class="sipetani-feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" style="height: 1.75rem; width: 1.75rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 00-2-2H5a2 2 0 00-2 2v10m0 0h10m-10 0V5a2 2 0 012-2h2a2 2 0 012 2v14m0 0h10m-10 0V7a2 2 0 012-2h2a2 2 0 012 2v14m0 0h10" />
                    </svg>
                </div>
                <div>
                    <h4 style="font-weight: 700; color: white; font-size: 1.25rem; margin-bottom: 0.375rem; text-shadow: 0 1px 2px rgba(0,0,0,0.1); letter-spacing: -0.025em; margin-top: 0;">Analisis Statistik</h4>
                    <p style="font-size: 0.875rem; color: #d1fae5; font-weight: 500; line-height: 1.625; margin: 0;">Data akumulasi luasan lahan sawah yang disajikan secara transparan dengan grafik visual.</p>
                </div>
            </div>

            <div class="sipetani-feature-card">
                <div class="sipetani-feature-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" style="height: 1.75rem; width: 1.75rem;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <h4 style="font-weight: 700; color: white; font-size: 1.25rem; margin-bottom: 0.375rem; text-shadow: 0 1px 2px rgba(0,0,0,0.1); letter-spacing: -0.025em; margin-top: 0;">Informasi Produktivitas</h4>
                    <p style="font-size: 0.875rem; color: #d1fae5; font-weight: 500; line-height: 1.625; margin: 0;">Lacak potensi dan realisasi produktivitas lahan rawa berdasarkan data akurat (Ton/Ha).</p>
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
        <div class="space-y-3 max-w-3xl">
            <div class="flex items-center gap-3">
                <div class="w-1 h-5 bg-emerald-500 rounded-full"></div>
                <p class="text-emerald-600 text-[11px] font-bold tracking-[0.2em] uppercase">Peta Geospasial</p>
            </div>
            <h2 class="text-slate-900 text-4xl sm:text-5xl font-extrabold tracking-tight">
                Map <span class="text-emerald-600">Interaktif</span>
            </h2>
            <p class="text-slate-500 text-[15px] sm:text-base font-medium leading-relaxed">
                Pantau sebaran lahan sawah, batas kecamatan, dan produktivitas wilayah Barito Kuala dalam satu peta yang mudah dibaca.
            </p>
        </div>
        <div class="md:text-right max-w-sm">
            <p class="text-slate-500 text-[15px] font-medium leading-relaxed">
                Klik wilayah untuk rekap produktivitas, atau pilih titik lahan untuk melihat detail sawah.
            </p>
        </div>
    </div>

    <div class="max-w-7xl w-full grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)]">
            <p class="text-[11px] font-extrabold tracking-[0.22em] uppercase text-emerald-600 mb-2">Warna Wilayah</p>
            <p class="text-[15px] font-medium leading-relaxed text-slate-500">Warna kecamatan menunjukkan kategori produktivitas dari data GIS.</p>
        </div>
        <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)]">
            <p class="text-[11px] font-extrabold tracking-[0.22em] uppercase text-emerald-600 mb-2">Titik Lahan</p>
            <p class="text-[15px] font-medium leading-relaxed text-slate-500">Titik hijau menandai lokasi lahan sawah yang dapat dibuka detailnya.</p>
        </div>
        <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-[0_10px_30px_rgba(15,23,42,0.04)]">
            <p class="text-[11px] font-extrabold tracking-[0.22em] uppercase text-emerald-600 mb-2">Detail Informasi</p>
            <p class="text-[15px] font-medium leading-relaxed text-slate-500">Panel detail menjaga informasi kecamatan dan lahan tetap rapi.</p>
        </div>
    </div>

    <div class="relative w-full max-w-7xl overflow-hidden rounded-[2.5rem] border border-slate-100 bg-white shadow-md">
        <div class="public-priority-map-grid">
            <div class="public-priority-map-left p-5 sm:p-7 lg:p-8">
                <div class="mb-6 flex items-center justify-between gap-4 text-[10px] font-bold uppercase tracking-[0.32em] text-slate-600">
                    <span class="inline-flex items-center gap-3">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Live Preview
                    </span>
                    <span>17 Kecamatan</span>
                </div>

                <div class="public-priority-map-canvas relative overflow-hidden rounded-2xl border border-slate-100 bg-white">
                    <div id="map" class="h-full w-full z-0"></div>
                </div>
            </div>

            <div class="public-priority-map-panel bg-white p-5 sm:p-7 lg:p-8">
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
        window.GATEWAY_URL = "{{ rtrim(env('GATEWAY_URL', 'http://127.0.0.1:8003'), '/') }}";
    </script>
    <script src="{{ asset('js/map-sigpala.js') }}"></script>

@endsection
