@php
    $menuBase = 'group flex items-center gap-2.5 px-3 py-2.5 rounded-2xl text-[13px] font-semibold transition-all duration-200';
    $menuActive = 'bg-[#edf8dc] text-[#203c10] shadow-sm ring-1 ring-[#dfeccc]';
    $menuIdle = 'text-slate-600 hover:bg-[#f7fced] hover:text-[#2f5c12]';
    $iconBase = 'w-8 h-8 rounded-xl flex items-center justify-center shrink-0 transition';
    $iconActive = 'bg-[#3E7D00] text-white';
    $iconIdle = 'bg-slate-100 text-slate-500 group-hover:bg-[#edf8dc] group-hover:text-[#3E7D00]';

    $roleId = (int) session('role_id');
    $totalLahan = session('total_lahan');
    if ($roleId === 1 && $totalLahan === null) {
        $token = session('token');
        if ($token) {
            try {
                $response = \Illuminate\Support\Facades\Http::withToken($token)
                    ->acceptJson()
                    ->get('http://127.0.0.1:8003/api/lahan', ['page' => 1]);
                if ($response->successful()) {
                    $totalLahan = $response->json()['data']['total'] ?? count($response->json()['data']['data'] ?? []);
                    session(['total_lahan' => $totalLahan]);
                }
            } catch (\Throwable $e) {
                $totalLahan = 0;
            }
        }
    }
    $lahanLabel = $roleId === 5 ? 'Proses Tanam' : (($totalLahan > 1) ? 'Lahan Saya' : 'Lahan Saya');
@endphp

<p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-[.18em] px-3 py-2 mt-1">Lahan & Produksi</p>

@if($roleId === 1)
<a href="{{ route('petani.dashboard') }}" class="{{ $menuBase }} {{ request()->is('dashboard-petani') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('dashboard-petani') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
    </span>
    <span>Lahan Sawah</span>
</a>
@else
<a href="{{ route('petani.dashboard') }}" class="{{ $menuBase }} {{ request()->is('dashboard-petani') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('dashboard-petani') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
    </span>
    <span>Proses Tanam</span>
</a>
@endif

<p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-[.18em] px-3 py-2 mt-4">Aktivitas</p>

@if($roleId === 1)
<a href="{{ route('tambah.lahan') }}" class="{{ $menuBase }} {{ request()->is('tambah-lahan*') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('tambah-lahan*') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
    </span>
    <span>Daftar Lahan Sawah</span>
</a>
@endif

@php
    $currentMonth = (int) now()->format('n');
    $isKelompokTaniAllowed = ($currentMonth >= 1 && $currentMonth <= 9);
    $isBrigadePanganAllowed = in_array($currentMonth, [10, 11, 12, 1], true);
    $isAllowedToPlant = ($roleId === 1 && $isKelompokTaniAllowed) || ($roleId === 5 && $isBrigadePanganAllowed);
@endphp

@if($isAllowedToPlant)
<a href="{{ route('lapor.tanam') }}" class="{{ $menuBase }} {{ request()->is('lapor-tanam*') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('lapor-tanam*') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2s4-4-3 1C7 9 7 15 7 15s0-3 3-5.31C13.77 7.73 17 8 17 8Z"/></svg>
    </span>
    <span>Lapor Tanam</span>
</a>
@else
<div class="group flex items-center gap-2.5 px-3 py-2.5 rounded-2xl text-[13px] font-semibold text-slate-400 cursor-not-allowed opacity-50 select-none" title="Masa tanam Anda sedang dikunci">
    <span class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 bg-slate-100 text-slate-400">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2s4-4-3 1C7 9 7 15 7 15s0-3 3-5.31C13.77 7.73 17 8 17 8Z"/></svg>
    </span>
    <span>Lapor Tanam (Kunci)</span>
</div>
@endif

@if($roleId === 1)
<a href="{{ route('lapor.panen') }}" class="{{ $menuBase }} {{ request()->is('lapor-panen*') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('lapor-panen*') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M19 15c-1.1 0-2 .9-2 2v3c0 1.1-.9 2-2 2H5c-1.1 0-2-.9-2-2V8c0-1.1.9-2 2-2h3V4H5C2.8 4 1 5.8 1 8v12c0 2.2 1.8 4 4 4h10c2.2 0 4-1.8 4-4v-3c0-1.1-.9-2-2-2zm-3-4V3c0-1.1-.9-2-2-2H8c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h6c1.1 0 2-.9 2-2zm-2 0H8V3h6v8z"/></svg>
    </span>
    <span>Lapor Panen</span>
</a>
@endif

<a href="{{ route('riwayat.panen') }}" class="{{ $menuBase }} {{ request()->is('riwayat-panen*') ? $menuActive : $menuIdle }}">
    <span class="{{ $iconBase }} {{ request()->is('riwayat-panen*') ? $iconActive : $iconIdle }}">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
    </span>
    <span>Riwayat Aktivitas</span>
</a>
