<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class KomunitasExport implements FromCollection, WithHeadings
{
    protected $komunitas;

    public function __construct(array $komunitas)
    {
        $this->komunitas = collect($komunitas)->map(function ($item) {
            return [
                'ID' => $item['id'] ?? '',
                'NIK' => $item['nik'] ?? '',
                'Jenis Komunitas' => $item['jenis_komunitas'] ?? '',
                'Nama' => $item['nama'] ?? '',
                'Nama Komunitas' => $item['nama_komunitas'] ?? '',
                'Nomor HP' => $item['nomor_hp'] ?? '',
                'Alamat' => $item['alamat'] ?? '',
                'Status Keanggotaan' => $item['status_keanggotaan'] ?? '',
                'Komunitas Induk ID' => $item['komunitas_induk_id'] ?? '',
                'Kecamatan ID' => $item['wilayah_kecamatan_id'] ?? '',
                'Kelurahan IDs' => is_array($item['wilayah_kelurahan_ids'] ?? null) ? json_encode($item['wilayah_kelurahan_ids']) : ($item['wilayah_kelurahan_ids'] ?? ''),
                'Instansi Asal' => $item['instansi_asal'] ?? '',
                'Nama BPP' => $item['nama_bpp'] ?? '',
            ];
        });
    }

    public function collection()
    {
        return collect($this->komunitas);
    }

    public function headings(): array
    {
        return [
            'ID',
            'NIK',
            'Jenis Komunitas',
            'Nama',
            'Nama Komunitas',
            'Nomor HP',
            'Alamat',
            'Status Keanggotaan',
            'Komunitas Induk ID',
            'Kecamatan ID',
            'Kelurahan IDs',
            'Instansi Asal',
            'Nama BPP'
        ];
    }
}
