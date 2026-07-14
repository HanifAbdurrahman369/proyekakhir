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
            <span class="inline-flex rounded-full border border-[#d1fae5] bg-[#ecfdf5] px-3 py-1 text-[11px] font-bold text-[#047857]">{{ $roleName }}</span>
            <h1 class="mt-2 text-2xl sm:text-3xl font-extrabold text-[#022c22] tracking-tight">Dashboard aktivitas pertanian</h1>
            <p class="mt-1 text-sm text-slate-500 leading-relaxed">Pantau proses tanam, pemupukan, dan riwayat panen yang terhubung dengan akun Anda.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(in_array($roleId, [1, 5], true))
                <a href="{{ route('tambah.lahan') }}" class="inline-flex items-center gap-2 rounded-[26px] border border-[#047857] bg-white px-5 py-2.5 text-xs font-semibold text-[#047857] hover:bg-[#ecfdf5] transition shadow-[0_14px_38px_rgba(4,120,87,.06)]">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    <span>Tambah Lahan</span>
                </a>
            @endif
            
            <a href="{{ route('lapor.tanam') }}" class="inline-flex items-center gap-2 rounded-[26px] bg-[#047857] px-5 py-2.5 text-xs font-semibold text-white hover:bg-[#065f46] transition shadow-[0_14px_38px_rgba(4,120,87,.16)]">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2s4-4-3 1C7 9 7 15 7 15s0-3 3-5.31C13.77 7.73 17 8 17 8Z"/>
                </svg>
                <span>Lapor Tanam</span>
            </a>

            @if(in_array($roleId, [1, 5], true))
                <a href="{{ route('lapor.panen') }}" class="inline-flex items-center gap-2 rounded-[26px] bg-[#047857] px-5 py-2.5 text-xs font-semibold text-white hover:bg-[#065f46] transition shadow-[0_14px_38px_rgba(4,120,87,.16)]">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19 15c-1.1 0-2 .9-2 2v3c0 1.1-.9 2-2 2H5c-1.1 0-2-.9-2-2V8c0-1.1.9-2 2-2h3V4H5C2.8 4 1 5.8 1 8v12c0 2.2 1.8 4 4 4h10c2.2 0 4-1.8 4-4v-3c0-1.1-.9-2-2-2zm-3-4V3c0-1.1-.9-2-2-2H8c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h6c1.1 0 2-.9 2-2zm-2 0H8V3h6v8z"/>
                    </svg>
                    <span>Lapor Hasil Panen</span>
                </a>
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
            <p class="text-xs font-bold uppercase text-slate-500">Lahan Terdaftar</p>
            <p class="mt-2 text-3xl font-bold text-[#022c22]">{{ $totalLahan }}</p>
            <p class="mt-1 text-xs text-slate-500">Pengajuan lahan pada akun Anda</p>
        </div>
        <div class="rounded-2xl bg-[#065f46] p-5 text-white shadow-sm transition-all hover:shadow-md">
            <p class="text-xs font-bold uppercase text-white/80">Produksi Tahun Ini</p>
            <p class="mt-2 text-3xl font-bold">{{ number_format((float) $totalProduksi, 2, ',', '.') }} <span class="text-sm font-normal text-white/70">Ton</span></p>
            <p class="mt-1 text-xs text-white/70">Hanya hasil panen yang telah disetujui petugas</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-[#ecfdf5] p-5 shadow-sm transition-all hover:shadow-md">
            <p class="text-xs font-bold uppercase text-[#047857]">Proses Aktif</p>
            <p class="mt-2 text-3xl font-bold text-[#022c22]">{{ $prosesAktif->count() }}</p>
            <p class="mt-1 text-xs text-slate-600">Siklus tanam berjalan yang terhubung dengan akun Anda</p>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-[#d1fae5] px-5 py-4">
            <div>
                <h2 class="text-sm font-bold text-[#022c22]">Padi dalam masa tanam</h2>
                <p class="mt-1 text-[11px] text-slate-500">Progres dihitung otomatis sampai estimasi masa panen.</p>
            </div>
            <span class="text-xs font-bold text-[#047857]">{{ $prosesAktif->count() }} aktif</span>
        </div>
        <div class="divide-y divide-[#d1fae5]">
            @forelse($prosesAktif as $siklus)
                <article class="p-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-[#022c22]">{{ $siklus['nama_lahan'] }}</h3>
                            <p class="mt-1 text-[11px] text-slate-500">{{ $siklus['nama_bibit'] }} · Dikelola Sendiri</p>
                        </div>
                        <span class="w-fit rounded-full bg-[#ecfdf5] px-2.5 py-1 text-[10px] font-bold text-[#047857]">Panen {{ \Carbon\Carbon::parse($siklus['estimasi_tanggal_panen'])->format('d M Y') }}</span>
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-[#047857]" style="width: {{ $siklus['progress_persen'] }}%"></div>
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
            <div class="border-b border-[#d1fae5] px-5 py-4">
                <h2 class="text-sm font-bold text-[#022c22]">Daftar lahan sawah</h2>
                <p class="mt-1 text-[11px] text-slate-500">Status pengajuan dan catatan verifikasi petugas.</p>
            </div>
            <div class="divide-y divide-[#d1fae5]">
                @forelse($lahan['data'] ?? [] as $item)
                    @php
                        $status = $item['status_verifikasi'] ?? 'PENDING';
                        $statusClass = $status === 'DITERIMA' ? 'bg-emerald-50 text-emerald-700' : ($status === 'DITOLAK' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700');
                    @endphp
                    <article class="p-5 cursor-pointer hover:bg-slate-50 transition-colors" onclick="openLahanModal(this)" data-lahan="{{ base64_encode(json_encode($item)) }}">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-[#022c22]">{{ $item['nama_lahan'] }}</h3>
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
                    <p class="px-5 py-10 text-center text-xs text-slate-500">Belum ada lahan yang diajukan.</p>
                @endforelse
            </div>
            @if(($lahan['last_page'] ?? 1) > 1)
                <div class="flex items-center justify-between border-t border-[#d1fae5] px-5 py-3 text-xs">
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

<!-- Modal Detail Lahan -->
<div id="lahanDetailModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 backdrop-blur-sm opacity-0 transition-opacity duration-300">
    <div class="w-full max-w-md scale-95 rounded-3xl bg-white shadow-2xl transition-transform duration-300 overflow-hidden m-4 max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 bg-slate-50">
            <h3 class="text-lg font-bold text-slate-800">Detail Lahan</h3>
            <button onclick="closeLahanModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        
        <!-- Body -->
        <div class="p-6 overflow-y-auto">
            <!-- Status Badge -->
            <div class="mb-5 flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Status Pengajuan</span>
                <span id="modalLahanStatus" class="px-3 py-1 rounded-full text-xs font-bold"></span>
            </div>

            <!-- Flow -->
            <div class="mb-6 relative">
                <div class="absolute left-[15px] top-4 bottom-4 w-0.5 bg-slate-200 -z-10"></div>
                <div class="space-y-4">
                    <!-- Step 1 -->
                    <div class="flex gap-4">
                        <div class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-sm shadow-emerald-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-800">1. Isi Formulir</p>
                            <p class="text-xs text-slate-500">Data lahan telah berhasil disubmit.</p>
                        </div>
                    </div>
                    <!-- Step 2 -->
                    <div class="flex gap-4">
                        <div id="step2Icon" class="w-8 h-8 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center shrink-0 transition-colors duration-300">2</div>
                        <div>
                            <p class="text-sm font-bold text-slate-800">2. Persetujuan Petugas</p>
                            <p id="step2Desc" class="text-xs text-slate-500">Tunggu Petugas menyetujui pengajuan Anda.</p>
                        </div>
                    </div>
                    <!-- Step 3 -->
                    <div class="flex gap-4">
                        <div id="step3Icon" class="w-8 h-8 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center shrink-0 transition-colors duration-300">3</div>
                        <div>
                            <p class="text-sm font-bold text-slate-800">3. Hubungi Petugas</p>
                            <p id="step3Desc" class="text-xs text-slate-500">Jika disetujui, hubungi petugas untuk pemetaan lahan.</p>
                        </div>
                    </div>
                    <!-- Step 4 -->
                    <div class="flex gap-4">
                        <div id="step4Icon" class="w-8 h-8 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center shrink-0 transition-colors duration-300">4</div>
                        <div>
                            <p class="text-sm font-bold text-slate-800">4. Terverifikasi</p>
                            <p id="step4Desc" class="text-xs text-slate-500">Lahan terpetakan dan siap digunakan untuk panen.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lahan Details -->
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 space-y-3">
                <div>
                    <p class="text-[10px] uppercase font-bold text-slate-400">Nama Lahan</p>
                    <p id="modalLahanName" class="text-sm font-bold text-slate-800"></p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-[10px] uppercase font-bold text-slate-400">Luas Lahan</p>
                        <p id="modalLahanLuas" class="text-sm font-bold text-slate-800"></p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-slate-400">Kecamatan</p>
                        <p id="modalLahanKecamatan" class="text-sm font-bold text-slate-800"></p>
                    </div>
                </div>
                <div>
                    <p class="text-[10px] uppercase font-bold text-slate-400">Alamat Lengkap</p>
                    <p id="modalLahanAlamat" class="text-xs text-slate-700"></p>
                </div>
            </div>
            
            <!-- Rejection Reason -->
            <div id="modalLahanReject" class="mt-4 hidden p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700">
            </div>

        </div>

        <!-- Footer -->
        <div class="border-t border-slate-100 p-4 bg-slate-50 flex justify-end gap-3">
            <button onclick="closeLahanModal()" class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 hover:text-slate-900 transition">Tutup</button>
            <a id="modalLahanWA" href="#" target="_blank" class="hidden gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-emerald-500 hover:bg-emerald-600 transition shadow-md shadow-emerald-500/20">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                Hubungi Petugas
            </a>
        </div>
    </div>
</div>

<script>
    function openLahanModal(element) {
        let item = {};
        try {
            item = JSON.parse(atob(element.getAttribute('data-lahan')));
        } catch(e) {
            console.error('Invalid lahan data', e);
            return;
        }

        const modal = document.getElementById('lahanDetailModal');
        const modalBox = modal.querySelector('div.scale-95');
        
        // Populate text
        document.getElementById('modalLahanName').innerText = item.nama_lahan || '-';
        document.getElementById('modalLahanLuas').innerText = (item.luas_lahan_hektar || 0) + ' Ha';
        document.getElementById('modalLahanKecamatan').innerText = item.nama_kecamatan || item.kecamatan || '-';
        document.getElementById('modalLahanAlamat').innerText = item.alamat_detail || '-';
        
        // Status Badge & Flow logic
        const statusEl = document.getElementById('modalLahanStatus');
        const waBtn = document.getElementById('modalLahanWA');
        const rejectBox = document.getElementById('modalLahanReject');
        
        const checkIcon = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>`;
        const idleIconCls = 'w-8 h-8 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center shrink-0 transition-colors duration-300';
        const successIconCls = 'w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-sm shadow-emerald-200 transition-colors duration-300';
        const activeIconCls = 'w-8 h-8 rounded-full bg-emerald-100 border-2 border-emerald-500 text-emerald-600 flex items-center justify-center shrink-0 transition-colors duration-300 font-bold';
        
        // Reset Steps Default
        document.getElementById('step2Icon').className = idleIconCls;
        document.getElementById('step2Icon').innerHTML = '2';
        document.getElementById('step2Desc').innerText = 'Tunggu Petugas menyetujui pengajuan Anda.';
        
        document.getElementById('step3Icon').className = idleIconCls;
        document.getElementById('step3Icon').innerHTML = '3';
        document.getElementById('step3Desc').innerText = 'Jika disetujui, hubungi petugas untuk pemetaan lahan.';
        
        document.getElementById('step4Icon').className = idleIconCls;
        document.getElementById('step4Icon').innerHTML = '4';
        document.getElementById('step4Desc').innerText = 'Lahan terpetakan dan siap digunakan untuk panen.';

        waBtn.classList.add('hidden');
        waBtn.classList.remove('inline-flex', 'items-center');
        rejectBox.classList.add('hidden');

        // Status Evaluation
        let statusText = item.status_verifikasi || 'PENDING';
        const hasPolygon = item.polygon_geojson || item.geojson || item.polygon_area;
        if (hasPolygon) statusText = 'TERVERIFIKASI';

        if (statusText === 'DITERIMA' || statusText === 'TERVERIFIKASI') {
            statusEl.className = 'px-3 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700';
            statusEl.innerText = statusText;
            
            // Step 2 Success
            document.getElementById('step2Icon').className = successIconCls;
            document.getElementById('step2Icon').innerHTML = checkIcon;
            document.getElementById('step2Desc').innerText = 'Pengajuan telah disetujui petugas.';
            
            // WA Button logic
            waBtn.classList.remove('hidden');
            waBtn.classList.add('inline-flex', 'items-center');
            const rawPhone = item.petugas_no_hp || '6285753510996'; 
            const phone = rawPhone.replace(/^0/, '62');
            const message = `Halo Petugas, pengajuan lahan sawah saya bernama *${item.nama_lahan}* seluas *${item.luas_lahan_hektar} Ha* telah disetujui. Saya ingin berkoordinasi untuk pemetaan poligon lahan.`;
            waBtn.href = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;

            if (statusText === 'TERVERIFIKASI') {
                document.getElementById('step3Icon').className = successIconCls;
                document.getElementById('step3Icon').innerHTML = checkIcon;
                document.getElementById('step3Desc').innerText = 'Sudah berkoordinasi dengan petugas.';

                document.getElementById('step4Icon').className = successIconCls;
                document.getElementById('step4Icon').innerHTML = checkIcon;
                document.getElementById('step4Desc').innerText = 'Lahan telah terpetakan dan siap digunakan.';
            } else {
                document.getElementById('step3Icon').className = activeIconCls;
                document.getElementById('step3Desc').innerText = 'Silakan tekan tombol Hubungi Petugas via WhatsApp.';
            }

        } else if (statusText === 'DITOLAK') {
            statusEl.className = 'px-3 py-1 rounded-full text-[10px] font-bold bg-red-50 text-red-700';
            statusEl.innerText = 'DITOLAK';
            
            document.getElementById('step2Icon').className = 'w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center shrink-0 shadow-sm shadow-red-200 transition-colors duration-300';
            document.getElementById('step2Icon').innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>';
            document.getElementById('step2Desc').innerText = 'Pengajuan lahan ditolak.';
            
            rejectBox.classList.remove('hidden');
            rejectBox.innerHTML = `<strong>Catatan Penolakan:</strong><br>${item.alasan_penolakan || item.catatan_verifikasi || 'Perbaiki data lahan Anda.'}`;
        } else {
            statusEl.className = 'px-3 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700';
            statusEl.innerText = 'PENDING';
            document.getElementById('step2Icon').className = activeIconCls;
        }

        // Show Modal
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        void modal.offsetWidth; // Trigger reflow
        modal.classList.remove('opacity-0');
        modalBox.classList.remove('scale-95');
    }

    function closeLahanModal() {
        const modal = document.getElementById('lahanDetailModal');
        const modalBox = modal.querySelector('div.scale-95') || modal.children[0];
        
        modal.classList.add('opacity-0');
        modalBox.classList.add('scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }
</script>
</div>
@endsection
