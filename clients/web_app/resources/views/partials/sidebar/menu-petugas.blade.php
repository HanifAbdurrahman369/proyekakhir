@php
    $menuBase = 'group flex items-center gap-3 px-3.5 py-3 rounded-2xl text-[13px] font-semibold transition-all duration-200';
    $menuActive = 'bg-[#edf8dc] text-[#203c10] shadow-sm ring-1 ring-[#dfeccc]';
    $menuIdle = 'text-slate-600 hover:bg-[#f7fced] hover:text-[#2f5c12]';
    $iconBase = 'w-9 h-9 rounded-xl flex items-center justify-center shrink-0 transition';
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

    @if($totalPending > 0)
        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-200">
            {{ $totalPending }}
        </span>
    @endif
</a>

<a href="/manajemen-data-spasial" class="{{ $menuBase }} {{ request()->is('manajemen-data-spasial') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('manajemen-data-spasial') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
        </svg>
    </span>
    <span>Manajemen Data Spasial</span>
</a>

<a href="/input-parameter-lingkungan" class="{{ $menuBase }} {{ request()->is('input-parameter-lingkungan') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('input-parameter-lingkungan') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9-4.03-9-9-9zm1 13h-2v-3H8v-2h3V8h2v3h3v2h-3v3z"/>
        </svg>
    </span>
    <span>Input Parameter Lingkungan</span>
</a>

<a href="/verifikasi-data-petani" class="{{ $menuBase }} {{ request()->is('verifikasi-data-petani') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('verifikasi-data-petani') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/>
        </svg>
    </span>
    <span class="flex-1">Verifikasi Data Petani</span>

    @if($totalPending > 0)
        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-red-100 text-red-700 border border-red-200">
            {{ $totalPending }}
        </span>
    @endif
</a>