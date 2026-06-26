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
            <td colspan="{{ $kecamatan ? 5 : 6 }}" class="header"><b>LAPORAN REKAP HASIL PANEN DESA / KELURAHAN</b></td>
        </tr>
        @if($kecamatan)
            <tr>
                <td colspan="5" class="header"><b>KECAMATAN: {{ strtoupper($kecamatan) }}</b></td>
            </tr>
        @endif
        <tr>
            <td colspan="{{ $kecamatan ? 5 : 6 }}" class="header"><b>KABUPATEN BARITO KUALA</b></td>
        </tr>
        <tr>
            <td colspan="{{ $kecamatan ? 5 : 6 }}" style="text-align: center;">Tanggal Cetak: {{ now()->translatedFormat('d F Y H:i') }}</td>
        </tr>
        <tr>
            <td colspan="{{ $kecamatan ? 5 : 6 }}"></td>
        </tr>
        <thead>
            <tr>
                <th class="th-style">No</th>
                <th class="th-style">Desa / Kelurahan</th>
                @if(!$kecamatan)
                    <th class="th-style">Kecamatan</th>
                @endif
                <th class="th-style">Jumlah Lahan</th>
                <th class="th-style">Total Luas (Ha)</th>
                <th class="th-style">Hasil Panen (Ton)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $item)
                <tr>
                    <td class="td-center">{{ $index + 1 }}</td>
                    <td class="td-style"><b>{{ $item['nama_kelurahan'] ?? '-' }}</b></td>
                    @if(!$kecamatan)
                        <td class="td-style">{{ $item['nama_kecamatan'] ?? '-' }}</td>
                    @endif
                    <td class="td-center">{{ $item['jumlah_lahan'] ?? 0 }}</td>
                    <td class="td-number">{{ number_format($item['total_luas'] ?? 0.0, 2, '.', '') }}</td>
                    <td class="td-number"><b>{{ number_format($item['total_panen'] ?? 0.0, 2, '.', '') }}</b></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
