<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Produksi Padi Per Kecamatan</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
            font-size: 12px;
            line-height: 1.5;
        }
        .header-banner {
            border-bottom: 2px solid #3e7d00;
            padding-bottom: 18px;
            margin-bottom: 30px;
            color: #1a1a1a;
        }
        .header-banner table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        .header-banner td {
            border: none;
            padding: 0;
        }
        .header-banner .logo-container {
            width: 55px;
            vertical-align: middle;
        }
        .header-banner .logo-container img {
            width: 48px;
            height: auto;
            display: block;
        }
        .header-banner .title-container {
            vertical-align: middle;
            padding-left: 12px;
        }
        .header-banner .system-subtitle {
            font-size: 11px;
            text-transform: uppercase;
            color: #555555;
            font-weight: bold;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }
        .header-banner .system-title {
            font-size: 20px;
            font-weight: bold;
            color: #14280b;
            line-height: 1.2;
            margin-top: 2px;
        }
        .header-banner .info-container {
            text-align: right;
            vertical-align: middle;
            font-size: 10px;
            color: #555555;
            line-height: 1.4;
        }
        .header-banner .app-name {
            font-weight: bold;
            color: #3e7d00;
            font-size: 18px;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
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
        .text-green {
            color: #3E7D00;
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

    <div class="header-banner">
        <table>
            <tr>
                <td class="logo-container">
                    <img src="{{ public_path('storage/logo.png') }}" alt="Logo">
                </td>
                <td class="title-container">
                    <div class="system-subtitle">Sistem Informasi</div>
                    <div class="system-title">Dinas Pertanian</div>
                </td>
                <td class="info-container">
                    <div>Rekap Total Produksi Padi Per Kecamatan</div>
                    <div style="margin-top: 3px; color: #555555; font-weight: 500;">
                        Tanggal Cetak: {{ now()->translatedFormat('d F Y H:i') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 10%;">No</th>
                <th style="width: 60%;">Kecamatan</th>
                <th class="text-right" style="width: 30%;">Total Produksi (Ton)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="font-bold">{{ $item['nama_kecamatan'] }}</td>
                    <td class="text-right font-bold text-green">
                        {{ number_format($item['produksi_pejabat'], 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center" style="padding: 20px; color: #777;">
                        Belum ada data produksi.
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
