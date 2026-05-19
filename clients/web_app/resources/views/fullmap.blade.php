<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Peta Interaktif - SIG-PALA</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js']) 

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    </head>

<body class="bg-slate-100 relative font-['Poppins']">

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

    <a href="{{ url('/') }}" class="absolute top-6 left-24 z-[9999] bg-white/95 backdrop-blur-md text-slate-700 px-7 py-3 rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.08)] border border-slate-100 font-medium text-base hover:bg-emerald-600 hover:text-white hover:border-emerald-600 transition-all duration-300 font-['Poppins']">
        Kembali ke Dashboard
    </a>

    <script>
        window.GATEWAY_URL = "{{ env('GATEWAY_URL', 'http://127.0.0.1:8000') }}";
    </script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="{{ asset('js/map-sigpala.js') }}"></script>
</body>
</html>