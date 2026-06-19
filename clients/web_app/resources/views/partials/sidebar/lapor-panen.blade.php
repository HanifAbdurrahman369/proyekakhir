@extends('layouts.app')

@php
    $isEdit = !empty($editPanen);
    $siapPanen = collect($siklusTanam ?? [])->filter(fn ($item) => !empty($item['can_report_harvest']));
@endphp

@section('title', $isEdit ? 'Perbaiki Laporan Panen' : 'Lapor Hasil Panen')

@section('content')
<div class="mx-auto max-w-3xl space-y-5 px-4 py-6 sm:px-6">
    <header>
        <p class="text-[11px] font-bold uppercase text-[#3E7D00]">Kelompok Tani</p>
        <h1 class="mt-1 text-xl font-bold text-[#14280b]">{{ $isEdit ? 'Perbaiki laporan hasil panen' : 'Laporan hasil panen' }}</h1>
        <p class="mt-1 text-xs text-slate-500">Hasil panen akan masuk ke riwayat dan statistik setelah disetujui petugas.</p>
    </header>

    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-xs font-semibold text-red-700">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    @if($isEdit && !empty($editPanen['catatan_verifikasi']))
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
            <p class="font-bold">Catatan petugas</p>
            <p class="mt-1">{{ $editPanen['catatan_verifikasi'] }}</p>
        </div>
    @endif

    <section class="border border-[#e7efd8] bg-white">
        <div class="border-b border-[#e7efd8] px-5 py-4">
            <h2 class="text-sm font-bold text-[#14280b]">Data panen aktual</h2>
            <p class="mt-1 text-[11px] text-slate-500">Pastikan hasil ditulis dalam satuan ton.</p>
        </div>
        <form action="{{ $isEdit ? route('panen.update', $editPanen['id']) : route('lapor.panen.store') }}" method="POST" class="space-y-4 p-5">
            @csrf
            @if($isEdit) @method('PUT') @endif

            @if(!$isEdit)
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Proses tanam siap panen</label>
                    <select name="siklus_tanam_id" required class="w-full rounded-lg border-slate-300 text-sm focus:border-[#5EA500] focus:ring-[#5EA500]">
                        <option value="">Pilih proses tanam</option>
                        @foreach($siapPanen as $item)
                            <option value="{{ $item['id'] }}" @selected((string) old('siklus_tanam_id') === (string) $item['id'])>
                                {{ $item['nama_lahan'] }} - {{ $item['nama_bibit'] }} - estimasi {{ \Carbon\Carbon::parse($item['estimasi_tanggal_panen'])->format('d M Y') }}
                            </option>
                        @endforeach
                    </select>
                    @if($siapPanen->isEmpty())
                        <p class="mt-2 text-[11px] text-amber-700">Belum ada proses tanam yang mencapai estimasi panen.</p>
                    @endif
                </div>
            @else
                <div class="rounded-lg border border-[#e7efd8] bg-[#f7fced] p-3 text-xs text-slate-700">
                    <p class="font-bold">{{ $editPanen['nama_lahan'] ?? '-' }}</p>
                    <p class="mt-1">{{ $editPanen['nama_bibit'] ?? '-' }} · tanggal tanam {{ !empty($editPanen['tanggal_tanam']) ? \Carbon\Carbon::parse($editPanen['tanggal_tanam'])->format('d M Y') : '-' }}</p>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Tanggal panen</label>
                    <input type="date" name="tanggal_panen" max="{{ date('Y-m-d') }}" required
                           value="{{ old('tanggal_panen', $editPanen['tanggal_panen'] ?? date('Y-m-d')) }}"
                           class="w-full rounded-lg border-slate-300 text-sm focus:border-[#5EA500] focus:ring-[#5EA500]">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Hasil panen (ton)</label>
                    <input type="number" name="hasil_panen" min="0.01" step="0.01" required
                           value="{{ old('hasil_panen', $editPanen['hasil_panen'] ?? '') }}" placeholder="Contoh: 4.50"
                           class="w-full rounded-lg border-slate-300 text-sm focus:border-[#5EA500] focus:ring-[#5EA500]">
                </div>
            </div>

            <div class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-[11px] text-blue-700">
                Petugas akan memeriksa laporan. Hanya laporan berstatus DITERIMA yang memperbarui hasil lahan, riwayat panen, peta publik, dan statistik.
            </div>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <a href="{{ url('/dashboard-petani') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-center text-xs font-bold text-slate-600">Batal</a>
                <button type="submit" @disabled(!$isEdit && $siapPanen->isEmpty()) class="rounded-lg bg-[#3E7D00] px-5 py-2 text-xs font-bold text-white disabled:cursor-not-allowed disabled:bg-slate-300">
                    {{ $isEdit ? 'Ajukan Ulang' : 'Kirim untuk Verifikasi' }}
                </button>
            </div>
        </form>
    </section>
</div>
@endsection
