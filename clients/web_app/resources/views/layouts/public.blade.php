<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIG-PALA | Sistem Informasi Geografis Lahan Rawa</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite('resources/css/app.css')
</head>

<body class="bg-white font-['Poppins']">

    <nav class="flex items-center justify-between px-8 md:px-16 py-4 border-b border-slate-100 shadow-sm fixed top-0 w-full bg-white/90 backdrop-blur-md z-[2000] transition-all">
        <div class="flex items-center gap-4">
            <img src="{{ asset('storage/logo.png') }}" alt="Logo SIG-PALA" class="w-12 h-12 object-contain">
            <p class="text-slate-900 font-extrabold text-2xl tracking-wide">SIG<span class="text-emerald-500">-PALA</span></p>
        </div>

        <div class="hidden md:flex justify-end items-center gap-10 font-semibold text-sm">
            <a href="{{ url('/') }}" class="text-slate-500 hover:text-emerald-600 transition-colors">BERANDA</a>
            <a href="{{ url('/data-statistik') }}" class="text-slate-500 hover:text-emerald-600 transition-colors">DATA STATISTIK</a>
            <a href="{{ route('map.full') }}" class="text-slate-500 hover:text-emerald-600 transition-colors">MAP EKSPLORASI</a>
            
            <a href="/login" class="bg-emerald-600 hover:bg-emerald-700 text-white px-7 py-2.5 rounded-xl shadow-[0_8px_20px_rgba(16,185,129,0.3)] hover:shadow-[0_10px_25px_rgba(16,185,129,0.4)] transition-all hover:-translate-y-0.5">
                Login Sistem
            </a>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <footer class="bg-slate-900 pt-20 pb-10 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-6 md:px-12 lg:px-20">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12 mb-16">
                
                <div class="space-y-6">
                    <p class="text-white font-extrabold text-3xl tracking-wide">SIG<span class="text-emerald-500">-PALA</span></p>
                    <p class="text-slate-400 font-medium text-base leading-relaxed">
                        Sistem Informasi Geografis Produktivitas pada Lahan Rawa Kabupaten Barito Kuala. Transparansi data untuk kemajuan pertanian.
                    </p>
                </div>
                
                <div class="space-y-6">
                    <h4 class="text-white font-bold text-lg tracking-wide uppercase">Kontak Kami</h4>
                    <ul class="text-slate-400 space-y-4 font-medium text-sm">
                        <li class="flex items-start gap-3">
                            <span class="text-emerald-500 text-lg">📍</span>
                            <span>Jl. Jend Sudirman No.74, Ulu Benteng, Kec. Marabahan, Kabupaten Barito Kuala, Kalimantan Selatan 70513</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-500 text-lg">📞</span>
                            <span>0511-6701895</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="text-emerald-500 text-lg">✉️</span>
                            <span>distantph@baritokualakab.go.id</span>
                        </li>
                    </ul>
                </div>

                <div class="space-y-6 lg:pl-10">
                    <h4 class="text-white font-bold text-lg tracking-wide uppercase">Akses Cepat</h4>
                    <ul class="text-slate-400 space-y-3 font-medium text-sm">
                        <li><a href="{{ url('/') }}" class="hover:text-emerald-400 transition-colors">Beranda Utama</a></li>
                        <li><a href="{{ url('/data-statistik') }}" class="hover:text-emerald-400 transition-colors">Data Rekapitulasi</a></li>
                        <li><a href="{{ route('map.full') }}" class="hover:text-emerald-400 transition-colors">Peta Eksplorasi</a></li>
                    </ul>
                </div>

            </div>
            
            <div class="pt-8 border-t border-slate-800/80 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-slate-500 font-medium text-sm text-center md:text-left">
                    &copy; {{ date('Y') }} Dinas Pertanian, Tanaman Pangan dan Hortikultura Kab. Barito Kuala.
                </p>
                <div class="text-slate-600 text-sm font-semibold">
                    Platform Open Data Geospasial
                </div>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

</body>
</html>