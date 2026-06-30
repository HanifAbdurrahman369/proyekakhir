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

    private function currentUserId(): ?int
    {
        $id = session('user.id') ?? data_get(session('user'), 'id');

        return $id ? (int) $id : null;
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

    private function getJson(string $endpoint, array $query = []): array
    {
        if (!$this->token()) {
            return [];
        }

        try {
            $response = $this->http()->get($this->api($endpoint), $query);

            if (!$response->successful()) {
                return [];
            }

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            return [];
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

        $spasialReferensi = $this->getData('/spasial-lahan/referensi', []);
        $petani = $spasialReferensi['petani'] ?? [];

        return view('dashboard.petugas', [
            'page' => 'dashboard',
            'stats' => $stats,
            'pendingLahan' => $pendingLahan,
            'pendingPanen' => $pendingPanen,
            'notifikasi' => $notifikasi,
            'petani' => $petani,
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

        $spasialResponse = $this->getJson('/spasial-lahan', [
            'status' => 'ALL',
            'kabupaten' => 'batola',
        ]);
        $batasWilayah = $this->getJson('/batas-wilayah');
        $batasKecamatan = $this->getJson('/batas-kecamatan');

        $spasialRows = $spasialResponse['data'] ?? [];
        $koleksiLahan = [
            'type' => 'FeatureCollection',
            'features' => [],
        ];

        $punyaPolygon = function ($lahan) {
            $polygon = data_get($lahan, 'polygon_geojson') ?? data_get($lahan, 'geojson') ?? data_get($lahan, 'polygon_area');

            return !empty($polygon);
        };

        $lahanLamaSpasial = collect($spasialRows)
            ->filter(function ($lahan) use ($punyaPolygon) {
                return $punyaPolygon($lahan);
            })
            ->values()
            ->all();

        $lahanBelumDipetakan = collect($spasialRows)
            ->filter(function ($lahan) use ($punyaPolygon) {
                $statusVerifikasi = strtoupper((string) data_get($lahan, 'status_verifikasi', ''));

                return $statusVerifikasi === 'DITERIMA' && !$punyaPolygon($lahan);
            })
            ->values()
            ->all();

        $totalSpasialTerdata = collect($spasialRows)
            ->pluck('id')
            ->filter()
            ->unique()
            ->count();

        $spasialSummary = $spasialResponse['summary'] ?? [
            'total' => is_countable($spasialRows) ? count($spasialRows) : 0,
            'sudah_dipetakan' => 0,
            'belum_dipetakan' => 0,
        ];
        $spasialSummary['total'] = $totalSpasialTerdata;
        $spasialSummary['sudah_dipetakan'] = count($lahanLamaSpasial);
        $spasialSummary['belum_dipetakan'] = count($lahanBelumDipetakan);
        $spasialSummary['persentase_lengkap'] = $spasialSummary['total'] > 0
            ? round(($spasialSummary['sudah_dipetakan'] / $spasialSummary['total']) * 100, 2)
            : 0;

        $lahanDiterima = is_array($spasialRows) ? $spasialRows : [];

        return view('dashboard.petugas', [
            'page' => 'manajemen-data-spasial',
            'referensi' => $referensi,
            'koleksiLahan' => $koleksiLahan,
            'batasWilayah' => $batasWilayah,
            'batasKecamatan' => $batasKecamatan,
            'spasialRows' => $spasialRows,
            'spasialSummary' => $spasialSummary,
            'lahanDiterima' => $lahanDiterima,
            'lahanBelumDipetakan' => $lahanBelumDipetakan,
            'lahanLamaSpasial' => $lahanLamaSpasial,
            'highlightLahanId' => $request->query('lahan_id'),
        ]);
    }

    public function lahanTermonitor()
    {
        $previewData = $this->getData('/lahan-termonitor/preview', ['lands' => [], 'sensors' => []]);
        $lahanHuma = $this->getData('/lahan-termonitor', []);
        $monitoringHuma = $this->getData('/lahan-termonitor/monitoring', []);

        return view('dashboard.petugas', [
            'page' => 'lahan-termonitor',
            'previewData' => $previewData,
            'lahanHuma' => $lahanHuma,
            'monitoringHuma' => $monitoringHuma,
        ]);
    }

    public function verifikasiDataPetani(Request $request)
    {
        $pendingLahan = $this->getData('/lahan/pending', []);
        $panenPending = $this->getData('/panen/pending', []);
        $spasialReferensi = $this->getData('/spasial-lahan/referensi', []);
        $petani = $spasialReferensi['petani'] ?? [];

        return view('dashboard.petugas', [
            'page' => 'verifikasi-data-petani',
            'pendingLahan' => $pendingLahan,
            'panenPending' => $panenPending,
            'pendingPanen' => $panenPending,
            'highlightPanenId' => $request->query('id'),
            'highlightLahanId' => $request->query('lahan_id'),
            'petani' => $petani,
        ]);
    }

    public function pendingCounts()
    {
        $pendingLahan = $this->getData('/lahan/pending', []);
        $pendingPanen = $this->getData('/panen/pending', []);
        $lahanCount = is_countable($pendingLahan) ? count($pendingLahan) : 0;
        $panenCount = is_countable($pendingPanen) ? count($pendingPanen) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'pending_lahan' => $lahanCount,
                'pending_panen' => $panenCount,
                'total_pending' => $lahanCount + $panenCount,
            ],
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
            'tahun_lbs' => 'nullable|in:2017,2024',
            'luas_lahan_hektar' => 'required|numeric|min:0.0001',
            'alamat_detail' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'polygon_geojson' => 'required|string',
        ]);

        $payload = $request->all();
        $payload['status_verifikasi'] = 'DITERIMA';
        $payload['status_spasial'] = 'SUDAH_DIPETAKAN';
        unset($payload['catatan_spasial']);

        if ($this->currentUserId()) {
            $payload['spasial_updated_by'] = $this->currentUserId();
        }

        $response = $request->filled('lahan_id')
            ? $this->putData('/spasial-lahan/' . $request->input('lahan_id'), $payload)
            : $this->postData('/spasial-lahan', $payload);

        if ($response->successful()) {
            return redirect('/manajemen-data-spasial')->with('success', 'Data lahan berhasil dipetakan dan tersimpan ke tabel lahan_sawah.');
        }

        return back()->with('error', $response->json('message') ?? 'Gagal menyimpan data spasial lahan.')->withInput();
    }

    public function updateSpasial(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'nullable|integer',
            'kecamatan_id' => 'required|integer',
            'kelurahan_id' => 'nullable|integer',
            'tipe_lahan_id' => 'nullable|integer',
            'nama_lahan' => 'required|string|max:100',
            'tahun_lbs' => 'nullable|in:2017,2024',
            'luas_lahan_hektar' => 'required|numeric|min:0.0001',
            'alamat_detail' => 'nullable|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'polygon_geojson' => 'required|string',
        ]);

        $payload = $request->all();
        $payload['status_spasial'] = 'SUDAH_DIPETAKAN';
        unset($payload['catatan_spasial']);

        if ($this->currentUserId()) {
            $payload['spasial_updated_by'] = $this->currentUserId();
        }

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
            return redirect('/manajemen-data-spasial')->with('success', 'Polygon lahan berhasil dikosongkan. Data pengajuan tetap tersimpan sebagai arsip.');
        }

        return back()->with('error', $response->json('message') ?? 'Gagal menghapus data spasial lahan.');
    }

    public function storeParameterLingkungan(Request $request)
    {
        $request->validate([
            'lahan_id' => 'required|integer',
            'tanggal_cek' => 'required|date',
            'ph_air' => 'nullable|numeric',
            'tinggi_muka_air' => 'nullable|numeric',
            'status_air' => 'nullable|in:Surut,Pasang,Banjir,Normal',
            'kekeruhan_air' => 'nullable|string',
            'catatan_petugas' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $response = $this->postData('/monitoring', $request->all());

        if ($response->successful()) {
            return redirect('/lahan-termonitor')->with('success', 'Parameter lingkungan berhasil disimpan.');
        }

        return back()->with('error', $response->json('message') ?? 'Gagal menyimpan parameter lingkungan.')->withInput();
    }

    public function aksiVerifikasiLahan(Request $request, $id, $aksi)
    {
        $aksi = strtolower($aksi);
        $endpointAction = in_array($aksi, ['terima', 'diterima', 'setuju', 'approve', 'approved'], true) ? 'approve' : 'reject';

        $payload = $request->all();

        if ($this->currentUserId()) {
            $payload['verified_by'] = $this->currentUserId();
        }

        $response = $this->postData('/lahan/' . $id . '/' . $endpointAction, $payload);

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
