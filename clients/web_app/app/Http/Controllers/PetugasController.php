<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PetugasController extends Controller
{
    private function gatewayUrl(): string
    {
        return rtrim(env('GATEWAY_URL', 'http://127.0.0.1:8003'), '/') . '/api';
    }

    private function getBearerToken(): string
    {
        return session('token') ?? session('jwt_token') ?? '';
    }

    private function getData(string $endpoint, mixed $default = [])
    {
        $token = $this->getBearerToken();

        if (!$token) {
            return $default;
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(8)
                ->get($this->gatewayUrl() . $endpoint);

            if (!$response->successful()) {
                return $default;
            }

            $json = $response->json();

            return $json['data'] ?? $json ?? $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private function sendPost(string $endpoint, array $payload = [])
    {
        $token = $this->getBearerToken();

        return Http::withToken($token)
            ->acceptJson()
            ->timeout(10)
            ->post($this->gatewayUrl() . $endpoint, $payload);
    }

    public function index()
    {
        $pendingLahan = $this->getData('/lahan/pending', []);
        $pendingPanen = $this->getData('/activities/pending', []);
        $notifikasi = $this->getData('/notifikasi/petugas', []);

        $stats = [
            'pending_lahan' => is_countable($pendingLahan) ? count($pendingLahan) : 0,
            'pending_panen' => is_countable($pendingPanen) ? count($pendingPanen) : 0,
            'total_pending' => (is_countable($pendingLahan) ? count($pendingLahan) : 0)
                + (is_countable($pendingPanen) ? count($pendingPanen) : 0),
            'notifikasi' => is_countable($notifikasi) ? count($notifikasi) : 0,
        ];

        return view('dashboard.petugas', [
            'page' => 'dashboard',
            'stats' => $stats,
            'pendingLahan' => $pendingLahan,
            'pendingPanen' => $pendingPanen,
            'notifikasi' => $notifikasi,
        ]);
    }

    public function manajemenDataSpasial()
    {
        $referensi = $this->getData('/spasial-lahan/referensi', [
            'petani' => [],
            'kecamatan' => [],
            'kelurahan' => [],
            'tipe_lahan' => [],
        ]);

        $koleksiLahan = $this->getData('/spasial-lahan?status=DITERIMA', [
            'type' => 'FeatureCollection',
            'features' => [],
        ]);

        return view('dashboard.petugas', [
            'page' => 'manajemen-data-spasial',
            'referensi' => $referensi,
            'koleksiLahan' => $koleksiLahan,
        ]);
    }

    public function inputParameterLingkungan()
    {
        $lahan = $this->getData('/lahan/accepted', []);
        $monitoring = $this->getData('/monitoring', []);

        return view('dashboard.petugas', [
            'page' => 'input-parameter-lingkungan',
            'lahan' => $lahan,
            'monitoring' => $monitoring,
        ]);
    }

    public function verifikasiDataPetani()
    {
        $antreanLahan = $this->getData('/lahan/pending', []);
        $antreanPanen = $this->getData('/activities/pending', []);

        return view('dashboard.petugas', [
            'page' => 'verifikasi-data-petani',
            'antreanLahan' => $antreanLahan,
            'antreanPanen' => $antreanPanen,

            // agar view lama yang masih memakai $antrean tidak error
            'antrean' => $antreanPanen,
        ]);
    }

    public function storeSpasial(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'kecamatan_id' => 'required',
            'kelurahan_id' => 'required',
            'nama_lahan' => 'required',
            'luas_lahan_hektar' => 'required|numeric',
        ]);

        $payload = $request->all();
        $payload['status_verifikasi'] = 'DITERIMA';

        $response = $this->sendPost('/spasial-lahan', $payload);

        if ($response->successful()) {
            return redirect('/manajemen-data-spasial')
                ->with('success', 'Data lahan berhasil dipetakan dan langsung legal.');
        }

        return back()
            ->with('error', $response->json('message') ?? 'Gagal menyimpan data spasial lahan.')
            ->withInput();
    }

    public function updateSpasial(Request $request, $id)
    {
        $token = $this->getBearerToken();

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(10)
            ->put($this->gatewayUrl() . "/spasial-lahan/{$id}", $request->all());

        if ($response->successful()) {
            return redirect('/manajemen-data-spasial')
                ->with('success', 'Data spasial lahan berhasil diperbarui.');
        }

        return back()
            ->with('error', $response->json('message') ?? 'Gagal memperbarui data spasial.')
            ->withInput();
    }

    public function destroySpasial($id)
    {
        $token = $this->getBearerToken();

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(10)
            ->delete($this->gatewayUrl() . "/spasial-lahan/{$id}");

        if ($response->successful()) {
            return redirect('/manajemen-data-spasial')
                ->with('success', 'Data spasial lahan berhasil dihapus.');
        }

        return back()->with('error', 'Gagal menghapus data spasial lahan.');
    }

    public function storeParameterLingkungan(Request $request)
    {
        $request->validate([
            'lahan_id' => 'required|integer',
            'tanggal_cek' => 'required|date',
            'ph_air' => 'nullable|numeric',
            'tinggi_muka_air' => 'nullable|numeric',
            'status_air' => 'nullable|string',
            'kekeruhan_air' => 'nullable|string',
            'catatan_petugas' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $response = $this->sendPost('/monitoring', $request->all());

        if ($response->successful()) {
            return redirect('/input-parameter-lingkungan')
                ->with('success', 'Parameter lingkungan berhasil disimpan.');
        }

        return back()
            ->with('error', $response->json('message') ?? 'Gagal menyimpan parameter lingkungan.')
            ->withInput();
    }

    public function aksiVerifikasiLahan(Request $request, $id, $aksi)
    {
        return $this->prosesVerifikasi($request, 'lahan', $id, $aksi);
    }

    public function aksiVerifikasiPanen(Request $request, $id, $aksi)
    {
        return $this->prosesVerifikasi($request, 'panen', $id, $aksi);
    }

    public function aksiVerifikasi(Request $request, $id, $aksi)
    {
        // route lama diarahkan ke verifikasi panen agar tetap kompatibel
        return $this->prosesVerifikasi($request, 'panen', $id, $aksi);
    }

    private function prosesVerifikasi(Request $request, string $jenis, $id, string $aksi)
    {
        $aksi = strtolower($aksi);

        if (in_array($aksi, ['terima', 'diterima', 'approve', 'approved'])) {
            $endpointAction = 'approve';
            $pesanSukses = $jenis === 'lahan'
                ? 'Pengajuan lahan berhasil diterima.'
                : 'Laporan hasil panen berhasil diterima.';
        } elseif (in_array($aksi, ['tolak', 'ditolak', 'reject', 'rejected'])) {
            $endpointAction = 'reject';
            $pesanSukses = $jenis === 'lahan'
                ? 'Pengajuan lahan berhasil ditolak.'
                : 'Laporan hasil panen berhasil ditolak.';
        } else {
            return back()->with('error', 'Aksi verifikasi tidak valid.');
        }

        $endpoint = $jenis === 'lahan'
            ? "/lahan/{$id}/{$endpointAction}"
            : "/activities/{$id}/{$endpointAction}";

        $response = $this->sendPost($endpoint, $request->all());

        if ($response->successful()) {
            return redirect('/verifikasi-data-petani')->with('success', $pesanSukses);
        }

        return back()->with('error', $response->json('message') ?? 'Proses verifikasi gagal.');
    }
}