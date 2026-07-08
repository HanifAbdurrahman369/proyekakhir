<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiTani | Sistem Informasi Dinas Pertanian</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite('resources/css/app.css')
</head>

<body class="bg-white font-['Poppins']">

    <nav class="flex items-center justify-between px-8 md:px-16 py-4 border-b border-slate-100 shadow-sm fixed top-0 w-full bg-white/90 backdrop-blur-md transition-all" style="z-index: 9997;">
        <div class="flex items-center gap-4">
            <img src="{{ asset('storage/logo.png') }}" alt="Logo SiTani" class="w-12 h-12 object-contain">
            <p class="text-slate-900 font-extrabold text-2xl tracking-wide"><span class="text-[1.25em]">S</span>i<span class="text-emerald-500"><span class="text-[1.25em]">T</span>ani</span></p>
        </div>

        <div class="hidden md:flex justify-end items-center gap-10 font-semibold text-sm">
            <a href="{{ url('/') }}" class="text-slate-500 hover:text-emerald-600 transition-colors">BERANDA</a>
            <a href="{{ route('statistik.publik') }}" class="text-slate-500 hover:text-emerald-600 transition-colors">DATA STATISTIK</a>
            <a href="{{ route('map.full') }}" class="text-slate-500 hover:text-emerald-600 transition-colors">MAP EKSPLORASI</a>
            
            <a href="/login" class="bg-emerald-600 hover:bg-emerald-700 text-white px-7 py-2.5 rounded-xl shadow-[0_8px_20px_rgba(16,185,129,0.3)] hover:shadow-[0_10px_25px_rgba(16,185,129,0.4)] transition-all hover:-translate-y-0.5">
                Login
            </a>
        </div>

        <!-- Mobile Menu Button -->
        <button id="mobile-menu-btn" onclick="toggleMenu()" class="md:hidden flex items-center justify-center p-2 text-slate-600 hover:text-emerald-600 focus:outline-none transition-transform active:scale-95">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
    </nav>

    <!-- Mobile Menu Overlay -->
    <div id="mobile-menu-overlay" onclick="toggleMenu()" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-all duration-300 md:hidden" style="z-index: 9998; cursor: pointer; opacity: 0; pointer-events: none;"></div>

    <!-- Mobile Menu Drawer -->
    <div id="mobile-menu" class="fixed top-0 right-0 w-[280px] sm:w-[320px] h-full bg-white shadow-2xl transition-transform duration-300 ease-out md:hidden flex flex-col" style="z-index: 9999; transform: translateX(100%);">
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <img src="{{ asset('storage/logo.png') }}" alt="Logo SiTani" class="w-9 h-9 object-contain">
                <span class="text-slate-900 font-extrabold text-xl tracking-wide"><span class="text-[1.2em]">S</span>i<span class="text-emerald-500"><span class="text-[1.2em]">T</span>ani</span></span>
            </div>
            <button id="close-menu-btn" onclick="toggleMenu()" class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-full transition-colors focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto py-6 px-6">
            <div class="flex flex-col space-y-2 font-semibold text-sm">
                <a href="{{ url('/') }}" class="text-slate-600 hover:text-emerald-600 hover:bg-emerald-50/70 px-4 py-3.5 rounded-xl transition-all flex items-center gap-3">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    BERANDA
                </a>
                <a href="{{ route('statistik.publik') }}" class="text-slate-600 hover:text-emerald-600 hover:bg-emerald-50/70 px-4 py-3.5 rounded-xl transition-all flex items-center gap-3">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    DATA STATISTIK
                </a>
                <a href="{{ route('map.full') }}" class="text-slate-600 hover:text-emerald-600 hover:bg-emerald-50/70 px-4 py-3.5 rounded-xl transition-all flex items-center gap-3">
                    <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                    MAP EKSPLORASI
                </a>
            </div>
        </div>
        
        <div class="p-6 border-t border-slate-100 bg-slate-50/50">
            <a href="/login" class="flex items-center justify-center gap-2 bg-emerald-600 text-white w-full py-3.5 rounded-xl font-bold hover:bg-emerald-700 shadow-lg shadow-emerald-600/30 transition-all hover:-translate-y-0.5 active:scale-95">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                Login Sistem
            </a>
        </div>
    </div>

    <main class="mt-[73px]">
        @yield('content')
    </main>

    <footer class="bg-slate-900 pt-20 pb-10 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-6 md:px-12 lg:px-20">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12 mb-16">
                
                <div class="space-y-6">
                    <p class="text-white font-extrabold text-3xl tracking-wide"><span class="text-[1.25em]">S</span>i<span class="text-emerald-500"><span class="text-[1.25em]">T</span>ani</span></p>
                    <p class="text-slate-400 font-medium text-base leading-relaxed">
                        SiTani ( Sistem informasi dinas pertanian ). Transparansi data untuk kemajuan pertanian.
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
                        <li><a href="{{ route('statistik.publik') }}" class="hover:text-emerald-400 transition-colors">Data Rekapitulasi</a></li>
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
    <script>
        // Mobile menu toggle logic
        window.toggleMenu = function() {
            const menu = document.getElementById('mobile-menu');
            const overlay = document.getElementById('mobile-menu-overlay');
            
            if (!menu || !overlay) return;

            const isClosed = menu.style.transform === 'translateX(100%)' || menu.style.transform === '';
            if (isClosed) {
                // Open menu
                menu.style.transform = 'translateX(0)';
                overlay.style.opacity = '1';
                overlay.style.pointerEvents = 'auto';
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
            } else {
                // Close menu
                menu.style.transform = 'translateX(100%)';
                overlay.style.opacity = '0';
                overlay.style.pointerEvents = 'none';
                document.body.style.overflow = '';
            }
        };

        // Auto-wrap tables to prevent horizontal overflow on mobile
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll("table").forEach(function(table) {
                const parent = table.parentElement;
                if (parent && !parent.classList.contains("overflow-x-auto")) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'overflow-x-auto w-full custom-scrollbar';
                    parent.insertBefore(wrapper, table);
                    wrapper.appendChild(table);
                }
            });
        });
    </script>
</body>
</html>
