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

    {{-- Right: Notifikasi + User + Logout --}}
    <div class="flex items-center gap-4">
        
        {{-- NOTIFIKASI (KHUSUS PETUGAS) --}}
        @if(session('role_id') == 2)
        <div class="relative flex items-center">
            <button onclick="document.getElementById('notif-dropdown').classList.toggle('hidden')" class="relative p-2 rounded-lg text-white hover:bg-white/10 transition" style="border: 1px solid rgba(255,255,255,.18)">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                <span id="notif-badge" class="hidden absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold text-red-600 bg-white rounded-full transform translate-x-1/4 -translate-y-1/4">0</span>
            </button>
            <div id="notif-dropdown" class="hidden absolute right-0 top-12 mt-1 w-72 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="px-4 py-2 bg-gray-50 border-b border-gray-100 font-bold text-xs text-gray-700">Notifikasi Masuk</div>
                <ul id="notif-list" class="max-h-60 overflow-y-auto bg-white">
                    <li class="p-4 text-center text-xs text-gray-500">Memuat...</li>
                </ul>
            </div>
        </div>
        <script>
            // Script ringan penarik notifikasi agar tidak membebani Controller
            document.addEventListener('DOMContentLoaded', () => {
                const token = "{{ session('token') }}";
                const gateway = "{{ env('GATEWAY_URL', 'http://127.0.0.1:8003') }}/api";
                
                function fetchNotif() {
                    fetch(`${gateway}/notifikasi/petugas`, { headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' } })
                    .then(res => res.json())
                    .then(json => {
                        if(json.success) {
                            const badge = document.getElementById('notif-badge');
                            const list = document.getElementById('notif-list');
                            if(json.unread_count > 0) {
                                badge.innerText = json.unread_count; badge.classList.remove('hidden');
                            } else { badge.classList.add('hidden'); }
                            
                            list.innerHTML = json.data.length === 0 ? '<li class="p-4 text-center text-xs text-gray-400">Belum ada notifikasi</li>' : '';
                            json.data.forEach(item => {
                                list.innerHTML += `<li class="p-3 border-b border-gray-50 hover:bg-emerald-50 cursor-pointer" onclick="bacaNotif(${item.id})">
                                    <p class="font-bold text-xs text-primary-900">${item.judul}</p>
                                    <p class="text-[10px] text-gray-500 mt-0.5">${item.pesan}</p>
                                </li>`;
                            });
                        }
                    });
                }
                window.bacaNotif = function(id) {
                    fetch(`${gateway}/notifikasi/${id}/read`, { method:'PUT', headers: { 'Authorization': `Bearer ${token}` } })
                    .then(() => { window.location.href = '/verifikasi-panen'; });
                };
                fetchNotif(); setInterval(fetchNotif, 30000);
            });
        </script>
        @endif

        {{-- User chip --}}
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg" style="background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.18)">
            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                 style="background:#9AE600; color:#35530E">
                {{ strtoupper(substr(session('user.name', 'PT'), 0, 2)) }}
            </div>
            <span class="text-xs font-medium text-white">
                {{ session('user.name', 'Petugas') }}
            </span>
        </div>

        {{-- Logout --}}
        <a href="/logout"
           class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition text-white/80 hover:text-white hover:bg-white/10"
           style="border: 1px solid rgba(255,255,255,.18)">
            Logout
        </a>
    </div>
</nav>