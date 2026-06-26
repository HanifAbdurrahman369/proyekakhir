<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Hasil Panen Desa/Kelurahan</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
            font-size: 11px;
            line-height: 1.5;
        }
        .header-banner {
            border-bottom: 2px solid #3e7d00;
            padding-bottom: 18px;
            margin-bottom: 24px;
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
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .table-data th {
            background-color: #f4fbe9;
            color: #203c10;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dfeccc;
            padding: 10px 8px;
            text-align: left;
        }
        .table-data td {
            padding: 10px 8px;
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
            color: #3e7d00;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 9px;
            color: #999999;
            border-top: 1px solid #e7efd8;
            padding-top: 8px;
        }
    </style>
</head>
<body>

    <div class="header-banner">
        <table>
            <tr>
                <td class="logo-container">
                    @if(file_exists(public_path('storage/logo.png')))
                        <img src="{{ public_path('storage/logo.png') }}" alt="Logo">
                    @else
                        <div style="font-weight: bold; color: #3e7d00; font-size: 24px;">ST</div>
                    @endif
                </td>
                <td class="title-container">
                    <div class="system-subtitle">Sistem Informasi</div>
                    <div class="system-title">Dinas Pertanian</div>
                </td>
                <td class="info-container">
                    <div style="font-weight: bold; font-size: 11px; color: #14280b; margin-bottom: 2px;">Rekap Produksi Desa/Kelurahan</div>
                    @if($kecamatan)
                        <div style="font-weight: bold;">Kecamatan: {{ $kecamatan }}</div>
                    @else
                        <div>Kabupaten Barito Kuala</div>
                    @endif
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
                <th style="width: 5%;" class="text-center">No</th>
                <th style="width: 30%;">Desa / Kelurahan</th>
                @if(!$kecamatan)
                    <th style="width: 25%;">Kecamatan</th>
                @endif
                <th style="width: 15%;" class="text-center">Jumlah Lahan</th>
                <th style="width: 15%;" class="text-right">Total Luas (Ha)</th>
                <th style="width: 15%;" class="text-right">Hasil Panen (Ton)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-bold">{{ $item['nama_kelurahan'] ?? '-' }}</td>
                    @if(!$kecamatan)
                        <td>{{ $item['nama_kecamatan'] ?? '-' }}</td>
                    @endif
                    <td class="text-center">{{ $item['jumlah_lahan'] ?? 0 }}</td>
                    <td class="text-right">{{ number_format($item['total_luas'] ?? 0.0, 2) }}</td>
                    <td class="text-right font-bold text-emerald">
                        {{ number_format($item['total_panen'] ?? 0.0, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $kecamatan ? 5 : 6 }}" class="text-center" style="padding: 20px; color: #777;">
                        Belum ada data produksi desa/kelurahan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dokumen ini dibuat otomatis oleh Sistem SiTani - Laporan Produksi Wilayah Administratif.
    </div>

</body>
</html>
