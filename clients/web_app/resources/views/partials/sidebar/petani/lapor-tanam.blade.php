@extends('layouts.app')

@php
    $roleId = (int) session('role_id');
    $roleName = $roleId === 5 ? 'Brigade Pangan' : 'Kelompok Tani';
    $isEdit = !empty($editTanam);
    $aktif = collect($siklusTanam ?? [])->where('status_aktif', 'AKTIF');
@endphp

@section('title', $isEdit ? 'Perbarui Laporan Tanam' : 'Lapor Tanam')

@section('content')
<div class="max-w-[1400px] mx-auto px-4 sm:px-6 py-6 space-y-6">
    <header class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[11px] font-bold uppercase text-[#047857]">{{ $roleName }}</p>
            <h1 class="mt-1 text-xl font-bold text-[#022c22]">{{ $isEdit ? 'Perbarui laporan tanam' : 'Laporan tanam dan pemupukan' }}</h1>
            <p class="mt-1 text-xs text-slate-500">Estimasi panen dihitung otomatis berdasarkan masa varietas bibit.</p>
        </div>
        <span class="inline-flex w-fit rounded-full border border-[#d1fae5] bg-[#ecfdf5] px-3 py-1 text-[11px] font-bold text-[#047857]">
            Akses lapor tanam aktif
        </span>
    </header>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-xs font-semibold text-red-700">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <!-- Alur Pelaporan Tanam -->
    <div class="mb-8">
        <h2 class="text-lg font-bold text-slate-800 text-center mb-6">Panduan 3 Langkah Memulai Masa Tanam</h2>
        
        <div class="relative flex flex-col md:flex-row justify-between items-center md:items-start gap-6 md:gap-4">
            <!-- Line connector for desktop -->
            <div class="hidden md:block absolute top-6 left-[15%] right-[15%] h-1 bg-slate-200 -z-10 rounded-full overflow-hidden">
                <div class="h-full bg-emerald-500 w-full animate-[shimmer_3s_infinite] origin-left" style="background: linear-gradient(90deg, transparent, rgba(4,120,87,0.5), transparent); background-size: 200% 100%;"></div>
            </div>

            <!-- Step 1 -->
            <div class="flex flex-col items-center text-center w-full md:w-1/3 group">
                <div class="w-12 h-12 rounded-2xl bg-white border-2 border-emerald-500 text-emerald-600 shadow-lg flex items-center justify-center font-black text-xl mb-3 relative group-hover:-translate-y-1 transition-transform duration-300">
                    1
                    <div class="absolute inset-0 bg-emerald-50 rounded-2xl scale-0 group-hover:scale-100 transition-transform duration-300 -z-10"></div>
                </div>
                <h3 class="font-bold text-slate-800 text-sm mb-1">Pilih Sawah & Bibit</h3>
                <p class="text-xs text-slate-500">Pilih lahan sawah terverifikasi dan jenis varietas bibit yang akan ditanam.</p>
            </div>

            <!-- Step 2 -->
            <div class="flex flex-col items-center text-center w-full md:w-1/3 group">
                <div class="w-12 h-12 rounded-2xl bg-white border-2 border-emerald-500 text-emerald-600 shadow-lg flex items-center justify-center font-black text-xl mb-3 relative group-hover:-translate-y-1 transition-transform duration-300">
                    2
                    <div class="absolute inset-0 bg-emerald-50 rounded-2xl scale-0 group-hover:scale-100 transition-transform duration-300 -z-10"></div>
                </div>
                <h3 class="font-bold text-slate-800 text-sm mb-1">Info Pemupukan</h3>
                <p class="text-xs text-slate-500">Masukkan takaran awal pupuk yang diberikan sebelum proses tanam dimulai.</p>
            </div>

            <!-- Step 3 -->
            <div class="flex flex-col items-center text-center w-full md:w-1/3 group">
                <div class="w-12 h-12 rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-500/30 flex items-center justify-center mb-3 relative group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h3 class="font-bold text-emerald-600 text-sm mb-1">Mulai Tanam</h3>
                <p class="text-xs text-slate-500">Laporan tersimpan dan progres masa tanam akan dihitung secara otomatis.</p>
            </div>
        </div>
    </div>
    
    <style>
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden lg:col-span-12">
            <div class="border-b border-[#d1fae5] px-5 py-4">
                <h2 class="text-sm font-bold text-[#022c22]">{{ $isEdit ? 'Informasi tanam yang diperbarui' : 'Informasi Tanam & Pemupukan Baru' }}</h2>
                <p class="mt-1 text-[11px] text-slate-500">Pilih lahan, bibit, estimasi panen, serta informasi awal pemupukan sebelum tanam dimulai.</p>
            </div>
            <form method="POST" action="{{ $isEdit ? route('lapor.tanam.update', $editTanam['id']) : route('lapor.tanam.store') }}" class="p-5">
                @csrf
                @if($isEdit) @method('PUT') @endif
                
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Bagian Informasi Tanam -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-bold text-[#047857] border-b pb-2 mb-3">Informasi Tanam</h3>
                        
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-slate-700">Lahan sawah</label>
                            <select name="lahan_id" id="lahan-select" required class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-800 shadow-sm transition-all hover:border-[#047857] focus:border-[#047857] focus:outline-none focus:ring-2 focus:ring-[#047857]/20">
                                <option value="">Pilih lahan terverifikasi</option>
                                @foreach($lahan ?? [] as $item)
                                    <option value="{{ $item['id'] }}"
                                            data-luas-lahan="{{ $item['luas_lahan_hektar'] ?? 0 }}"
                                            data-luas-tanam="{{ $item['luas_tanam_hektar'] ?? $item['luas_lahan_hektar'] ?? 0 }}"
                                            @selected((string) old('lahan_id', $editTanam['lahan_id'] ?? '') === (string) $item['id'])>
                                        {{ $item['nama_lahan'] }} - {{ $item['pemilik_lahan'] ?? 'Pemilik belum dicatat' }}
                                    </option>
                                @endforeach
                            </select>
                            @if(empty($lahan))
                                <p class="mt-1.5 text-[11px] text-amber-700">Belum ada lahan terverifikasi yang ditugaskan untuk akun ini.</p>
                            @endif
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-slate-700">Luas tanam (hektar)</label>
                            <input type="number" name="luas_tanam_hektar" id="luas-tanam-input" min="0.01" step="0.01" required
                                   value="{{ old('luas_tanam_hektar', $editTanam['luas_tanam_hektar'] ?? '') }}"
                                   placeholder="Cth: 1.25"
                                   class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-800 shadow-sm transition-all hover:border-[#047857] focus:border-[#047857] focus:outline-none focus:ring-2 focus:ring-[#047857]/20">
                            <p id="luas-tanam-hint" class="mt-1 text-[10px] text-slate-500">Masukkan luas lahan yang benar-benar ditanami padi.</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-slate-700">Jenis bibit</label>
                            <select name="bibit_id" required id="bibit-select" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-800 shadow-sm transition-all hover:border-[#047857] focus:border-[#047857] focus:outline-none focus:ring-2 focus:ring-[#047857]/20">
                                <option value="">Pilih bibit</option>
                                @foreach($bibit ?? [] as $item)
                                    <option value="{{ $item['id'] }}" data-hari="{{ $item['masa_tanam_hari'] }}" @selected((string) old('bibit_id', $editTanam['bibit_id'] ?? '') === (string) $item['id'])>
                                        {{ $item['nama_bibit'] }} - {{ $item['masa_tanam_hari'] }} hari
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1.5 block text-xs font-bold text-slate-700">Tanggal tanam</label>
                                <input type="date" name="tanggal_tanam" id="tanggal-tanam" max="{{ date('Y-m-d') }}" required
                                       value="{{ old('tanggal_tanam', $editTanam['tanggal_tanam'] ?? date('Y-m-d')) }}"
                                       class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-800 shadow-sm transition-all hover:border-[#047857] focus:border-[#047857] focus:outline-none focus:ring-2 focus:ring-[#047857]/20">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-bold text-slate-700">Tanggal Panen (Opsional)</label>
                                <input type="date" name="tanggal_panen_estimasi" id="tanggal-panen-estimasi"
                                       value="{{ old('tanggal_panen_estimasi', $editTanam['estimasi_tanggal_panen'] ?? '') }}"
                                       class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-800 shadow-sm transition-all hover:border-[#047857] focus:border-[#047857] focus:outline-none focus:ring-2 focus:ring-[#047857]/20">
                                <p class="mt-1 text-[10px] text-slate-500">Jika diisi, estimasi hari akan dihitung otomatis.</p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1.5 block text-xs font-bold text-slate-700">Estimasi Tanam (Hari)</label>
                                <input type="number" name="estimasi_hari_tanam" id="estimasi-hari" min="1" required
                                       value="{{ old('estimasi_hari_tanam', $editTanam['estimasi_panen'] ?? '') }}"
                                       placeholder="Cth: 120"
                                       class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-800 shadow-sm transition-all hover:border-[#047857] focus:border-[#047857] focus:outline-none focus:ring-2 focus:ring-[#047857]/20">
                                <p class="mt-1 text-[10px] text-slate-500">Otomatis terisi berdasarkan bibit atau pilihan tanggal panen di atas.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Bagian Informasi Pemupukan -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-bold text-[#047857] border-b pb-2 mb-3">Informasi Pemupukan Awal</h3>
                        
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-slate-700">Jenis pupuk awal</label>
                            <select name="pupuk_id" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition-all hover:border-[#047857] focus:border-[#047857] focus:outline-none focus:ring-2 focus:ring-[#047857]/20">
                                <option value="">Pilih pupuk</option>
                                @foreach($pupuk ?? [] as $item)
                                    <option value="{{ $item['id'] }}" @selected((string) old('pupuk_id', $editTanam['pemupukan_awal']['pupuk_id'] ?? '') === (string) $item['id'])>
                                        {{ $item['nama_pupuk'] }} - {{ $item['tipe_pupuk'] ?? $item['tipe'] ?? 'Umum' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-slate-700">Tanggal pemupukan awal</label>
                            <input type="date" name="tanggal_pemupukan" max="{{ date('Y-m-d') }}" required
                                   value="{{ old('tanggal_pemupukan', $editTanam['pemupukan_awal']['tanggal_pemupukan'] ?? date('Y-m-d')) }}"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition-all hover:border-[#047857] focus:border-[#047857] focus:outline-none focus:ring-2 focus:ring-[#047857]/20">
                            <p class="mt-1 text-[10px] text-slate-500">Tanggal tidak boleh lebih awal dari tanggal tanam.</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-slate-700">Takaran (kg)</label>
                            <input type="number" name="takaran" min="0.01" step="0.01" required placeholder="Cth: 20"
                                   value="{{ old('takaran', $editTanam['pemupukan_awal']['takaran'] ?? '') }}"
                                   class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition-all hover:border-[#047857] focus:border-[#047857] focus:outline-none focus:ring-2 focus:ring-[#047857]/20">
                            <p class="mt-1 text-[10px] text-slate-500">Jumlah pupuk untuk awal tanam</p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-4 border-t border-slate-100 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    @if($isEdit)
                        <a href="{{ route('lapor.tanam') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-center text-xs font-bold text-slate-600">Batal</a>
                    @endif
                    <button type="submit" class="rounded-lg bg-[#047857] px-6 py-2.5 text-xs font-bold text-white hover:bg-[#065f46] shadow-md transition-all hover:-translate-y-0.5">
                        {{ $isEdit ? 'Simpan Perubahan' : 'Mulai Proses Tanam & Pemupukan' }}
                    </button>
                </div>
            </form>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const lahanSelect = document.getElementById('lahan-select');
            const luasTanamInput = document.getElementById('luas-tanam-input');
            const luasTanamHint = document.getElementById('luas-tanam-hint');
            const bibitSelect = document.getElementById('bibit-select');
            const estimasiHariInput = document.getElementById('estimasi-hari');

            function syncLuasTanam() {
                if (!lahanSelect || !luasTanamInput) return;
                const selectedOption = lahanSelect.options[lahanSelect.selectedIndex];
                const luasLahan = selectedOption ? selectedOption.getAttribute('data-luas-lahan') : '';
                const luasTanam = selectedOption ? selectedOption.getAttribute('data-luas-tanam') : '';

                if (!luasTanamInput.value && luasTanam) {
                    luasTanamInput.value = luasTanam;
                }

                if (luasLahan) {
                    luasTanamInput.max = luasLahan;
                    luasTanamHint.textContent = `Maksimal ${Number(luasLahan).toLocaleString('id-ID')} ha sesuai luas lahan.`;
                } else {
                    luasTanamInput.removeAttribute('max');
                    luasTanamHint.textContent = 'Masukkan luas lahan yang benar-benar ditanami padi.';
                }
            }

            lahanSelect?.addEventListener('change', function() {
                if (luasTanamInput) luasTanamInput.value = '';
                syncLuasTanam();
            });
            syncLuasTanam();

            const tanggalTanamInput = document.getElementById('tanggal-tanam') || document.querySelector('input[name="tanggal_tanam"]');
            const tanggalPanenInput = document.getElementById('tanggal-panen-estimasi');
            
            function calculateEstimasiPanen() {
                let hintEl = document.getElementById('estimasi-panen-hint');
                if (!hintEl) {
                    hintEl = document.createElement('p');
                    hintEl.id = 'estimasi-panen-hint';
                    hintEl.className = 'mt-2 text-[11px] font-bold text-[#047857]';
                    estimasiHariInput.parentNode.appendChild(hintEl);
                }

                // 1. Calculate from user input date if provided
                if (tanggalPanenInput && tanggalPanenInput.value && tanggalTanamInput && tanggalTanamInput.value) {
                    const tglTanam = new Date(tanggalTanamInput.value);
                    const tglPanen = new Date(tanggalPanenInput.value);
                    if (!isNaN(tglTanam) && !isNaN(tglPanen)) {
                        const diffTime = tglPanen - tglTanam;
                        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                        if (diffDays > 0) {
                            estimasiHariInput.value = diffDays;
                            hintEl.textContent = `Dihitung berdasarkan selisih tanggal tanam dan panen yang dipilih (${diffDays} Hari).`;
                            return;
                        }
                    }
                }

                // 2. Fallback to bibit default
                if (!bibitSelect) return;
                const selectedOption = bibitSelect.options[bibitSelect.selectedIndex];
                const namaBibit = selectedOption ? selectedOption.textContent.toLowerCase() : '';
                const defaultHari = selectedOption ? selectedOption.getAttribute('data-hari') : '';
                
                if (defaultHari) {
                    estimasiHariInput.value = defaultHari;
                } else {
                    estimasiHariInput.value = '';
                }
                
                if (namaBibit.includes('inpara') || namaBibit.includes('inpari')) {
                    const tglTanam = new Date(tanggalTanamInput.value);
                    if (!isNaN(tglTanam)) {
                        const minHari = namaBibit.includes('inpara') ? 102 : (namaBibit.includes('inpari 32') ? 120 : parseInt(defaultHari) || 100);
                        const maxHari = namaBibit.includes('inpara') ? 131 : (namaBibit.includes('inpari 32') ? 120 : parseInt(defaultHari) || 120);
                        
                        const tglA = new Date(tglTanam);
                        tglA.setDate(tglA.getDate() + minHari);
                        const tglB = new Date(tglTanam);
                        tglB.setDate(tglB.getDate() + maxHari);
                        
                        const formatA = tglA.toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'});
                        const formatB = tglB.toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'});
                        
                        hintEl.textContent = minHari !== maxHari ? `Estimasi Masa Tanam: ${minHari} - ${maxHari} Hari. Perkiraan Panen: ${formatA} hingga ${formatB}` : `Estimasi Masa Tanam: ${minHari} Hari. Perkiraan Panen: ${formatA}`;
                    } else {
                        hintEl.textContent = '';
                    }
                } else {
                    hintEl.textContent = '';
                }
            }

            bibitSelect?.addEventListener('change', calculateEstimasiPanen);
            tanggalTanamInput?.addEventListener('change', calculateEstimasiPanen);
            tanggalPanenInput?.addEventListener('change', calculateEstimasiPanen);
        });
    </script>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden mt-6">
        <div class="border-b border-[#d1fae5] px-5 py-4">
            <h2 class="text-sm font-bold text-[#022c22]">Proses tanam berjalan</h2>
            <p class="mt-1 text-[11px] text-slate-500">Perkembangan dihitung dari tanggal tanam sampai estimasi panen.</p>
        </div>
        <div class="divide-y divide-[#d1fae5]">
            @forelse($aktif as $siklus)
                <article class="p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-sm font-bold text-[#022c22]">{{ $siklus['nama_lahan'] }}</h3>
                                <span class="rounded-full bg-[#ecfdf5] px-2 py-0.5 text-[10px] font-bold text-[#047857]">{{ $siklus['nama_bibit'] }}</span>
                            </div>
                            <p class="mt-1 text-[11px] text-slate-500">Tanam {{ \Carbon\Carbon::parse($siklus['tanggal_tanam'])->format('d M Y') }} · Estimasi {{ \Carbon\Carbon::parse($siklus['estimasi_tanggal_panen'])->format('d M Y') }}{{ !empty($siklus['estimasi_tanggal_panen_akhir']) ? ' - ' . \Carbon\Carbon::parse($siklus['estimasi_tanggal_panen_akhir'])->format('d M Y') : '' }}</p>
                            <p class="mt-1 text-[11px] text-slate-500">Luas tanam: {{ number_format((float) ($siklus['luas_tanam_hektar'] ?? 0), 2, ',', '.') }} ha</p>
                            @if(!empty($siklus['pemupukan_awal']))
                                <p class="mt-1 text-[11px] text-slate-500">
                                    Pemupukan awal: {{ $siklus['pemupukan_awal']['nama_pupuk'] ?? '-' }}
                                    · {{ number_format((float) ($siklus['pemupukan_awal']['takaran'] ?? 0), 2, ',', '.') }} kg
                                    · {{ \Carbon\Carbon::parse($siklus['pemupukan_awal']['tanggal_pemupukan'])->format('d M Y') }}
                                </p>
                            @endif
                        </div>
                        @if($siklus['can_edit'] || $siklus['can_delete'])
                            <div class="flex gap-2">
                                @if($siklus['can_edit'])
                                    <a href="{{ route('lapor.tanam.edit', $siklus['id']) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-[11px] font-bold text-slate-600">Edit</a>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-[#047857]" style="width: {{ $siklus['progress_persen'] }}%"></div>
                    </div>
                    <div class="mt-2 flex justify-between text-[10px] font-semibold text-slate-500">
                        <span>{{ $siklus['progress_persen'] }}% masa tanam</span>
                        <span>{{ $siklus['hari_tersisa'] }} hari menuju estimasi panen</span>
                    </div>
                </article>
            @empty
                <div class="px-5 py-10 text-center text-xs text-slate-500">Belum ada proses tanam aktif.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
