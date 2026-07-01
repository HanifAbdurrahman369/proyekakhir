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
            <td colspan="7" class="header"><b>LAPORAN DAFTAR LAHAN SAWAH TERVERIFIKASI</b></td>
        </tr>
        <tr>
            <td colspan="7" class="header"><b>KABUPATEN BARITO KUALA</b></td>
        </tr>
        <tr>
            <td colspan="7" style="text-align: center;">Tanggal Cetak: {{ now()->translatedFormat('d F Y H:i') }}</td>
        </tr>
        <tr>
            <td colspan="7"></td>
        </tr>
        <thead>
            <tr>
                <th class="th-style">No</th>
                <th class="th-style">Nama Lahan Sawah</th>
                <th class="th-style">Kecamatan</th>
                <th class="th-style">Kelurahan/Desa</th>
                <th class="th-style">Pemilik</th>
                <th class="th-style">Tipe Lahan</th>
                <th class="th-style">Luas Lahan (Ha)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $item)
                <tr>
                    <td class="td-center">{{ $index + 1 }}</td>
                    <td class="td-style"><b>{{ $item['nama_lahan'] ?? '-' }}</b></td>
                    <td class="td-style">{{ $item['nama_kecamatan'] ?? '-' }}</td>
                    <td class="td-style">{{ $item['nama_kelurahan'] ?? '-' }}</td>
                    <td class="td-style">{{ $item['pemilik_nama'] ?? '-' }}</td>
                    <td class="td-style">{{ $item['tipe_lahan'] ?? '-' }}</td>
                    <td class="td-number">{{ number_format($item['luas'] ?? 0.0, 2, '.', '') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
