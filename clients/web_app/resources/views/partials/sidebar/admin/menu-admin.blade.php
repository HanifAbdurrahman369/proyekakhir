@php
    $menuBase = 'group flex items-center gap-2.5 px-3 py-2.5 rounded-2xl text-[13px] font-semibold transition-all duration-200';
    $menuActive = 'bg-[#edf8dc] text-[#203c10] shadow-sm ring-1 ring-[#dfeccc]';
    $menuIdle = 'text-slate-600 hover:bg-[#f7fced] hover:text-[#2f5c12]';
    $iconBase = 'w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition';
    $iconActive = 'bg-[#047857] text-white';
    $iconIdle = 'bg-slate-100 text-slate-500 group-hover:bg-[#edf8dc] group-hover:text-[#047857]';
@endphp

<p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-[.18em] px-3 py-2 mt-1">Sistem Manajemen</p>

<a href="/dashboard-admin" class="{{ $menuBase }} {{ request()->is('dashboard-admin*') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('dashboard-admin*') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
    </span>
    <span>Dashboard Utama</span>
</a>

<a href="/admin/users" class="{{ $menuBase }} {{ request()->is('admin/users') && !request()->has('section') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('admin/users') && !request()->has('section') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
    </span>
    <span>Manajemen Pengguna</span>
</a>

<a href="/admin/users?section=komunitas" class="{{ $menuBase }} {{ request()->query('section') === 'komunitas' ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->query('section') === 'komunitas' ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
    </span>
    <span>Manajemen Komunitas</span>
</a>

<a href="/admin/master" class="{{ $menuBase }} {{ request()->is('admin/master*') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('admin/master*') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M4 14h6v-4H4v4zm0 5h6v-4H4v4zM4 9h6V5H4v4zm7 5h6v-4h-6v4zm0 5h6v-4h-6v4zm0-14v4h6V5h-6z"/></svg>
    </span>
    <span>Manajemen Data Master</span>
</a>
