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
    <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <span class="inline-flex rounded-full border border-[#dfeccc] bg-[#edf8dc] px-3 py-1 text-[11px] font-bold text-[#3E7D00]">{{ $roleName }}</span>
            <h1 class="mt-2 text-2xl sm:text-3xl font-extrabold text-[#14280b] tracking-tight">Dashboard aktivitas pertanian</h1>
            <p class="mt-1 text-sm text-slate-500 leading-relaxed">Pantau proses tanam, pemupukan, dan riwayat panen yang terhubung dengan akun Anda.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($roleId === 1)
                <a href="{{ route('tambah.lahan') }}" class="rounded-[26px] border border-[#3E7D00] bg-white px-5 py-2.5 text-xs font-semibold text-[#3E7D00] hover:bg-[#edf8dc] transition shadow-[0_14px_38px_rgba(32,60,16,.06)]">Tambah Lahan</a>
            @endif
            <a href="{{ route('lapor.tanam') }}" class="rounded-[26px] bg-[#3E7D00] px-5 py-2.5 text-xs font-semibold text-white hover:bg-[#2f5c12] transition shadow-[0_14px_38px_rgba(32,60,16,.06)]">Lapor Tanam</a>
            @if($roleId === 1)
                <a href="{{ route('lapor.panen') }}" class="rounded-[26px] bg-[#203c10] px-5 py-2.5 text-xs font-semibold text-white hover:bg-[#14280b] transition shadow-[0_14px_38px_rgba(32,60,16,.06)]">Lapor Hasil Panen</a>
            @endif
        </div>
    </header>

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
            <p class="text-xs font-bold uppercase text-[#3E7D00]">Aturan Masa Tanam</p>
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
            <span class="text-xs font-bold text-[#3E7D00]">{{ $prosesAktif->count() }} aktif</span>
        </div>
        <div class="divide-y divide-[#edf4df]">
            @forelse($prosesAktif as $siklus)
                <article class="p-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-[#14280b]">{{ $siklus['nama_lahan'] }}</h3>
                            <p class="mt-1 text-[11px] text-slate-500">{{ $siklus['nama_bibit'] }} · {{ $siklus['peran_pelapor'] === 'brigade_pangan' ? 'Dikelola Brigade Pangan' : 'Dikelola Kelompok Tani' }}</p>
                        </div>
                        <span class="w-fit rounded-full bg-[#edf8dc] px-2.5 py-1 text-[10px] font-bold text-[#3E7D00]">Panen {{ \Carbon\Carbon::parse($siklus['estimasi_tanggal_panen'])->format('d M Y') }}</span>
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-[#5EA500]" style="width: {{ $siklus['progress_persen'] }}%"></div>
                    </div>
                    <div class="mt-2 flex justify-between text-[10px] font-semibold text-slate-500">
                        <span>{{ $siklus['progress_persen'] }}% masa tanam</span>
                        <span>{{ $siklus['hari_tersisa'] }} hari tersisa</span>
                    </div>
                    @if($roleId === 1 && $siklus['can_report_harvest'])
                        <div class="mt-3"><a href="{{ route('lapor.panen') }}" class="text-xs font-bold text-[#3E7D00] hover:underline">Input hasil panen</a></div>
                    @endif
                </article>
            @empty
                <p class="px-5 py-10 text-center text-xs text-slate-500">Belum ada proses tanam aktif.</p>
            @endforelse
        </div>
    </section>

    @if($roleId === 1)
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-[#e7efd8] px-5 py-4">
                <h2 class="text-sm font-bold text-[#14280b]">Daftar lahan milik Kelompok Tani</h2>
                <p class="mt-1 text-[11px] text-slate-500">Status pengajuan dan catatan verifikasi petugas.</p>
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
                                <a href="{{ route('lahan.edit', $item['id']) }}" class="mt-2 inline-block font-bold hover:underline">Perbaiki pengajuan</a>
                            </div>
                        @endif
                        @if(in_array($status, ['PENDING', 'DITOLAK'], true))
                            <form action="{{ route('lahan.destroy', $item['id']) }}" method="POST" class="mt-3" onsubmit="return confirm('Hapus pengajuan lahan ini?')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg border border-red-200 px-3 py-1.5 text-[11px] font-bold text-red-600 hover:bg-red-50">Hapus Pengajuan</button>
                            </form>
                        @endif
                    </article>
                @empty
                    <p class="px-5 py-10 text-center text-xs text-slate-500">Belum ada lahan yang diajukan.</p>
                @endforelse
            </div>
            @if(($lahan['last_page'] ?? 1) > 1)
                <div class="flex items-center justify-between border-t border-[#e7efd8] px-5 py-3 text-xs">
                    <span>Halaman {{ $lahan['current_page'] }} dari {{ $lahan['last_page'] }}</span>
                    <div class="flex gap-2">
                        @if(!empty($lahan['prev_page_url']))<a class="font-bold text-[#3E7D00]" href="?page={{ $lahan['current_page'] - 1 }}">Sebelumnya</a>@endif
                        @if(!empty($lahan['next_page_url']))<a class="font-bold text-[#3E7D00]" href="?page={{ $lahan['current_page'] + 1 }}">Selanjutnya</a>@endif
                    </div>
                </div>
            @endif
        </section>
    @endif
</div>
@endsection
