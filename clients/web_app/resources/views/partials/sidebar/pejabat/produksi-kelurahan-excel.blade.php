<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        .header {
            font-weight: bold;
            font-size: 14px;
            text-align: center;
        }
        .th-style {
            background-color: #3e7d00;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            border: 1px solid #000000;
        }
        .td-style {
            border: 1px solid #000000;
            text-align: left;
        }
        .td-number {
            border: 1px solid #000000;
            text-align: right;
        }
        .td-center {
            border: 1px solid #000000;
            text-align: center;
        }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="9" class="header"><b>LAPORAN REKAP HASIL PANEN DESA / KELURAHAN</b></td>
        </tr>
        @if($kecamatan)
            <tr>
                <td colspan="9" class="header"><b>KECAMATAN: {{ strtoupper($kecamatan) }}</b></td>
            </tr>
        @endif
        <tr>
            <td colspan="9" class="header"><b>KABUPATEN BARITO KUALA</b></td>
        </tr>
        <tr>
            <td colspan="9" style="text-align: center;">Tanggal Cetak: {{ now()->translatedFormat('d F Y H:i') }}</td>
        </tr>
        <tr>
            <td colspan="9"></td>
        </tr>
        <thead>
            <tr>
                <th class="th-style">No</th>
                <th class="th-style">Kecamatan</th>
                <th class="th-style">Kelurahan / Desa</th>
                <th class="th-style">Tahun LBS</th>
                <th class="th-style">Jumlah Lahan</th>
                <th class="th-style">Total Luas (Ha)</th>
                <th class="th-style">Rincian Per Tipe (Ha)</th>
                <th class="th-style">Hasil Panen (Ton)</th>
                <th class="th-style">Produktivitas (Ton/Ha)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $item)
                @php
                    $tipeRincian = is_array($item['rincian_tipe_lahan'] ?? null) ? $item['rincian_tipe_lahan'] : [];
                    $rincianStr = [];
                    foreach($tipeRincian as $tipe) {
                        $val = (float)($tipe['total_luas'] ?? 0);
                        if ($val > 0) {
                            $rincianStr[] = ($tipe['nama_tipe'] ?? 'Belum Ditentukan') . ': ' . number_format($val, 2) . ' Ha';
                        }
                    }
                    $totalLuas = (float)($item['total_luas'] ?? 0);
                    $totalPanen = (float)($item['total_panen'] ?? 0);
                    $prod = $totalLuas > 0 ? ($totalPanen / $totalLuas) : 0;
                @endphp
                <tr>
                    <td class="td-center">{{ $index + 1 }}</td>
                    <td class="td-style"><b>{{ $item['nama_kecamatan'] ?? '-' }}</b></td>
                    <td class="td-style">{{ $item['nama_kelurahan'] ?? '-' }}</td>
                    <td class="td-center">{{ $item['tahun_lbs'] ?? '-' }}</td>
                    <td class="td-center">{{ $item['jumlah_lahan'] ?? 0 }} Lahan</td>
                    <td class="td-number">{{ number_format($totalLuas, 2, '.', '') }}</td>
                    <td class="td-style">
                        {{ implode(', ', $rincianStr) ?: '-' }}
                    </td>
                    <td class="td-number"><b>{{ number_format($totalPanen, 2, '.', '') }}</b></td>
                    <td class="td-number" style="color: #1d4ed8;">{{ number_format($prod, 2, '.', '') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
