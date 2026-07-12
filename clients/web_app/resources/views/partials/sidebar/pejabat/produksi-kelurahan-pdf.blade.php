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
                    <div style="font-weight: bold; font-size: 11px; color: #022c22; margin-bottom: 2px;">Rekap Produksi Desa/Kelurahan</div>
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
                <th style="width: 4%;" class="text-center">No</th>
                <th style="width: 12%;">Kecamatan</th>
                <th style="width: 14%;">Kelurahan / Desa</th>
                <th style="width: 8%;" class="text-center">Tahun LBS</th>
                <th style="width: 10%;" class="text-center">Jumlah Lahan</th>
                <th style="width: 10%;" class="text-right">Total Luas (Ha)</th>
                <th style="width: 22%;">Rincian Per Tipe (Ha)</th>
                <th style="width: 12%;" class="text-right">Hasil Panen (Ton)</th>
                <th style="width: 8%;" class="text-right">Produktivitas</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $index => $item)
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
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-bold">{{ $item['nama_kecamatan'] ?? '-' }}</td>
                    <td>{{ $item['nama_kelurahan'] ?? '-' }}</td>
                    <td class="text-center">{{ $item['tahun_lbs'] ?? '-' }}</td>
                    <td class="text-center">{{ $item['jumlah_lahan'] ?? 0 }} Lahan</td>
                    <td class="text-right">{{ number_format($totalLuas, 2) }} Ha</td>
                    <td>
                        @if(empty($rincianStr))
                            <span style="color: #bbb; font-style: italic;">-</span>
                        @else
                            {!! implode('<br>', $rincianStr) !!}
                        @endif
                    </td>
                    <td class="text-right font-bold text-emerald">
                        {{ number_format($totalPanen, 2) }} Ton
                    </td>
                    <td class="text-right font-bold" style="color: #1d4ed8;">
                        {{ number_format($prod, 2) }} Ton/Ha
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
        Dokumen ini dibuat otomatis oleh Sistem SiPetani - Laporan Produksi Wilayah Administratif.
    </div>

</body>
</html>
