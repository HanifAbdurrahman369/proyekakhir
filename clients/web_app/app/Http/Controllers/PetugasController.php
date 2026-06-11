<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PetugasController extends Controller
{
    private function gatewayUrl(): string
    {
        return rtrim(env('GATEWAY_URL', env('API_GATEWAY_URL', 'http://127.0.0.1:8003')), '/') . '/api';
    }

    private function getBearerToken(): string
    {
        return session('token')
            ?? session('jwt_token')
            ?? session('access_token')
            ?? session('api_token')
            ?? '';
    }

    private function http()
    {
        $http = Http::acceptJson()->timeout(15);

        if ($this->getBearerToken()) {
            $http = $http->withToken($this->getBearerToken());
        }

        return $http;
    }

    private function getData(string $endpoint, mixed $default = [])
    {
        if (!$this->getBearerToken()) {
            return $default;
        }

        try {
            $response = $this->http()->get($this->gatewayUrl() . $endpoint);

            if (!$response->successful()) {
                return $default;
            }

            $json = $response->json();

            return $json['data'] ?? $json ?? $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private function getDataWithError(string $endpoint, mixed $default = []): array
    {
        if (!$this->getBearerToken()) {
            return [
                'data' => $default,
                'error' => 'Token login tidak ditemukan. Silakan login ulang.',
            ];
        }

        try {
            $response = $this->http()->get($this->gatewayUrl() . $endpoint);

            if (!$response->successful()) {
                $json = $response->json();

                return [
                    'data' => $default,
                    'error' => $json['message'] ?? 'Gagal mengambil data. Status API: ' . $response->status(),
                ];
            }

            $json = $response->json();

            return [
                'data' => $json['data'] ?? $json ?? $default,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'data' => $default,
                'error' => 'Gagal terhubung ke API: ' . $e->getMessage(),
            ];
        }
    }

    private function sendPost(string $endpoint, array $payload = [])
    {
        return $this->http()
            ->post($this->gatewayUrl() . $endpoint, $payload);
    }

    public function index()
    {
        $pendingLahan = $this->getData('/lahan/pending', []);
        $pendingPanen = $this->getData('/panen/pending', []);
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
            'panenPending' => $pendingPanen,
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

    public function verifikasiDataPetani(Request $request)
    {
        $lahanResult = $this->getDataWithError('/lahan/pending', []);
        $panenResult = $this->getDataWithError('/panen/pending', []);
        $notifikasi = $this->getData('/notifikasi/petugas', []);

        $pendingLahan = $lahanResult['data'];
        $pendingPanen = $panenResult['data'];

        $dataView = [
            'page' => 'verifikasi-data-petani',
            'pendingLahan' => $pendingLahan,
            'pendingPanen' => $pendingPanen,
            'panenPending' => $pendingPanen,
            'notifikasi' => $notifikasi,
            'errorLahan' => $lahanResult['error'],
            'errorPanen' => $panenResult['error'],
            'highlightPanenId' => $request->query('id'),
            'highlightTipe' => $request->query('tipe'),
        ];

        $view = view()->exists('dashboard.petugas.verifikasi-data-petani')
            ? 'dashboard.petugas.verifikasi-data-petani'
            : 'dashboard.petugas';

        return view($view, $dataView);
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
        $response = $this->http()
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
        $response = $this->http()
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
        $jenis = strtolower((string) $request->input('jenis', $request->query('jenis', 'panen')));

        if ($jenis === 'lahan') {
            return $this->prosesVerifikasi($request, 'lahan', $id, $aksi);
        }

        return $this->prosesVerifikasi($request, 'panen', $id, $aksi);
    }

    public function redirectVerifikasiPanen($id)
    {
        return redirect('/verifikasi-data-petani?tipe=panen&id=' . $id);
    }

    public function bukaNotifikasi($id)
    {
        try {
            $response = $this->http()->put($this->gatewayUrl() . '/notifikasi/' . $id . '/read');

            if (!$response->successful()) {
                return redirect('/verifikasi-data-petani')
                    ->with('error', $response->json('message') ?? 'Notifikasi tidak dapat ditandai sudah dibaca.');
            }
        } catch (\Throwable $e) {
            return redirect('/verifikasi-data-petani')
                ->with('error', 'Notifikasi tidak bisa dibuka: ' . $e->getMessage());
        }

        return redirect('/verifikasi-data-petani');
    }

    private function prosesVerifikasi(Request $request, string $jenis, $id, string $aksi)
    {
        $aksi = strtolower($aksi);

        if (in_array($aksi, ['terima', 'diterima', 'setuju', 'approve', 'approved'], true)) {
            $endpointAction = 'approve';
            $pesanSukses = $jenis === 'lahan'
                ? 'Pengajuan lahan berhasil diterima.'
                : 'Laporan hasil panen berhasil diterima dan data lahan sudah otomatis diperbarui.';
        } elseif (in_array($aksi, ['tolak', 'ditolak', 'reject', 'rejected'], true)) {
            $endpointAction = 'reject';
            $pesanSukses = $jenis === 'lahan'
                ? 'Pengajuan lahan berhasil ditolak.'
                : 'Laporan hasil panen berhasil ditolak.';
        } else {
            return back()->with('error', 'Aksi verifikasi tidak valid.');
        }

        $endpoint = $jenis === 'lahan'
            ? "/lahan/{$id}/{$endpointAction}"
            : "/panen/{$id}/{$endpointAction}";

        try {
            $response = $this->sendPost($endpoint, $request->except(['_token']));

            if ($response->successful()) {
                return redirect('/verifikasi-data-petani')->with('success', $response->json('message') ?? $pesanSukses);
            }

            return redirect('/verifikasi-data-petani')
                ->with('error', $response->json('message') ?? 'Proses verifikasi gagal. Status API: ' . $response->status());
        } catch (\Throwable $e) {
            return redirect('/verifikasi-data-petani')
                ->with('error', 'Gagal terhubung ke API verifikasi: ' . $e->getMessage());
        }
    }
}
