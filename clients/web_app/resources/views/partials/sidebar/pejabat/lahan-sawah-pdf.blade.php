<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daftar Lahan Sawah Terverifikasi</title>
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
            border-bottom: 2px solid #047857;
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
            color: #022c22;
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
            background-color: #ecfdf5;
            color: #065f46;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #d1fae5;
            padding: 10px 8px;
            text-align: left;
        }
        .table-data td {
            padding: 10px 8px;
            border-bottom: 1px solid #d1fae5;
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
            font-size: 9px;
            color: #999999;
            border-top: 1px solid #d1fae5;
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
                        <div style="font-weight: bold; color: #047857; font-size: 24px;">ST</div>
                    @endif
                </td>
                <td class="title-container">
                    <div class="system-subtitle">Sistem Informasi</div>
                    <div class="system-title">Pemetaan Tanaman Padi</div>
                </td>
                <td class="info-container">
                    <div style="font-weight: bold; font-size: 12px; color: #022c22; margin-bottom: 2px;">Daftar Lahan Sawah Terverifikasi</div>
                    <div>Kabupaten Barito Kuala</div>
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
                <th style="width: 22%;">Nama Lahan Sawah</th>
                <th style="width: 18%;">Kecamatan</th>
                <th style="width: 18%;">Kelurahan/Desa</th>
                <th style="width: 15%;">Pemilik</th>
                <th style="width: 12%;">Tipe Lahan</th>
                <th style="width: 10%;" class="text-right">Luas (Ha)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-bold">{{ $item['nama_lahan'] ?? '-' }}</td>
                    <td>{{ $item['nama_kecamatan'] ?? '-' }}</td>
                    <td>{{ $item['nama_kelurahan'] ?? '-' }}</td>
                    <td>{{ $item['pemilik_nama'] ?? '-' }}</td>
                    <td>{{ $item['tipe_lahan'] ?? '-' }}</td>
                    <td class="text-right font-bold text-emerald">
                        {{ number_format($item['luas'] ?? 0.0, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 20px; color: #777;">
                        Belum ada data lahan sawah.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dokumen ini dibuat otomatis oleh Sistem SiPetani - Laporan Eksekutif Lahan Sawah Daerah.
    </div>

</body>
</html>
