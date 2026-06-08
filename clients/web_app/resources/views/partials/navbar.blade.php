@php
    $roleId = (int) session('role_id');

    $roleLabel = match ($roleId) {
        1 => 'Petani',
        2 => 'Petugas',
        3 => 'Pejabat',
        4 => 'Admin',
        default => 'Pengguna',
    };

    $roleInitial = match ($roleId) {
        1 => 'PT',
        2 => 'PG',
        3 => 'PJ',
        4 => 'AD',
        default => 'US',
    };

    $namaLengkap = session('user.nama_lengkap', 'Pengguna');
@endphp

<nav class="sticky top-0 z-50 h-16 lg:h-[72px] border-b border-white/20"
     style="background:linear-gradient(135deg,#244b10 0%,#3E7D00 50%,#5EA500 100%); box-shadow:0 18px 50px rgba(32,60,16,.18);">

    <div class="h-full px-4 sm:px-5 lg:px-8 flex items-center justify-between gap-4">

        {{-- LEFT: Brand --}}
        <div class="flex items-center gap-3 min-w-0">

            {{-- Mobile Sidebar Button --}}
            <button type="button"
                    onclick="openSidebar()"
                    class="lg:hidden w-10 h-10 rounded-2xl bg-white/14 border border-white/20 text-white flex items-center justify-center active:scale-95 transition"
                    aria-label="Buka menu">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z"/>
                </svg>
            </button>

            <a href="/" class="flex items-center gap-3 min-w-0">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0"
                     style="background:rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.22);">
                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2s4-4-3 1C7 9 7 15 7 15s0-3 3-5.31C13.77 7.73 17 8 17 8Z"/>
                    </svg>
                </div>

                <div class="min-w-0">
                    <p class="text-[11px] sm:text-xs font-medium leading-none text-white/65 truncate">
                        Sistem Informasi
                    </p>
                    <p class="text-sm sm:text-base font-bold leading-snug text-white truncate">
                        Dinas Pertanian
                    </p>
                </div>
            </a>
        </div>

        {{-- RIGHT: User Area --}}
        <div class="flex items-center gap-2 sm:gap-3">

            {{-- Notifikasi hanya untuk Petugas --}}
            @if($roleId === 2)
                <div class="relative flex items-center">
                    <button onclick="document.getElementById('notif-dropdown').classList.toggle('hidden')"
                            class="relative w-10 h-10 rounded-2xl text-white hover:bg-white/15 transition flex items-center justify-center"
                            style="border:1px solid rgba(255,255,255,.20); background:rgba(255,255,255,.10);"
                            aria-label="Notifikasi">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                            </path>
                        </svg>

                        <span id="notif-badge"
                              class="hidden absolute -top-1 -right-1 min-w-5 h-5 px-1 inline-flex items-center justify-center text-[10px] font-bold text-white rounded-full"
                              style="background:#ef4444;">
                            0
                        </span>
                    </button>

                    <div id="notif-dropdown"
                         class="hidden absolute right-0 top-12 mt-2 w-[320px] max-w-[calc(100vw-32px)] bg-white rounded-3xl shadow-2xl border border-[#e7efd8] overflow-hidden">

                        <div class="px-5 py-3 bg-[#f7fced] border-b border-[#e7efd8]">
                            <p class="font-bold text-xs text-[#203c10]">
                                Notifikasi Masuk
                            </p>
                            <p class="text-[10px] text-slate-400 mt-0.5">
                                Update otomatis setiap 30 detik
                            </p>
                        </div>

                        <ul id="notif-list" class="max-h-72 overflow-y-auto bg-white">
                            <li class="p-5 text-center text-xs text-slate-500">
                                Memuat...
                            </li>
                        </ul>
                    </div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const token = "{{ session('token') }}";
                        const gateway = "{{ env('GATEWAY_URL', 'http://127.0.0.1:8003') }}/api";

                        function fetchNotif() {
                            fetch(`${gateway}/notifikasi/petugas`, {
                                headers: {
                                    'Authorization': `Bearer ${token}`,
                                    'Accept': 'application/json'
                                }
                            })
                            .then(res => res.json())
                            .then(json => {
                                if (json.success) {
                                    const badge = document.getElementById('notif-badge');
                                    const list = document.getElementById('notif-list');

                                    if (json.unread_count > 0) {
                                        badge.innerText = json.unread_count;
                                        badge.classList.remove('hidden');
                                    } else {
                                        badge.classList.add('hidden');
                                    }

                                    list.innerHTML = json.data.length === 0
                                        ? '<li class="p-5 text-center text-xs text-slate-400">Belum ada notifikasi</li>'
                                        : '';

                                    json.data.forEach(item => {
                                        list.innerHTML += `
                                            <li class="p-4 border-b border-[#edf4df] hover:bg-[#f7fced] cursor-pointer"
                                                onclick="bacaNotif(${item.id})">
                                                <p class="font-bold text-xs text-[#203c10]">${item.judul}</p>
                                                <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">${item.pesan}</p>
                                            </li>
                                        `;
                                    });
                                }
                            })
                            .catch(() => {});
                        }

                        window.bacaNotif = function(id) {
                            fetch(`${gateway}/notifikasi/${id}/read`, {
                                method: 'PUT',
                                headers: {
                                    'Authorization': `Bearer ${token}`
                                }
                            }).then(() => {
                                window.location.href = '/verifikasi-panen';
                            });
                        };

                        fetchNotif();
                        setInterval(fetchNotif, 30000);
                    });
                </script>
            @endif

            {{-- Role Label Desktop --}}
            <div class="hidden sm:flex items-center gap-2.5 px-3 py-2 rounded-2xl"
                 style="background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.20);">

                <div class="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-extrabold"
                     style="background:#B7F43B; color:#244b10;">
                    {{ $roleInitial }}
                </div>

                <div class="leading-tight min-w-0">
                    <p class="text-xs font-bold text-white max-w-[180px] truncate">
                        {{ $namaLengkap }}
                    </p>

                    <p class="text-[10px] text-white/55 font-medium">
                        {{ $roleLabel }}
                    </p>
                </div>
            </div>

            {{-- Role Label Mobile --}}
            <div class="sm:hidden w-10 h-10 rounded-2xl flex items-center justify-center text-xs font-extrabold"
                 title="{{ $roleLabel }}"
                 style="background:#B7F43B; color:#244b10;">
                {{ $roleInitial }}
            </div>

            {{-- Logout --}}
            <a href="/logout"
               class="px-3 sm:px-4 py-2 rounded-2xl text-xs font-bold transition text-white hover:bg-white/15"
               style="border:1px solid rgba(255,255,255,.20);">
                Logout
            </a>
        </div>
    </div>
</nav>