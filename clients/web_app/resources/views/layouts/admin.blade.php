<!-- resources/views/layouts/admin.blade.php -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard Admin') — SiTani</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite('resources/css/app.css')
</head>

<body class="font-sans bg-primary-50/40 min-h-screen">

    {{-- Navbar --}}
    @include('partials.navbar-admin')

    {{-- Layout: Sidebar + Main --}}
    <div class="flex min-h-[calc(100vh-56px)]">

        {{-- Sidebar --}}
        <aside class="w-52 flex-shrink-0 bg-white border-r border-primary-100 py-4 px-3 flex flex-col gap-1">

            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider px-2 py-1 mt-1">Menu Utama</p>

            <a href="/dashboard-admin" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-medium transition
               {{ request()->is('dashboard-admin') ? 'bg-primary-100 text-primary-800' : 'text-gray-600 hover:bg-primary-50 hover:text-primary-700' }}">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                Dashboard
            </a>

            <a href="/peta-lahan" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-medium transition text-gray-600 hover:bg-primary-50 hover:text-primary-700">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                Peta Lahan
            </a>

            <a href="/komoditas" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-medium transition text-gray-600 hover:bg-primary-50 hover:text-primary-700">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2s4-4-3 1C7 9 7 15 7 15s0-3 3-5.31C13.77 7.73 17 8 17 8Z"/></svg>
                Data Komoditas
            </a>

            <a href="/petani" class="flex items-center justify-between px-2.5 py-2 rounded-lg text-xs font-medium transition text-gray-600 hover:bg-primary-50 hover:text-primary-700">
                <span class="flex items-center gap-2.5">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
                    Data Petani
                </span>
                <span class="bg-primary-400 text-primary-900 text-[9px] font-bold px-1.5 py-0.5 rounded-full">12</span>
            </a>

            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider px-2 py-1 mt-3">Laporan</p>

            <a href="/laporan-produksi" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-medium transition text-gray-600 hover:bg-primary-50 hover:text-primary-700">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z"/></svg>
                Produksi
            </a>

            <a href="/ekspor" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-medium transition text-gray-600 hover:bg-primary-50 hover:text-primary-700">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                Ekspor Data
            </a>

            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider px-2 py-1 mt-3">Sistem</p>

            <a href="/manajemen-user" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-medium transition text-gray-600 hover:bg-primary-50 hover:text-primary-700">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                Manajemen User
            </a>

            <a href="/settings" class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg text-xs font-medium transition text-gray-600 hover:bg-primary-50 hover:text-primary-700">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>
                Settings
            </a>

        </aside>

        {{-- Main Content --}}
        <main class="flex-1 p-6 overflow-y-auto">
            @yield('content')
        </main>

    </div>

</body>
</html>