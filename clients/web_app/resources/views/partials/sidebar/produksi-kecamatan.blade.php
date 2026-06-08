@extends('layouts.app')

@section('title', 'Produksi per Kecamatan')

@section('content')

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-[#14280b]">
            Produksi Per Kecamatan
        </h1>
        <p class="text-sm text-slate-500 mt-1">
            Rekap total produksi padi berdasarkan kecamatan.
        </p>
    </div>

    <a href="{{ url('/dashboard-pejabat')  }}"
       class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl
              bg-gradient-to-r from-[#3E7D00] to-[#65bd00]
              text-white font-bold text-sm
              shadow-lg hover:scale-105 transition-all duration-300">
        ← Kembali
    </a>
</div>

<div class="glass-card rounded-[28px] p-6">

    <div class="overflow-x-auto">

        <table class="w-full">

            <thead>
                <tr class="border-b">
                    <th class="text-left py-3">No</th>
                    <th class="text-left py-3">Kecamatan</th>
                    <th class="text-right py-3">Total Produksi (Ton)</th>
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

                        <td class="py-3 text-right font-bold text-green-700">
                            {{ number_format($item['produksi_pejabat'], 2) }}
                        </td>

                    </tr>

                @empty

                    <tr>
                        <td colspan="3" class="text-center py-6 text-slate-500">
                            Belum ada data produksi.
                        </td>
                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection