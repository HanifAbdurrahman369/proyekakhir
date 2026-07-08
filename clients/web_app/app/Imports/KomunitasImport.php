<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class KomunitasImport implements ToCollection, WithHeadingRow
{
    protected $gatewayUrl;
    protected $token;
    protected $successCount = 0;
    protected $failureCount = 0;

    public function __construct(string $gatewayUrl, string $token)
    {
        $this->gatewayUrl = $gatewayUrl;
        $this->token = $token;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $data = [
                'nik' => $row['nik'] ?? null,
                'jenis_komunitas' => $row['jenis_komunitas'] ?? null,
                'nama' => $row['nama'] ?? null,
                'nama_komunitas' => $row['nama_komunitas'] ?? null,
                'nomor_hp' => $row['nomor_hp'] ?? null,
                'alamat' => $row['alamat'] ?? null,
                'status_keanggotaan' => $row['status_keanggotaan'] ?? 'AKTIF',
                'komunitas_induk_id' => $row['komunitas_induk_id'] ?? null,
                'wilayah_kecamatan_id' => $row['kecamatan_id'] ?? null,
                'instansi_asal' => $row['instansi_asal'] ?? null,
                'nama_bpp' => $row['nama_bpp'] ?? null,
            ];

            // parsing JSON for kelurahan ids
            if (isset($row['kelurahan_ids'])) {
                $decoded = json_decode($row['kelurahan_ids'], true);
                $data['wilayah_kelurahan_ids'] = is_array($decoded) ? $decoded : [];
            }

            if (empty($data['jenis_komunitas']) || empty($data['nama'])) {
                $this->failureCount++;
                continue;
            }

            $response = Http::withToken($this->token)->acceptJson()
                            ->post($this->gatewayUrl . '/api/komunitas', $data);

            if ($response->successful()) {
                $this->successCount++;
            } else {
                $this->failureCount++;
            }
        }
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }

    public function getFailureCount()
    {
        return $this->failureCount;
    }
}
