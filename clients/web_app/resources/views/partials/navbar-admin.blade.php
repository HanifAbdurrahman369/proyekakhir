<!-- resources/views/partials/navbar-admin.blade.php -->

<nav class="bg-primary-800 text-white h-14 flex items-center justify-between px-6 sticky top-0 z-50"
     style="background: linear-gradient(90deg, #3C6300 0%, #497D00 100%);">

    {{-- Brand --}}
    <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:rgba(255,255,255,.18)">
            <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2s4-4-3 1C7 9 7 15 7 15s0-3 3-5.31C13.77 7.73 17 8 17 8Z"/>
            </svg>
        </div>
        <div>
            <p class="text-xs font-medium leading-none" style="color:rgba(255,255,255,.6)">Sistem Informasi</p>
            <p class="text-sm font-bold leading-snug">Dinas Pertanian</p>
        </div>
    </div>

    {{-- Nav Links --}}
    <div class="flex items-center gap-1">
        <a href="/dashboard-admin"
           class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition
                  {{ request()->is('dashboard-admin') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
            </svg>
            Dashboard
        </a>

        <a href="/manajemen-user"
           class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition
                  {{ request()->is('manajemen-user') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
            </svg>
            Manajemen User
        </a>

        <a href="/settings"
           class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition
                  {{ request()->is('settings') ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
            </svg>
            Settings
        </a>
    </div>

    {{-- Right: User + Logout --}}
    <div class="flex items-center gap-2">
        {{-- User chip --}}
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg" style="background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.18)">
            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                 style="background:#9AE600; color:#35530E">
                {{ strtoupper(substr(session('user.nama_lengkap', 'AD'), 0, 2)) }}
            </div>
            <span class="text-xs font-medium text-white">
                {{ session('user.nama_lengkap', 'Admin') }}
            </span>
        </div>

        {{-- Logout --}}
        <a href="/logout"
           class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition text-white/80 hover:text-white"
           style="background:rgba(0,0,0,.25); border:1px solid rgba(0,0,0,.2)">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
            </svg>
            Logout
        </a>
    </div>

</nav>