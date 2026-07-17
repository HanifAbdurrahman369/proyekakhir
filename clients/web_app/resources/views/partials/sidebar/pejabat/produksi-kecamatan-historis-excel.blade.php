<table>
    <thead>
        <tr>
            <th colspan="8" style="font-weight: bold; text-align: center; font-size: 16px;">
                LAPORAN HISTORIS PRODUKSI KECAMATAN (2010-2025)
            </th>
        </tr>
        <tr>
            <th colspan="8" style="text-align: center;">
                Kecamatan: {{ $kecamatan['nama_kecamatan'] ?? '-' }} | Tahun: {{ $tahun ? $tahun : 'Semua (2010 - 2025)' }}
            </th>
        </tr>
        <tr>
            <th colspan="8"></th>
        </tr>
        <tr>
            <th style="font-weight: bold; background-color: #f5f5f5;">Total Luas Tanam (Ha)</th>
            <td>{{ number_format($summary['total_luas_tanam_ha'] ?? 0, 2, ',', '.') }}</td>
            <th style="font-weight: bold; background-color: #f5f5f5;">Total Produksi (Ton)</th>
            <td colspan="5">{{ number_format($summary['total_produksi_ton'] ?? 0, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <th style="font-weight: bold; background-color: #f5f5f5;">Rata-rata Produktivitas (Ton/Ha)</th>
            <td>{{ number_format($summary['rata_produktivitas_ton_ha'] ?? 0, 3, ',', '.') }}</td>
            <th style="font-weight: bold; background-color: #f5f5f5;">Jumlah Tahun</th>
            <td colspan="5">{{ number_format($summary['jumlah_tahun'] ?? 0, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th colspan="8"></th>
        </tr>
        <tr>
            <th style="font-weight: bold; background-color: #e2efd9; border: 1px solid #000; text-align: center;">No</th>
            <th style="font-weight: bold; background-color: #e2efd9; border: 1px solid #000; text-align: center;">Tahun</th>
            <th style="font-weight: bold; background-color: #e2efd9; border: 1px solid #000; text-align: center;">Luas Tanam (Ha)</th>
            <th style="font-weight: bold; background-color: #e2efd9; border: 1px solid #000; text-align: center;">Luas Panen (Ha)</th>
            <th style="font-weight: bold; background-color: #e2efd9; border: 1px solid #000; text-align: center;">Produktivitas (Ton/Ha)</th>
            <th style="font-weight: bold; background-color: #e2efd9; border: 1px solid #000; text-align: center;">Produksi (Ton)</th>
            <th style="font-weight: bold; background-color: #e2efd9; border: 1px solid #000; text-align: center;">Status</th>
            <th style="font-weight: bold; background-color: #e2efd9; border: 1px solid #000; text-align: center;">Sumber</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $index => $row)
            <tr>
                <td style="border: 1px solid #000; text-align: center;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $row['tahun'] ?? '-' }}</td>
                <td style="border: 1px solid #000; text-align: right;">{{ number_format($row['luas_tanam_ha'] ?? 0, 2, ',', '') }}</td>
                <td style="border: 1px solid #000; text-align: right;">{{ number_format($row['luas_panen_ha'] ?? 0, 2, ',', '') }}</td>
                <td style="border: 1px solid #000; text-align: right;">{{ number_format($row['produktivitas_ton_ha'] ?? 0, 3, ',', '') }}</td>
                <td style="border: 1px solid #000; text-align: right;">{{ number_format($row['produksi_ton'] ?? 0, 2, ',', '') }}</td>
                <td style="border: 1px solid #000;">{{ $row['status_data'] ?? '-' }}</td>
                <td style="border: 1px solid #000;">{{ $row['sumber_data'] ?? '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8" style="border: 1px solid #000; text-align: center;">Tidak ada data historis yang tersedia.</td>
            </tr>
        @endforelse
    </tbody>
</table>
