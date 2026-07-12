<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Luas Lahan Aktif Per Kecamatan</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
            font-size: 12px;
            line-height: 1.5;
        }
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #3E7D00;
            padding-bottom: 15px;
        }
        .header table {
            width: 100%;
        }
        .header .title {
            font-size: 24px;
            font-weight: bold;
            color: #14280b;
        }
        .header .subtitle {
            font-size: 12px;
            color: #555555;
            margin-top: 5px;
        }
        .header .date {
            text-align: right;
            font-size: 11px;
            color: #777777;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .table-data th {
            background-color: #f4fbe9;
            color: #203c10;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dfeccc;
            padding: 10px;
            text-align: left;
        }
        .table-data td {
            padding: 12px 10px;
            border-bottom: 1px solid #e7efd8;
        }
        .table-data tr:nth-child(even) {
            background-color: #fcfdfe;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-bold {
            font-weight: bold;
        }
        .text-emerald {
            color: #047857;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #999999;
            border-top: 1px solid #e7efd8;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <div class="header">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; vertical-align: top;">
                    <div class="title">SiTani</div>
                    <div class="subtitle">Rekap Luas Lahan Aktif Per Kecamatan</div>
                </td>
                <td class="date" style="border: none; vertical-align: top; text-align: right;">
                    Tanggal Cetak: {{ now()->translatedFormat('d F Y H:i') }}<br>
                    Sistem SIG-PALA
                </td>
            </tr>
        </table>
    </div>

    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 10%;">No</th>
                <th style="width: 60%;">Kecamatan</th>
                <th class="text-right" style="width: 30%;">Luas Lahan (Ha)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="font-bold">{{ $item['nama_kecamatan'] }}</td>
                    <td class="text-right font-bold text-emerald">
                        {{ number_format($item['total_lahan'], 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center" style="padding: 20px; color: #777;">
                        Belum ada data lahan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dokumen ini dibuat otomatis oleh Sistem SiTani - Laporan Eksekutif Pejabat Daerah.
    </div>

</body>
</html>
