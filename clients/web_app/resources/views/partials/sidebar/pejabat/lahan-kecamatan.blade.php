@extends('layouts.app')

@section('title', 'Lahan Aktif per Kecamatan')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-[#022c22]">
            Lahan Aktif Per Kecamatan
        </h1>
        <p class="text-sm text-slate-500 mt-1">
            Rekap luas lahan aktif berdasarkan kecamatan.
        </p>
    </div>

    <div class="flex items-center gap-3">
        <a href="{{ route('dashboard.pejabat') }}"
           class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl
                  bg-white border border-[#d1fae5] text-[#047857] font-bold text-sm
                  shadow-md hover:bg-[#ecfdf5] hover:scale-105 transition-all duration-300">
            ← Kembali
        </a>
    </div>
</div>

<div class="glass-card rounded-[28px] p-6">

    <table class="w-full">

        <thead>
            <tr class="border-b">
                <th class="text-left py-3">No</th>
                <th class="text-left py-3">Kecamatan</th>
                <th class="text-right py-3">Total Lahan (Ha)</th>
                <th class="text-right py-3">Total Luas Tanam (Ha)</th>
            </tr>
        </thead>

        <tbody>

            @forelse($data as $index => $item)

                <tr class="border-b hover:bg-slate-50 transition-colors">

                    <td class="py-3 text-slate-500">
                        {{ $index + 1 }}
                    </td>

                    <td class="py-3 font-semibold text-slate-700">
                        {{ $item['nama_kecamatan'] }}
                    </td>

                    <td class="py-3 text-right font-bold text-emerald-700">
                        {{ number_format($item['total_lahan'], 2) }}
                    </td>
                    
                    <td class="py-3 text-right font-bold text-amber-600">
                        {{ number_format($item['total_luas_tanam'] ?? 0, 2) }}
                    </td>

                </tr>

            @empty

                <tr class="border-b">
                    <td colspan="4" class="text-center py-6 text-slate-500">
                        Belum ada data lahan.
                    </td>
                </tr>

            @endforelse

        </tbody>

    </table>

    <div class="mt-6 flex justify-end">
        <a href="{{ route('lahan.kecamatan.pdf') }}" target="_blank"
           class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl
                  bg-gradient-to-r from-[#047857] to-[#065f46] text-white font-bold text-sm
                  shadow-md hover:scale-105 transition-all duration-300">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            Export PDF
        </a>
    </div>

</div>

@endsection
