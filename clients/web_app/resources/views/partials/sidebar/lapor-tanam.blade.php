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
            <p class="text-[11px] font-bold uppercase text-[#3E7D00]">{{ $roleName }}</p>
            <h1 class="mt-1 text-xl font-bold text-[#14280b]">{{ $isEdit ? 'Perbarui laporan tanam' : 'Laporan tanam dan pemupukan' }}</h1>
            <p class="mt-1 text-xs text-slate-500">Estimasi panen dihitung otomatis berdasarkan masa varietas bibit.</p>
        </div>
        <span class="inline-flex w-fit rounded-full border border-[#dfeccc] bg-[#edf8dc] px-3 py-1 text-[11px] font-bold text-[#3E7D00]">
            {{ $roleId === 5 ? 'Bibit unggul: Oktober - Januari' : 'Bibit lokal: Januari - September' }}
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

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <div class="border border-[#e7efd8] bg-white lg:col-span-12">
            <div class="border-b border-[#e7efd8] px-5 py-4">
                <h2 class="text-sm font-bold text-[#14280b]">{{ $isEdit ? 'Informasi tanam yang diperbarui' : 'Informasi Tanam & Pemupukan Baru' }}</h2>
                <p class="mt-1 text-[11px] text-slate-500">Pilih lahan, bibit, estimasi panen, serta informasi awal pemupukan sebelum tanam dimulai.</p>
            </div>
            <form method="POST" action="{{ $isEdit ? route('lapor.tanam.update', $editTanam['id']) : route('lapor.tanam.store') }}" class="p-5">
                @csrf
                @if($isEdit) @method('PUT') @endif
                
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Bagian Informasi Tanam -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-bold text-[#3E7D00] border-b pb-2 mb-3">Informasi Tanam</h3>
                        
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-slate-700">Lahan sawah</label>
                            <select name="lahan_id" required class="w-full rounded-lg border-slate-300 text-sm focus:border-[#5EA500] focus:ring-[#5EA500]">
                                <option value="">Pilih lahan terverifikasi</option>
                                @foreach($lahan ?? [] as $item)
                                    <option value="{{ $item['id'] }}" @selected((string) old('lahan_id', $editTanam['lahan_id'] ?? '') === (string) $item['id'])>
                                        {{ $item['nama_lahan'] }} - {{ $item['pemilik_lahan'] ?? 'Pemilik belum dicatat' }}
                                    </option>
                                @endforeach
                            </select>
                            @if(empty($lahan))
                                <p class="mt-1.5 text-[11px] text-amber-700">Belum ada lahan terverifikasi yang ditugaskan untuk akun ini.</p>
                            @endif
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-slate-700">Jenis bibit</label>
                            <select name="bibit_id" required id="bibit-select" class="w-full rounded-lg border-slate-300 text-sm focus:border-[#5EA500] focus:ring-[#5EA500]">
                                <option value="">Pilih bibit</option>
                                @foreach($bibit ?? [] as $item)
                                    <option value="{{ $item['id'] }}" data-hari="{{ $item['masa_tanam_hari'] }}" @selected((string) old('bibit_id', $editTanam['bibit_id'] ?? '') === (string) $item['id'])>
                                        {{ $item['nama_bibit'] }} - {{ $item['masa_tanam_hari'] }} hari
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1.5 block text-xs font-bold text-slate-700">Tanggal tanam</label>
                                <input type="date" name="tanggal_tanam" max="{{ date('Y-m-d') }}" required
                                       value="{{ old('tanggal_tanam', $editTanam['tanggal_tanam'] ?? date('Y-m-d')) }}"
                                       class="w-full rounded-lg border-slate-300 text-sm focus:border-[#5EA500] focus:ring-[#5EA500]">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-bold text-slate-700">Estimasi Tanam (Hari)</label>
                                <input type="number" name="estimasi_hari_tanam" id="estimasi-hari" min="1" required
                                       value="{{ old('estimasi_hari_tanam', $editTanam['estimasi_panen'] ?? '') }}"
                                       placeholder="Cth: 120"
                                       class="w-full rounded-lg border-slate-300 text-sm focus:border-[#5EA500] focus:ring-[#5EA500]">
                                <p class="mt-1 text-[10px] text-slate-500">Estimasi masa panen (hari)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Bagian Informasi Pemupukan -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-bold text-[#3E7D00] border-b pb-2 mb-3">Informasi Pemupukan Awal</h3>
                        
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-slate-700">Jenis pupuk awal</label>
                            <select name="pupuk_id" required class="w-full rounded-lg border-slate-300 bg-white text-sm focus:border-[#5EA500] focus:ring-[#5EA500]">
                                <option value="">Pilih pupuk</option>
                                @foreach($pupuk ?? [] as $item)
                                    <option value="{{ $item['id'] }}">{{ $item['nama_pupuk'] }} - {{ $item['tipe'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-bold text-slate-700">Takaran (kg)</label>
                            <input type="number" name="takaran" min="0.01" step="0.01" required placeholder="Cth: 20" class="w-full rounded-lg border-slate-300 bg-white text-sm focus:border-[#5EA500] focus:ring-[#5EA500]">
                            <p class="mt-1 text-[10px] text-slate-500">Jumlah pupuk untuk awal tanam</p>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-4 border-t border-slate-100 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    @if($isEdit)
                        <a href="{{ route('lapor.tanam') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-center text-xs font-bold text-slate-600">Batal</a>
                    @endif
                    <button type="submit" class="rounded-lg bg-[#3E7D00] px-6 py-2.5 text-xs font-bold text-white hover:bg-[#2f5c12] shadow-md transition-all hover:-translate-y-0.5">
                        {{ $isEdit ? 'Simpan Perubahan' : 'Mulai Proses Tanam & Pemupukan' }}
                    </button>
                </div>
            </form>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bibitSelect = document.getElementById('bibit-select');
            const estimasiHariInput = document.getElementById('estimasi-hari');

            bibitSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const defaultHari = selectedOption.getAttribute('data-hari');
                if (defaultHari) {
                    estimasiHariInput.value = defaultHari;
                } else {
                    estimasiHariInput.value = '';
                }
            });
        });
    </script>

    <section class="border border-[#e7efd8] bg-white">
        <div class="border-b border-[#e7efd8] px-5 py-4">
            <h2 class="text-sm font-bold text-[#14280b]">Proses tanam berjalan</h2>
            <p class="mt-1 text-[11px] text-slate-500">Perkembangan dihitung dari tanggal tanam sampai estimasi panen.</p>
        </div>
        <div class="divide-y divide-[#edf4df]">
            @forelse($aktif as $siklus)
                <article class="p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-sm font-bold text-[#14280b]">{{ $siklus['nama_lahan'] }}</h3>
                                <span class="rounded-full bg-[#edf8dc] px-2 py-0.5 text-[10px] font-bold text-[#3E7D00]">{{ $siklus['nama_bibit'] }}</span>
                            </div>
                            <p class="mt-1 text-[11px] text-slate-500">Tanam {{ \Carbon\Carbon::parse($siklus['tanggal_tanam'])->format('d M Y') }} · Estimasi {{ \Carbon\Carbon::parse($siklus['estimasi_tanggal_panen'])->format('d M Y') }}</p>
                        </div>
                        @if($siklus['can_edit'] || $siklus['can_delete'])
                            <div class="flex gap-2">
                                @if($siklus['can_edit'])
                                    <a href="{{ route('lapor.tanam.edit', $siklus['id']) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-[11px] font-bold text-slate-600">Edit</a>
                                @endif
                                @if($siklus['can_delete'])
                                    <form action="{{ route('lapor.tanam.destroy', $siklus['id']) }}" method="POST" onsubmit="return confirm('Hapus laporan tanam ini?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-lg border border-red-200 px-3 py-1.5 text-[11px] font-bold text-red-600">Hapus</button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-[#5EA500]" style="width: {{ $siklus['progress_persen'] }}%"></div>
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
