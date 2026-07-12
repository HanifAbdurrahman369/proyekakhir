<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Peta Interaktif - SiPetani</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js']) 

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
    
    <style>
        /* CSS Tambahan Khusus Halaman Peta Publik */
        .map-loading { backdrop-filter: blur(4px); }
        .fullmap-topbar {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            justify-content: flex-start;
            left: 24px;
            pointer-events: none;
            position: absolute;
            top: 24px;
            z-index: 11000;
        }
        .fullmap-back {
            pointer-events: auto;
        }
        .fullmap-back {
            align-items: center;
            background: rgba(255,255,255,.96);
            border: 1px solid rgba(226,232,240,.95);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15,23,42,.12);
            color: #334155;
            display: inline-flex;
            font-size: 13px;
            font-weight: 700;
            gap: 9px;
            padding: 12px 16px;
            text-decoration: none;
            transition: all .2s ease;
            white-space: nowrap;
        }
        .fullmap-back:hover {
            background: #16a34a;
            border-color: #16a34a;
            color: #fff;
        }
        @media (max-width: 768px) {
            .fullmap-topbar {
                left: 14px;
                top: 14px;
            }
            .fullmap-back {
                border-radius: 14px;
                font-size: 12px;
                padding: 11px 12px;
            }
        }
    </style>
</head>

<body class="bg-slate-100 relative font-['Poppins']">

    <!-- Loading Overlay -->
    <div id="map-loading" class="map-loading absolute inset-0 z-[10000] bg-white/60 flex flex-col items-center justify-center transition-opacity duration-500">
        <svg class="animate-spin h-12 w-12 text-emerald-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <h3 class="text-lg font-bold text-slate-800">Memuat Data Spasial...</h3>
        <p class="text-sm text-slate-500 mt-1">Mengambil data titik dan poligon lahan.</p>
    </div>

    <!-- Top Bar -->
    <div class="fullmap-topbar">
        <div class="flex gap-3">
            <a href="{{ url('/') }}" class="fullmap-back">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke Dashboard
            </a>
        </div>
        <!-- Tengah: Search Bar -->
        <div class="pointer-events-auto relative w-full max-w-md hidden md:block">
            <div class="relative">
                <input type="text" id="search-lahan" placeholder="Cari lahan, kecamatan, kelurahan..." class="w-full bg-white/95 backdrop-blur-md border border-slate-200 text-slate-700 text-sm rounded-2xl pl-12 pr-4 py-3.5 shadow-[0_8px_30px_rgba(0,0,0,0.08)] focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 absolute left-4 top-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <!-- Hasil Pencarian -->
            <div id="search-results" class="absolute top-full left-0 right-0 mt-2 bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden hidden max-h-60 overflow-y-auto z-[9991]">
                <!-- Results injected via JS -->
            </div>
        </div>
        
        <!-- Kanan: Spacer -->
        <div class="w-[100px] hidden lg:block"></div>
    </div>


    <div id="map-legend"></div>

    <div class="relative w-full h-screen overflow-hidden">
        <div id="map" class="w-full h-full z-0"></div>

        <div id="side-panel" class="absolute top-0 right-[-450px] w-[400px] h-full bg-white/95 backdrop-blur-xl z-[9999] shadow-[-10px_0_40px_rgba(0,0,0,0.15)] transition-all duration-500 ease-in-out p-6 overflow-y-auto border-l border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-slate-800 tracking-wide">Detail Lahan Sawah</h3>
                <button onclick="closeSidePanel()" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-red-100 transition group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-500 group-hover:text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div id="panel-content" class="space-y-4">
                <div class="flex flex-col items-center justify-center h-[50vh] text-center opacity-60">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 mb-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 20l-5.447-2.724A2 2 0 013 15.382V7.618a2 2 0 011.096-1.789L9 3m0 17l6-3m-6 3V3m6 14l5.447 2.724A2 2 0 0021 17.618V9.882a2 2 0 00-1.096-1.789L15 3m0 14V3" />
                    </svg>
                    <p class="text-sm font-medium">Klik area lahan sawah pada peta<br>untuk melihat informasi detail.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.GATEWAY_URL = "{{ rtrim(env('GATEWAY_URL', 'http://127.0.0.1:8003'), '/') }}";
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
    <script src="{{ asset('js/map-sigpala.js') }}"></script>
</body>
</html>
