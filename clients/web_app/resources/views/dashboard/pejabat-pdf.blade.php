<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Statistik Eksekutif SiTani</title>
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
        
        /* Summary Cards */
        .summary-card {
            background-color: #fcfdfe;
            border: 1px solid #e7efd8;
            border-radius: 12px;
            padding: 15px;
            vertical-align: top;
        }
        .card-label {
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
            color: #777777;
            letter-spacing: 0.5px;
        }
        .card-value {
            font-size: 24px;
            font-weight: bold;
            color: #14280b;
            margin-top: 10px;
        }
        .card-unit {
            font-size: 12px;
            color: #777777;
            font-weight: normal;
        }
        
        /* Table Layouts */
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #14280b;
            margin-top: 25px;
            margin-bottom: 10px;
            border-bottom: 1px solid #dfeccc;
            padding-bottom: 5px;
        }
        
        .grid-col {
            vertical-align: top;
        }
        
        .table-data {
            width: 100%;
            border-collapse: collapse;
        }
        .table-data th {
            background-color: #f4fbe9;
            color: #203c10;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #dfeccc;
            padding: 8px;
            text-align: left;
        }
        .table-data td {
            padding: 8px;
            border-bottom: 1px solid #e7efd8;
            font-size: 11px;
        }
        .table-data tr:nth-child(even) {
            background-color: #fbfdf9;
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
                    <div>Laporan Statistik Eksekutif (Dashboard Pejabat)</div>
                    <div style="margin-top: 3px; color: #555555; font-weight: 500;">
                        Periode: {{ now()->translatedFormat('F Y') }} | Cetak: {{ now()->translatedFormat('d F Y H:i') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Summary Metrics -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 25px;">
        <tr>
            <td class="summary-card" style="width: 48.5%;">
                <div class="card-label">TOTAL PRODUKSI</div>
                <div class="card-value">
                    {{ number_format($produksiPejabat ?? 0, 2) }}
                    <span class="card-unit">Ton</span>
                </div>
            </td>
            <td style="width: 3%;"></td>
            <td class="summary-card" style="width: 48.5%;">
                <div class="card-label">LAHAN AKTIF</div>
                <div class="card-value">
                    {{ number_format($totalLahan ?? 0, 2) }}
                    <span class="card-unit">Ha</span>
                </div>
            </td>
        </tr>
    </table>

    @php
    $bulanLabel = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    @endphp

    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <!-- Tren Produksi Bulanan -->
            <td class="grid-col" style="width: 48.5%;">
                <div class="section-title">Tren Produksi Bulanan</div>
                <table class="table-data">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th class="text-right">Produksi (Ton)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($produksiBulanan as $bulan => $total)
                            <tr>
                                <td>{{ $bulanLabel[$bulan] ?? '-' }}</td>
                                <td class="text-right font-bold text-green">
                                    {{ number_format($total, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
            
            <td style="width: 3%;"></td>
            
            <!-- Top Kecamatan -->
            <td class="grid-col" style="width: 48.5%;">
                <div class="section-title">Kecamatan dengan Produksi Tertinggi</div>
                <table class="table-data">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kecamatan</th>
                            <th class="text-right">Produksi (Ton)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topKecamatan as $index => $item)
                            <tr>
                                <td style="width: 15%;">{{ $index + 1 }}</td>
                                <td class="font-bold">{{ $item['nama_kecamatan'] }}</td>
                                <td class="text-right font-bold text-emerald">
                                    {{ number_format($item['produksi_pejabat'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center" style="padding: 20px; color: #777;">
                                    Belum ada data kecamatan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">
        Laporan Eksekutif ini dibuat otomatis oleh Sistem SiTani (SIG-PALA).
    </div>

</body>
</html>
