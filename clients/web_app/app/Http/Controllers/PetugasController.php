<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PetugasController extends Controller
{
    private function gatewayUrl(): string
    {
        $base = rtrim(env('GATEWAY_URL', env('API_GATEWAY_URL', 'http://127.0.0.1:8003')), '/');
        return str_ends_with($base, '/api') ? $base : $base . '/api';
    }

    private function token(): string
    {
        return session('token')
            ?? session('jwt_token')
            ?? session('access_token')
            ?? session('api_token')
            ?? '';
    }

    private function http()
    {
        $http = Http::acceptJson()->timeout(10);
        return $this->token() ? $http->withToken($this->token()) : $http;
    }

    private function api(string $endpoint): string
    {
        return $this->gatewayUrl() . '/' . ltrim($endpoint, '/');
    }

    private function getData(string $endpoint, mixed $default = [])
    {
        if (!$this->token()) {
            return $default;
        }

        try {
            $response = $this->http()->get($this->api($endpoint));

            if (!$response->successful()) {
                return $default;
            }

            $json = $response->json();
            return $json['data'] ?? $json ?? $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private function postData(string $endpoint, array $payload = [])
    {
        return $this->http()->post($this->api($endpoint), $payload);
    }

    private function putData(string $endpoint, array $payload = [])
    {
        return $this->http()->put($this->api($endpoint), $payload);
    }

    private function deleteData(string $endpoint)
    {
        return $this->http()->delete($this->api($endpoint));
    }

    public function index()
    {
        $pendingLahan = $this->getData('/lahan/pending', []);
        $pendingPanen = $this->getData('/panen/pending', []);
        $notifikasi = $this->getData('/notifikasi', []);

        $stats = [
            'pending_lahan' => is_countable($pendingLahan) ? count($pendingLahan) : 0,
            'pending_panen' => is_countable($pendingPanen) ? count($pendingPanen) : 0,
            'total_pending' => (is_countable($pendingLahan) ? count($pendingLahan) : 0) + (is_countable($pendingPanen) ? count($pendingPanen) : 0),
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

    public function manajemenDataSpasial(Request $request)
    {
        $referensi = $this->getData('/spasial-lahan/referensi', [
            'petani' => [],
            'kecamatan' => [],
            'kelurahan' => [],
            'tipe_lahan' => [],
        ]);

        $koleksiLahan = $this->getData('/spasial-lahan?kabupaten=batola&status=DITERIMA', [
            'type' => 'FeatureCollection',
            'features' => [],
        ]);

        $lahanDiterima = $this->getData('/lahan/accepted', []);
        $lahanBelumDipetakan = collect(is_array($lahanDiterima) ? $lahanDiterima : [])
            ->filter(function ($lahan) {
                $statusSpasial = data_get($lahan, 'status_spasial');
                $lat = data_get($lahan, 'latitude');
                $lng = data_get($lahan, 'longitude');
                $polygon = data_get($lahan, 'polygon_geojson') ?? data_get($lahan, 'geojson') ?? data_get($lahan, 'polygon_area');

                return $statusSpasial === 'BELUM_DIPETAKAN' || empty($lat) || empty($lng) || empty($polygon);
            })
            ->values()
            ->all();

        return view('dashboard.petugas', [
            'page' => 'manajemen-data-spasial',
            'referensi' => $referensi,
            'koleksiLahan' => $koleksiLahan,
            'lahanDiterima' => $lahanDiterima,
            'lahanBelumDipetakan' => $lahanBelumDipetakan,
            'highlightLahanId' => $request->query('lahan_id'),
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

    public function verifikasiDataPetani(Request $request)
    {
        $pendingLahan = $this->getData('/lahan/pending', []);
        $panenPending = $this->getData('/panen/pending', []);

        return view('dashboard.petugas', [
            'page' => 'verifikasi-data-petani',
            'pendingLahan' => $pendingLahan,
            'panenPending' => $panenPending,
            'pendingPanen' => $panenPending,
            'highlightPanenId' => $request->query('id'),
            'highlightLahanId' => $request->query('lahan_id'),
        ]);
    }

    public function storeSpasial(Request $request)
    {
        $request->validate([
            'lahan_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'kecamatan_id' => 'required|integer',
            'kelurahan_id' => 'nullable|integer',
            'tipe_lahan_id' => 'nullable|integer',
            'nama_lahan' => 'required|string|max:100',
            'pemilik_lahan' => 'nullable|string|max:100',
            'luas_lahan_hektar' => 'required|numeric|min:0',
            'alamat_detail' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'polygon_geojson' => 'nullable|string',
        ]);

        $payload = $request->all();
        $payload['status_verifikasi'] = 'DITERIMA';
        $payload['status_spasial'] = 'SUDAH_DIPETAKAN';

        $response = $request->filled('lahan_id')
            ? $this->putData('/spasial-lahan/' . $request->input('lahan_id'), $payload)
            : $this->postData('/spasial-lahan', $payload);

        if ($response->successful()) {
            return redirect('/manajemen-data-spasial')->with('success', 'Data spasial lahan berhasil disimpan.');
        }

        return back()->with('error', $response->json('message') ?? 'Gagal menyimpan data spasial lahan.')->withInput();
    }

    public function updateSpasial(Request $request, $id)
    {
        $payload = $request->all();
        $payload['status_spasial'] = 'SUDAH_DIPETAKAN';

        $response = $this->putData('/spasial-lahan/' . $id, $payload);

        if ($response->successful()) {
            return redirect('/manajemen-data-spasial')->with('success', 'Data spasial lahan berhasil diperbarui.');
        }

        return back()->with('error', $response->json('message') ?? 'Gagal memperbarui data spasial.')->withInput();
    }

    public function destroySpasial($id)
    {
        $response = $this->deleteData('/spasial-lahan/' . $id);

        if ($response->successful()) {
            return redirect('/manajemen-data-spasial')->with('success', 'Data spasial lahan berhasil dihapus.');
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

        $response = $this->postData('/monitoring', $request->all());

        if ($response->successful()) {
            return redirect('/input-parameter-lingkungan')->with('success', 'Parameter lingkungan berhasil disimpan.');
        }

        return back()->with('error', $response->json('message') ?? 'Gagal menyimpan parameter lingkungan.')->withInput();
    }

    public function aksiVerifikasiLahan(Request $request, $id, $aksi)
    {
        $aksi = strtolower($aksi);
        $endpointAction = in_array($aksi, ['terima', 'diterima', 'setuju', 'approve', 'approved'], true) ? 'approve' : 'reject';

        $response = $this->postData('/lahan/' . $id . '/' . $endpointAction, $request->all());

        if ($response->successful()) {
            if ($endpointAction === 'approve') {
                return redirect('/manajemen-data-spasial?lahan_id=' . $id)
                    ->with('success', 'Pengajuan lahan disetujui. Lanjutkan membuat titik lokasi dan polygon lahan.');
            }

            return redirect('/verifikasi-data-petani')->with('success', 'Pengajuan lahan berhasil ditolak.');
        }

        return back()->with('error', $response->json('message') ?? 'Proses verifikasi lahan gagal.');
    }

    public function aksiVerifikasiPanen(Request $request, $id, $aksi)
    {
        $aksi = strtolower($aksi);

        if (in_array($aksi, ['terima', 'setuju', 'diterima', 'approve', 'approved'], true)) {
            $aksiApi = 'DITERIMA';
        } elseif (in_array($aksi, ['tolak', 'ditolak', 'reject', 'rejected'], true)) {
            $aksiApi = 'DITOLAK';
        } else {
            return redirect('/verifikasi-data-petani')->with('error', 'Aksi verifikasi panen tidak valid.');
        }

        $response = $this->postData('/panen/' . $id . '/verifikasi', ['aksi' => $aksiApi] + $request->all());

        if ($response->successful()) {
            return redirect('/verifikasi-data-petani')->with('success', $response->json('message') ?? 'Status hasil panen berhasil diperbarui.');
        }

        return redirect('/verifikasi-data-petani')->with('error', $response->json('message') ?? 'Gagal memverifikasi hasil panen.');
    }

    public function aksiVerifikasi(Request $request, $id, $aksi)
    {
        return $this->aksiVerifikasiPanen($request, $id, $aksi);
    }

    public function redirectVerifikasiPanen($id)
    {
        return redirect('/verifikasi-data-petani?tipe=panen&id=' . $id);
    }

    public function bukaNotifikasi($id)
    {
        $response = $this->http()->get($this->api('/notifikasi/' . $id));

        if ($response->successful()) {
            $targetUrl = $response->json('data.target_url') ?? '/verifikasi-data-petani';
            return redirect($targetUrl);
        }

        return redirect('/verifikasi-data-petani');
    }
}
