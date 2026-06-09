@php
    $menuBase = 'group flex items-center gap-3 px-3.5 py-3 rounded-2xl text-[13px] font-semibold transition-all duration-200';
    $menuActive = 'bg-[#edf8dc] text-[#203c10] shadow-sm ring-1 ring-[#dfeccc]';
    $menuIdle = 'text-slate-600 hover:bg-[#f7fced] hover:text-[#2f5c12]';
    $iconBase = 'w-9 h-9 rounded-xl flex items-center justify-center shrink-0 transition';
    $iconActive = 'bg-[#3E7D00] text-white';
    $iconIdle = 'bg-slate-100 text-slate-500 group-hover:bg-[#edf8dc] group-hover:text-[#3E7D00]';
@endphp

<p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-[.18em] px-3 py-2 mt-1">Laporan Eksekutif</p>

<a href="/dashboard-pejabat" class="{{ $menuBase }} {{ request()->is('dashboard-pejabat') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('dashboard-pejabat') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
    </span>
    <span>Statistik Utama</span>
</a>

<a href="{{ route('map.pejabat') }}" class="{{ $menuBase }} {{ request()->is('map-pejabat*') || request()->is('sebaran-lahan*') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('map-pejabat*') || request()->is('sebaran-lahan*') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5z"/></svg>
    </span>
    <span>Sebaran Lahan</span>
</a>

<p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-[.18em] px-3 py-2 mt-4">Analisis Data</p>

<a href="/laporan-produksi" class="{{ $menuBase }} {{ request()->is('laporan-produksi*') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('laporan-produksi*') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="m3.5 18.49 6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z"/></svg>
    </span>
    <span>Produksi Daerah</span>
</a>

