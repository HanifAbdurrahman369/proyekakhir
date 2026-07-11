@extends('layouts.app')

@php
    $roleId = $roleId ?? (int) session('role_id');
    $roleName = $roleName ?? ($roleId === 5 ? 'Brigade Pangan' : 'Kelompok Tani');
    $prosesAktif = collect($siklusTanam ?? [])->where('status_aktif', 'AKTIF');
    $totalLahan = (int) ($lahan['total'] ?? count($lahan['data'] ?? []));
@endphp

@section('title', 'Dashboard ' . $roleName)

@section('content')
<div class="space-y-6">
    @php
        $currentMonth = (int) now()->format('n');
        $isKelompokTaniAllowed = ($currentMonth >= 1 && $currentMonth <= 9);
        $isBrigadePanganAllowed = in_array($currentMonth, [10, 11, 12, 1], true);
        $hasOwnLand = ($roleId === 5 && $totalLahan > 0);
        $isAllowedToPlant = ($roleId === 1 && $isKelompokTaniAllowed) || ($roleId === 5 && $isBrigadePanganAllowed) || $hasOwnLand;
    @endphp

    <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <span class="inline-flex rounded-full border border-[#dfeccc] bg-[#edf8dc] px-3 py-1 text-[11px] font-bold text-[#047857]">{{ $roleName }}</span>
            <h1 class="mt-2 text-2xl sm:text-3xl font-extrabold text-[#14280b] tracking-tight">Dashboard aktivitas pertanian</h1>
            <p class="mt-1 text-sm text-slate-500 leading-relaxed">Pantau proses tanam, pemupukan, dan riwayat panen yang terhubung dengan akun Anda.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(in_array($roleId, [1, 5], true))
                <a href="{{ route('tambah.lahan') }}" class="inline-flex items-center gap-2 rounded-[26px] border border-[#047857] bg-white px-5 py-2.5 text-xs font-semibold text-[#047857] hover:bg-[#edf8dc] transition shadow-[0_14px_38px_rgba(32,60,16,.06)]">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Tambah Lahan</span>
                </a>
            @endif
            
            @if($isAllowedToPlant)
                <a href="{{ route('lapor.tanam') }}" class="inline-flex items-center gap-2 rounded-[26px] bg-[#047857] px-5 py-2.5 text-xs font-semibold text-white hover:bg-[#2f5c12] transition shadow-[0_14px_38px_rgba(32,60,16,.06)]">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2s4-4-3 1C7 9 7 15 7 15s0-3 3-5.31C13.77 7.73 17 8 17 8Z"/>
                    </svg>
                    <span>Lapor Tanam</span>
                </a>
            @else
                <button type="button" disabled class="inline-flex items-center gap-2 rounded-[26px] bg-slate-300 px-5 py-2.5 text-xs font-semibold text-slate-500 cursor-not-allowed shadow-[0_14px_38px_rgba(32,60,16,.06)]" title="Masa tanam Anda sedang dikunci">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <span>Lapor Tanam (Kunci)</span>
                </button>
            @endif

            @if(in_array($roleId, [1, 5], true))
                <a href="{{ route('lapor.panen') }}" class="inline-flex items-center gap-2 rounded-[26px] bg-[#203c10] px-5 py-2.5 text-xs font-semibold text-white hover:bg-[#14280b] transition shadow-[0_14px_38px_rgba(32,60,16,.06)]">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 15c-1.1 0-2 .9-2 2v3c0 1.1-.9 2-2 2H5c-1.1 0-2-.9-2-2V8c0-1.1.9-2 2-2h3V4H5C2.8 4 1 5.8 1 8v12c0 2.2 1.8 4 4 4h10c2.2 0 4-1.8 4-4v-3c0-1.1-.9-2-2-2zm-3-4V3c0-1.1-.9-2-2-2H8c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h6c1.1 0 2-.9 2-2zm-2 0H8V3h6v8z"/>
                    </svg>
                    <span>Lapor Hasil Panen</span>
                </a>
            @endif
        </div>
    </header>

    @if(!$isAllowedToPlant)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-amber-800 text-sm font-semibold flex items-start gap-3 shadow-sm">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <div>
                <p class="font-bold">Masa Tanam Sedang Terkunci</p>
                <p class="text-xs text-amber-700 mt-0.5 leading-relaxed">
                    Saat ini (Bulan {{ now()->translatedFormat('F') }}) bukan jadwal masa tanam Anda. 
                    Masa tanam untuk <strong>{{ $roleId === 5 ? 'Brigade Pangan adalah Oktober - Januari' : 'Kelompok Tani adalah Januari - September' }}</strong>. 
                    Tombol lapor tanam dinonaktifkan sementara.
                </p>
            </div>
        </div>
    @endif

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-xs font-semibold text-red-700">{{ session('error') }}</div>
    @endif

    <section class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:shadow-md">
            <p class="text-xs font-bold uppercase text-slate-500">{{ $roleId === 5 ? 'Proses Aktif' : 'Lahan Terdaftar' }}</p>
            <p class="mt-2 text-3xl font-bold text-[#14280b]">{{ $roleId === 5 ? $prosesAktif->count() : $totalLahan }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $roleId === 5 ? 'Siklus tanam yang sedang digarap' : 'Pengajuan lahan pada akun Anda' }}</p>
        </div>
        <div class="rounded-2xl bg-[#203c10] p-5 text-white shadow-sm transition-all hover:shadow-md">
            <p class="text-xs font-bold uppercase text-white/80">Produksi Tahun Ini</p>
            <p class="mt-2 text-3xl font-bold">{{ number_format((float) $totalProduksi, 2, ',', '.') }} <span class="text-sm font-normal text-white/70">Ton</span></p>
            <p class="mt-1 text-xs text-white/70">Hanya hasil panen yang telah disetujui petugas</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-[#f7fced] p-5 shadow-sm transition-all hover:shadow-md">
            <p class="text-xs font-bold uppercase text-[#047857]">Aturan Masa Tanam</p>
            <p class="mt-2 text-lg font-bold text-[#14280b]">{{ $roleId === 5 ? 'Oktober - Januari' : 'Januari - September' }}</p>
            <p class="mt-1 text-xs text-slate-600">{{ $roleId === 5 ? 'Bibit unggul untuk lahan Kelompok Tani induk' : 'Bibit lokal sebagai pemilik lahan' }}</p>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-[#e7efd8] px-5 py-4">
            <div>
                <h2 class="text-sm font-bold text-[#14280b]">Padi dalam masa tanam</h2>
                <p class="mt-1 text-[11px] text-slate-500">Progres dihitung otomatis sampai estimasi masa panen.</p>
            </div>
            <span class="text-xs font-bold text-[#047857]">{{ $prosesAktif->count() }} aktif</span>
        </div>
        <div class="divide-y divide-[#edf4df]">
            @forelse($prosesAktif as $siklus)
                <article class="p-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-[#14280b]">{{ $siklus['nama_lahan'] }}</h3>
                            <p class="mt-1 text-[11px] text-slate-500">{{ $siklus['nama_bibit'] }} · Dikelola Sendiri</p>
                        </div>
                        <span class="w-fit rounded-full bg-[#edf8dc] px-2.5 py-1 text-[10px] font-bold text-[#047857]">Panen {{ \Carbon\Carbon::parse($siklus['estimasi_tanggal_panen'])->format('d M Y') }}</span>
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-[#10b981]" style="width: {{ $siklus['progress_persen'] }}%"></div>
                    </div>
                    <div class="mt-2 flex justify-between text-[10px] font-semibold text-slate-500">
                        <span>{{ $siklus['progress_persen'] }}% masa tanam</span>
                        <span>{{ $siklus['hari_tersisa'] }} hari tersisa</span>
                    </div>
                    @if(in_array($roleId, [1, 5], true) && $siklus['can_report_harvest'])
                        <div class="mt-3"><a href="{{ route('lapor.panen') }}" class="text-xs font-bold text-[#047857] hover:underline">Input hasil panen</a></div>
                    @endif
                </article>
            @empty
                <p class="px-5 py-10 text-center text-xs text-slate-500">Belum ada proses tanam aktif.</p>
            @endforelse
        </div>
    </section>
    @if(in_array($roleId, [1, 5], true))
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-[#e7efd8] px-5 py-4">
                <h2 class="text-sm font-bold text-[#14280b]">{{ $roleId === 5 ? 'Daftar lahan garapan Brigade Pangan' : 'Daftar lahan milik Kelompok Tani' }}</h2>
                <p class="mt-1 text-[11px] text-slate-500">{{ $roleId === 5 ? 'Daftar lahan yang Anda garap dan kelola.' : 'Status pengajuan dan catatan verifikasi petugas.' }}</p>
            </div>
            <div class="divide-y divide-[#edf4df]">
                @forelse($lahan['data'] ?? [] as $item)
                    @php
                        $status = $item['status_verifikasi'] ?? 'PENDING';
                        $statusClass = $status === 'DITERIMA' ? 'bg-emerald-50 text-emerald-700' : ($status === 'DITOLAK' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700');
                    @endphp
                    <article class="p-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-[#14280b]">{{ $item['nama_lahan'] }}</h3>
                                <p class="mt-1 text-[11px] text-slate-500">{{ $item['alamat_detail'] ?? '-' }} · {{ $item['luas_lahan_hektar'] ?? 0 }} Ha</p>
                            </div>
                            <span class="w-fit rounded-full px-2.5 py-1 text-[10px] font-bold {{ $statusClass }}">{{ str_replace('_', ' ', $status) }}</span>
                        </div>
                        @if($status === 'DITOLAK')
                            <div class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-700">
                                <p>{{ $item['alasan_penolakan'] ?? $item['catatan_verifikasi'] ?? 'Pengajuan perlu diperbaiki.' }}</p>
                                @if(in_array($roleId, [1, 5], true))
                                    <a href="{{ route('lahan.edit', $item['id']) }}" class="mt-2 inline-block font-bold hover:underline">Perbaiki pengajuan</a>
                                @endif
                            </div>
                        @endif
                    </article>
                @empty
                    <p class="px-5 py-10 text-center text-xs text-slate-500">{{ $roleId === 5 ? 'Belum ada lahan yang ditugaskan.' : 'Belum ada lahan yang diajukan.' }}</p>
                @endforelse
            </div>
            @if(($lahan['last_page'] ?? 1) > 1)
                <div class="flex items-center justify-between border-t border-[#e7efd8] px-5 py-3 text-xs">
                    <span>Halaman {{ $lahan['current_page'] }} dari {{ $lahan['last_page'] }}</span>
                    <div class="flex gap-2">
                        @if(!empty($lahan['prev_page_url']))<a class="font-bold text-[#047857]" href="?page={{ $lahan['current_page'] - 1 }}">Sebelumnya</a>@endif
                        @if(!empty($lahan['next_page_url']))<a class="font-bold text-[#047857]" href="?page={{ $lahan['current_page'] + 1 }}">Selanjutnya</a>@endif
                    </div>
                </div>
            @endif
        </section>
    @endif
</div>
@endsection
