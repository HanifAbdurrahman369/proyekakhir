<!-- resources/views/dashboard/admin.blade.php -->
@extends('layouts.admin')

@section('title', 'Dashboard Admin')

@section('content')

{{-- Page Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-lg font-bold text-primary-900">Dashboard Admin</h1>
        <p class="text-xs text-gray-400 mt-0.5">
            Selamat datang kembali, {{ session('user.nama_lengkap', 'Admin') }} 👋 &mdash;
            {{ now()->translatedFormat('l, d F Y') }}
        </p>
    </div>
    <a href="/manajemen-user/tambah"
       class="flex items-center gap-2 bg-primary-500 hover:bg-primary-600 text-white text-xs font-semibold px-4 py-2 rounded-lg transition shadow-sm shadow-primary-200">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
        Tambah User
    </a>
</div>

{{-- Stats Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">

    <div class="bg-white rounded-xl border border-primary-100 p-4">
        <div class="w-9 h-9 rounded-lg bg-primary-100 flex items-center justify-center mb-3">
            <svg class="w-4 h-4 text-primary-700" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
        </div>
        <p class="text-xs text-gray-400 font-medium mb-1">Total Petani</p>
        <p class="text-2xl font-bold text-primary-900">1.284</p>
        <p class="text-[10px] text-primary-600 font-medium mt-1.5 flex items-center gap-1">
            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M7 14l5-5 5 5z"/></svg>
            +48 bulan ini
        </p>
    </div>

    <div class="bg-white rounded-xl border border-primary-100 p-4">
        <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center mb-3">
            <svg class="w-4 h-4 text-blue-600" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
        </div>
        <p class="text-xs text-gray-400 font-medium mb-1">Total Lahan</p>
        <p class="text-2xl font-bold text-primary-900">3.671 <span class="text-sm font-medium text-gray-400">ha</span></p>
        <p class="text-[10px] text-primary-600 font-medium mt-1.5 flex items-center gap-1">
            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M7 14l5-5 5 5z"/></svg>
            +120 ha
        </p>
    </div>

    <div class="bg-white rounded-xl border border-primary-100 p-4">
        <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center mb-3">
            <svg class="w-4 h-4 text-amber-600" viewBox="0 0 24 24" fill="currentColor"><path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2s4-4-3 1C7 9 7 15 7 15s0-3 3-5.31C13.77 7.73 17 8 17 8Z"/></svg>
        </div>
        <p class="text-xs text-gray-400 font-medium mb-1">Produksi (ton)</p>
        <p class="text-2xl font-bold text-primary-900">8.940</p>
        <p class="text-[10px] text-primary-600 font-medium mt-1.5 flex items-center gap-1">
            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M7 14l5-5 5 5z"/></svg>
            +6.2% YoY
        </p>
    </div>

    <div class="bg-white rounded-xl border border-primary-100 p-4">
        <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center mb-3">
            <svg class="w-4 h-4 text-red-500" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
        </div>
        <p class="text-xs text-gray-400 font-medium mb-1">User Pending</p>
        <p class="text-2xl font-bold text-primary-900">7</p>
        <p class="text-[10px] text-red-500 font-medium mt-1.5 flex items-center gap-1">
            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor"><path d="M7 10l5 5 5-5z"/></svg>
            perlu verifikasi
        </p>
    </div>

</div>

{{-- Content Grid --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">

    {{-- Tabel User Terbaru --}}
    <div class="bg-white rounded-xl border border-primary-100 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-primary-50">
            <h3 class="text-sm font-semibold text-primary-900">User Terbaru</h3>
            <a href="/manajemen-user" class="text-xs text-primary-600 hover:text-primary-800 font-medium">Lihat semua →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-primary-50/50">
                        <th class="text-left font-semibold text-gray-400 px-4 py-2.5">Nama</th>
                        <th class="text-left font-semibold text-gray-400 px-4 py-2.5">Role</th>
                        <th class="text-left font-semibold text-gray-400 px-4 py-2.5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($latestUsers ?? [] as $user)
                    <tr class="hover:bg-primary-50/30 transition">
                        <td class="px-4 py-2.5 text-gray-700 font-medium">{{ $user->nama_lengkap }}</td>
                        <td class="px-4 py-2.5 text-gray-500">{{ $user->role }}</td>
                        <td class="px-4 py-2.5">
                            @if ($user->status === 'aktif')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-primary-100 text-primary-800">Aktif</span>
                            @elseif ($user->status === 'pending')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800">Pending</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-100 text-red-800">Nonaktif</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Aktivitas Terbaru --}}
    <div class="bg-white rounded-xl border border-primary-100 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-primary-50">
            <h3 class="text-sm font-semibold text-primary-900">Aktivitas Terbaru</h3>
            <a href="/log" class="text-xs text-primary-600 hover:text-primary-800 font-medium">Lihat log →</a>
        </div>
        <div class="divide-y divide-gray-50 px-4">
            @foreach ($activities ?? [] as $activity)
            <div class="flex items-start gap-3 py-3">
                <div class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0
                    {{ $activity->type === 'success' ? 'bg-primary-500' : ($activity->type === 'info' ? 'bg-blue-400' : 'bg-amber-400') }}">
                </div>
                <div>
                    <p class="text-xs text-gray-700 leading-relaxed">{!! $activity->description !!}</p>
                    <p class="text-[10px] text-gray-400 mt-0.5">{{ $activity->created_at->diffForHumans() }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>

{{-- Grafik Produksi --}}
<div class="bg-white rounded-xl border border-primary-100 overflow-hidden">
    <div class="flex items-center justify-between px-4 py-3 border-b border-primary-50">
        <h3 class="text-sm font-semibold text-primary-900">Grafik Produksi Bulanan</h3>
        <span class="text-xs text-gray-400 font-medium">{{ now()->year }}</span>
    </div>
    <div class="p-4">
        {{-- Bar chart placeholder — ganti dengan Chart.js atau library pilihan Anda --}}
        <div class="flex items-end gap-1.5 h-28">
            @php
                $months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
                $values = $monthlyProduction ?? [620,540,780,710,860,920,830,940,760,680,590,0];
                $max = max($values) ?: 1;
            @endphp
            @foreach ($months as $i => $month)
            <div class="flex-1 flex flex-col items-center gap-1">
                <div class="w-full rounded-t transition"
                     style="height: {{ $values[$i] ? round($values[$i]/$max*100) : 2 }}px;
                            background: {{ $i === now()->month - 1 ? '#7CCF00' : '#D8F999' }};">
                </div>
                <span class="text-[9px] text-gray-400">{{ $month }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

@endsection