@php
    $menuBase = 'group flex items-center gap-3 px-3.5 py-3 rounded-2xl text-[13px] font-semibold transition-all duration-200';
    $menuActive = 'bg-[#edf8dc] text-[#203c10] shadow-sm ring-1 ring-[#dfeccc]';
    $menuIdle = 'text-slate-600 hover:bg-[#f7fced] hover:text-[#2f5c12]';
    $iconBase = 'w-9 h-9 rounded-xl flex items-center justify-center shrink-0 transition';
    $iconActive = 'bg-[#3E7D00] text-white';
    $iconIdle = 'bg-slate-100 text-slate-500 group-hover:bg-[#edf8dc] group-hover:text-[#3E7D00]';
@endphp

<p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-[.18em] px-3 py-2 mt-1">Lahan & Produksi</p>

<a href="/dashboard-petani" class="{{ $menuBase }} {{ request()->is('dashboard-petani') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('dashboard-petani') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
    </span>
    <span>Lahan Saya</span>
</a>

<p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-[.18em] px-3 py-2 mt-4">Aktivitas</p>

<a href="{{ route('input.panen') }}" class="{{ $menuBase }} {{ request()->is('input-panen*') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('input-panen*') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2s4-4-3 1C7 9 7 15 7 15s0-3 3-5.31C13.77 7.73 17 8 17 8Z"/></svg>
    </span>
    <span>Input Hasil Panen</span>
</a>

<a href="{{ route('riwayat.panen') }}" class="{{ $menuBase }} {{ request()->is('riwayat-panen*') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('riwayat-panen*') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
    </span>
    <span>Riwayat Panen</span>
</a>
