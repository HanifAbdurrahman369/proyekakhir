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

    {{-- Right: User + Logout --}}
    <div class="flex items-center gap-2">
        {{-- User chip --}}
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg" style="background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.18)">
            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                 style="background:#9AE600; color:#35530E">
                {{ strtoupper(substr(session('user.nama_lengkap', 'US'), 0, 2)) }}
            </div>
            <span class="text-xs font-medium text-white">
                {{ session('user.nama_lengkap', 'Pengguna') }}
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