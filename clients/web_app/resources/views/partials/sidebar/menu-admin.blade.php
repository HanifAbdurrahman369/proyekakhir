@php
    $menuBase = 'group flex items-center gap-3 px-3.5 py-3 rounded-2xl text-[13px] font-semibold transition-all duration-200';
    $menuActive = 'bg-[#edf8dc] text-[#203c10] shadow-sm ring-1 ring-[#dfeccc]';
    $menuIdle = 'text-slate-600 hover:bg-[#f7fced] hover:text-[#2f5c12]';
    $iconBase = 'w-9 h-9 rounded-xl flex items-center justify-center shrink-0 transition';
    $iconActive = 'bg-[#3E7D00] text-white';
    $iconIdle = 'bg-slate-100 text-slate-500 group-hover:bg-[#edf8dc] group-hover:text-[#3E7D00]';
@endphp

<p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-[.18em] px-3 py-2 mt-1">Sistem Manajemen</p>

<a href="/admin/users" class="{{ $menuBase }} {{ request()->is('admin/users*') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('admin/users*') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
    </span>
    <span>Manajemen Pengguna</span>
</a>

<a href="/admin/master" class="{{ $menuBase }} {{ request()->is('admin/master*') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('admin/master*') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M4 14h6v-4H4v4zm0 5h6v-4H4v4zM4 9h6V5H4v4zm7 5h6v-4h-6v4zm0 5h6v-4h-6v4zm0-14v4h6V5h-6z"/></svg>
    </span>
    <span>Manajemen Data Master</span>
</a>
