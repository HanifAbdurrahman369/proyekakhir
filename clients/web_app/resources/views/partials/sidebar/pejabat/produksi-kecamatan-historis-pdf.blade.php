<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Historis Produksi Kecamatan - {{ $kecamatan['nama_kecamatan'] ?? '' }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
        .subtitle { font-size: 14px; color: #555; }
        .summary-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .summary-table th, .summary-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .summary-table th { background-color: #f5f5f5; width: 25%; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #000; padding: 6px; }
        table.data-table th { background-color: #e2efd9; font-weight: bold; text-align: center; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .footer { margin-top: 30px; font-size: 10px; color: #777; text-align: center; }
    </style>
</head>
<body>

    <div class="header">
        <div class="title">LAPORAN HISTORIS PRODUKSI KECAMATAN (2010-2025)</div>
        <div class="subtitle">Kecamatan: {{ $kecamatan['nama_kecamatan'] ?? '-' }}</div>
        @if($tahun)
            <div class="subtitle">Tahun: {{ $tahun }}</div>
        @else
            <div class="subtitle">Tahun: Semua (2010 - 2025)</div>
        @endif
    </div>

    <table class="summary-table">
        <tr>
            <th>Total Luas Tanam (Ha)</th>
            <td>{{ number_format($summary['total_luas_tanam_ha'] ?? 0, 2, ',', '.') }}</td>
            <th>Total Produksi (Ton)</th>
            <td>{{ number_format($summary['total_produksi_ton'] ?? 0, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <th>Rata-rata Produktivitas (Ton/Ha)</th>
            <td>{{ number_format($summary['rata_produktivitas_ton_ha'] ?? 0, 3, ',', '.') }}</td>
            <th>Jumlah Tahun</th>
            <td>{{ number_format($summary['jumlah_tahun'] ?? 0, 0, ',', '.') }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="10%">Tahun</th>
                <th width="14%">Luas Tanam (Ha)</th>
                <th width="14%">Luas Panen (Ha)</th>
                <th width="14%">Produktivitas (Ton/Ha)</th>
                <th width="14%">Produksi (Ton)</th>
                <th width="13%">Status</th>
                <th width="20%">Sumber</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $row['tahun'] ?? '-' }}</td>
                    <td class="text-right">{{ number_format($row['luas_tanam_ha'] ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row['luas_panen_ha'] ?? 0, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row['produktivitas_ton_ha'] ?? 0, 3, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($row['produksi_ton'] ?? 0, 2, ',', '.') }}</td>
                    <td>{{ $row['status_data'] ?? '-' }}</td>
                    <td>{{ $row['sumber_data'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Tidak ada data historis yang tersedia.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->translatedFormat('d F Y H:i:s') }} - Sistem Informasi Pertanian
    </div>

</body>
</html>
