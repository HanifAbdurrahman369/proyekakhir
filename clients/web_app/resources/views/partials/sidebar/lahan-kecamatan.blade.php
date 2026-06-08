@extends('layouts.app')

@section('title', 'Lahan Aktif per Kecamatan')

@section('content')

<div class="flex items-center justify-between mb-6">

    <div>
        <h1 class="text-2xl font-extrabold text-[#14280b]">
            Lahan Aktif Per Kecamatan
        </h1>

        <p class="text-sm text-slate-500 mt-1">
            Rekap luas lahan aktif berdasarkan kecamatan.
        </p>
    </div>

    <a href="{{ url('/dashboard-pejabat') }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-[#3E7D00] to-[#65bd00] text-white font-semibold shadow-md hover:shadow-lg hover:scale-[1.02] transition">

        ← Kembali
    </a>

</div>

<div class="glass-card rounded-[28px] p-6">

    <table class="w-full">

        <thead>
            <tr class="border-b">
                <th class="text-left py-3">No</th>
                <th class="text-left py-3">Kecamatan</th>
                <th class="text-right py-3">Luas Lahan (Ha)</th>
            </tr>
        </thead>

        <tbody>

            @forelse($data as $index => $item)

                <tr class="border-b hover:bg-slate-50">

                    <td class="py-3">
                        {{ $index + 1 }}
                    </td>

                    <td class="py-3 font-semibold">
                        {{ $item['nama_kecamatan'] }}
                    </td>

                    <td class="py-3 text-right font-bold text-emerald-700">
                        {{ number_format($item['total_lahan'], 2) }}
                    </td>

                </tr>

            @empty

                <tr>
                    <td colspan="3" class="text-center py-6 text-slate-500">
                        Belum ada data lahan.
                    </td>
                </tr>

            @endforelse

        </tbody>

    </table>

</div>

@endsection