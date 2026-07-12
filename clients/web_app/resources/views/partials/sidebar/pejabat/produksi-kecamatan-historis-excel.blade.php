<table>
    <thead>
        <tr>
            <th colspan="7" style="font-weight: bold; text-align: center; font-size: 16px;">
                LAPORAN HISTORIS PRODUKSI KECAMATAN (2010-2025)
            </th>
        </tr>
        <tr>
            <th colspan="7" style="text-align: center;">
                Kecamatan: {{ $kecamatan['nama_kecamatan'] ?? '-' }} | Tahun: {{ $tahun ? $tahun : 'Semua (2010 - 2025)' }}
            </th>
        </tr>
        <tr>
            <th colspan="7"></th>
        </tr>
        <tr>
            <th style="font-weight: bold; background-color: #f5f5f5;">Total Luas Tanam (Ha)</th>
            <td>{{ number_format($summary['total_luas_tanam_ha'] ?? 0, 2, ',', '.') }}</td>
            <th style="font-weight: bold; background-color: #f5f5f5;">Total Hasil Panen (Ton)</th>
            <td colspan="4">{{ number_format($summary['total_hasil_panen_ton'] ?? 0, 2, ',', '.') }}</td>
        </tr>
        <tr>
            <th style="font-weight: bold; background-color: #f5f5f5;">Rata-rata Produksi (Ton/Ha)</th>
            <td>{{ number_format($summary['avg_produktivitas'] ?? 0, 2, ',', '.') }}</td>
            <th style="font-weight: bold; background-color: #f5f5f5;">Total Record Data</th>
            <td colspan="4">{{ number_format($summary['total_record'] ?? 0, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <th colspan="7"></th>
        </tr>
        <tr>
            <th style="font-weight: bold; background-color: #e2efd9; border: 1px solid #000; text-align: center;">No</th>
            <th style="font-weight: bold; background-color: #e2efd9; border: 1px solid #000; text-align: center;">Tahun</th>
            <th style="font-weight: bold; background-color: #e2efd9; border: 1px solid #000; text-align: center;">Nama Petani</th>
            <th style="font-weight: bold; background-color: #e2efd9; border: 1px solid #000; text-align: center;">Kelurahan</th>
            <th style="font-weight: bold; background-color: #e2efd9; border: 1px solid #000; text-align: center;">Luas Tanam (Ha)</th>
            <th style="font-weight: bold; background-color: #e2efd9; border: 1px solid #000; text-align: center;">Hasil Panen (Ton)</th>
            <th style="font-weight: bold; background-color: #e2efd9; border: 1px solid #000; text-align: center;">Produktivitas (Ton/Ha)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $index => $row)
            <tr>
                <td style="border: 1px solid #000; text-align: center;">{{ $index + 1 }}</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $row['tahun_lbs'] ?? '-' }}</td>
                <td style="border: 1px solid #000;">{{ $row['pemilik_lahan'] ?? '-' }}</td>
                <td style="border: 1px solid #000;">{{ $row['nama_kelurahan'] ?? '-' }}</td>
                <td style="border: 1px solid #000; text-align: right;">{{ number_format($row['luas_tanam_hektar'] ?? 0, 2, ',', '') }}</td>
                <td style="border: 1px solid #000; text-align: right;">{{ number_format($row['hasil_panen_ton'] ?? 0, 2, ',', '') }}</td>
                <td style="border: 1px solid #000; text-align: right;">{{ number_format($row['produktivitas_ton_ha'] ?? 0, 2, ',', '') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="border: 1px solid #000; text-align: center;">Tidak ada data historis yang tersedia.</td>
            </tr>
        @endforelse
    </tbody>
</table>
