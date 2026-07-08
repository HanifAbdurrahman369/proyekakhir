@php
    $menuBase = 'group flex items-center gap-2.5 px-3 py-2.5 rounded-2xl text-[13px] font-semibold transition-all duration-200';
    $menuActive = 'bg-[#edf8dc] text-[#203c10] shadow-sm ring-1 ring-[#dfeccc]';
    $menuIdle = 'text-slate-600 hover:bg-[#f7fced] hover:text-[#2f5c12]';
    $iconBase = 'w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition';
    $iconActive = 'bg-[#3E7D00] text-white';
    $iconIdle = 'bg-slate-100 text-slate-500 group-hover:bg-[#edf8dc] group-hover:text-[#3E7D00]';

    $totalPending = 0;

    if (isset($stats)) {
        $totalPending = data_get($stats, 'total_pending', 0);
    }

    if ($totalPending == 0) {
        $totalPending =
            (isset($antreanLahan) && is_countable($antreanLahan) ? count($antreanLahan) : 0)
            +
            (isset($pendingLahan) && is_countable($pendingLahan) ? count($pendingLahan) : 0)
            +
            (isset($antreanPanen) && is_countable($antreanPanen) ? count($antreanPanen) : 0)
            +
            (isset($pendingPanen) && is_countable($pendingPanen) ? count($pendingPanen) : 0)
            +
            (isset($antrean) && is_countable($antrean) ? count($antrean) : 0);
    }
@endphp

<p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-[.18em] px-3 py-2 mt-1">
    Operasional Petugas
</p>

<a href="/dashboard-petugas" class="{{ $menuBase }} {{ request()->is('dashboard-petugas') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('dashboard-petugas') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
        </svg>
    </span>
    <span class="flex-1">Beranda Petugas</span>

    <span data-petugas-pending-badge class="px-2 py-0.5 rounded-full text-[10px] font-extrabold border {{ $totalPending > 0 ? 'bg-red-100 text-red-700 border-red-200' : 'bg-slate-100 text-slate-500 border-slate-200' }}">
        {{ $totalPending }}
    </span>
</a>

<a href="/manajemen-data-spasial" class="{{ $menuBase }} {{ request()->is('manajemen-data-spasial') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('manajemen-data-spasial') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
        </svg>
    </span>
    <span>Manajemen Data Spasial</span>
</a>

<a href="/lahan-termonitor" class="{{ $menuBase }} {{ request()->is('lahan-termonitor') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('lahan-termonitor') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9-4.03-9-9-9zm1 13h-2v-3H8v-2h3V8h2v3h3v2h-3v3z"/>
        </svg>
    </span>
    <span>Lahan Termonitor (IoT)</span>
</a>

<a href="/verifikasi-data-petani" class="{{ $menuBase }} {{ request()->is('verifikasi-data-petani') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('verifikasi-data-petani') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/>
        </svg>
    </span>
    <span class="flex-1">Verifikasi Data Petani</span>

    <span data-petugas-pending-badge class="px-2 py-0.5 rounded-full text-[10px] font-extrabold border {{ $totalPending > 0 ? 'bg-red-100 text-red-700 border-red-200' : 'bg-slate-100 text-slate-500 border-slate-200' }}">
        {{ $totalPending }}
    </span>
</a>

<a href="/manajemen-komunitas" class="{{ $menuBase }} {{ request()->is('manajemen-komunitas') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('manajemen-komunitas') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
        </svg>
    </span>
    <span>Manajemen Komunitas</span>
</a>

<a href="/profile" class="{{ $menuBase }} {{ request()->is('profile') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('profile') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
        </svg>
    </span>
    <span>Edit Profil</span>
</a>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        async function refreshPetugasPendingCounts() {
            try {
                const response = await fetch('/petugas/pending-counts', {
                    headers: { 'Accept': 'application/json' }
                });
                const json = await response.json();
                const total = Number(json?.data?.total_pending ?? 0);

                document.querySelectorAll('[data-petugas-pending-badge]').forEach((badge) => {
                    badge.textContent = total;
                    badge.classList.toggle('bg-red-100', total > 0);
                    badge.classList.toggle('text-red-700', total > 0);
                    badge.classList.toggle('border-red-200', total > 0);
                    badge.classList.toggle('bg-slate-100', total <= 0);
                    badge.classList.toggle('text-slate-500', total <= 0);
                    badge.classList.toggle('border-slate-200', total <= 0);
                });

                document.querySelectorAll('[data-petugas-pending-lahan]').forEach((badge) => {
                    badge.textContent = `${Number(json?.data?.pending_lahan ?? 0)} belum diverifikasi`;
                });

                document.querySelectorAll('[data-petugas-pending-panen]').forEach((badge) => {
                    badge.textContent = `${Number(json?.data?.pending_panen ?? 0)} belum diverifikasi`;
                });
            } catch (error) {}
        }

        window.refreshPetugasPendingCounts = refreshPetugasPendingCounts;
        refreshPetugasPendingCounts();
        setInterval(refreshPetugasPendingCounts, 8000);
        window.addEventListener('focus', refreshPetugasPendingCounts);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) refreshPetugasPendingCounts();
        });
    });
</script>
