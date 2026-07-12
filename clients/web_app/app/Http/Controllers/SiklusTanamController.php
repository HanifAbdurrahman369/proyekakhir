<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\SiklusTanam;
use App\Models\LahanSawah;  


class SiklusTanamController extends Controller
{
    protected function gatewayUrl(): string
    {
        return rtrim(env('GATEWAY_URL', env('API_GATEWAY_URL', 'http://127.0.0.1:8003')), '/');
    }

    private function api(?string $token = null)
    {
        $http = Http::acceptJson()
            ->withoutVerifying()
            ->timeout(15)
            ->connectTimeout(5);

        return $token ? $http->withToken($token) : $http;
    }

    private function getData(string $endpoint, array $query = [], array $default = []): array
    {
        try {
            $response = $this->api(session('token'))->get($this->gatewayUrl() . '/api/' . ltrim($endpoint, '/'), $query);

            if (!$response->successful()) {
                return $default;
            }

            return $response->json('data') ?? $default;
        } catch (\Throwable $e) {
            report($e);
            return $default;
        }
    }

    private function sendToApi(string $method, string $endpoint, array $payload = [])
    {
        try {
            return $this->api(session('token'))->{$method}($this->gatewayUrl() . '/api/' . ltrim($endpoint, '/'), $payload);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    /**
     * FORM INPUT AKTIVITAS TANAM
     */
    public function create()
    {
        $token = session('token');

        if (!$token) {
            return redirect('/login')
                ->with('error', 'Session login habis, silakan login kembali.');
        }

        $lahan = $this->getData('/lahan/dropdown');
        $bibit = $this->getData('/bibit');
        $pupuk = $this->getData('/jenis-pupuk');
        $siklusTanam = $this->getData('/my-siklus-tanam');

        return view('partials.sidebar.petani.lapor-tanam', compact(
            'lahan',
            'bibit',
            'pupuk',
            'siklusTanam'
        ));
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'lahan_id' => 'required|integer',
            'luas_tanam_hektar' => 'required|numeric|min:0.01',
            'bibit_id' => 'required|integer',
            'tanggal_tanam' => 'required|date|before_or_equal:today',
            'estimasi_hari_tanam' => 'required|integer|min:1',
            'pupuk_id' => 'required|integer',
            'tanggal_pemupukan' => 'required|date|after_or_equal:tanggal_tanam|before_or_equal:today',
            'takaran' => 'required|numeric|min:0.01',
        ], [
            'estimasi_hari_tanam.required' => 'Estimasi hari tanam wajib diisi.',
            'pupuk_id.required' => 'Catatan pemupukan (jenis pupuk) wajib diisi.',
            'takaran.required' => 'Takaran pupuk wajib diisi.',
        ]);

        $token = session('token'); 

        if (!$token) {
            return redirect()
                ->back()
                ->with('error', 'Token tidak ditemukan. Silakan login ulang.');
        }

        $response = $this->sendToApi('post', '/activities', [
            'lahan_id' => $request->lahan_id,
            'luas_tanam_hektar' => $request->luas_tanam_hektar,
            'bibit_id' => $request->bibit_id,
            'tanggal_tanam' => $request->tanggal_tanam,
            'estimasi_hari_tanam' => $request->estimasi_hari_tanam,
            'pupuk_id' => $request->pupuk_id,
            'tanggal_pemupukan' => $request->tanggal_pemupukan,
            'takaran' => $request->takaran,
        ]);

        if (!$response) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Backend laporan tanam belum dapat dihubungi. Silakan coba lagi.');
        }

        if ($response->successful()) {
            return redirect()
                ->back()
                ->with('success', $response->json('message') ?? 'Laporan tanam dan pemupukan berhasil disimpan.');
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', $response->json('message') ?? 'Gagal menyimpan laporan tanam.');
    }

    public function editTanam($id)
    {
        $token = session('token');
        if (!$token) {
            return redirect('/login')
                ->with('error', 'Session login habis, silakan login kembali.');
        }

        $detail = $this->sendToApi('get', '/activities/' . $id);
        if (!$detail || !$detail->successful()) {
            return redirect()->route('lapor.tanam')->with('error', $detail?->json('message') ?? 'Laporan tanam tidak ditemukan atau backend belum dapat dihubungi.');
        }

        return view('partials.sidebar.petani.lapor-tanam', [
            'editTanam' => $detail->json('data'),
            'lahan' => $this->getData('/lahan/dropdown'),
            'bibit' => $this->getData('/bibit'),
            'pupuk' => $this->getData('/jenis-pupuk'),
            'siklusTanam' => $this->getData('/my-siklus-tanam'),
        ]);
    }

    public function updateTanam(Request $request, $id)
    {
        $validated = $request->validate([
            'lahan_id' => 'required|integer',
            'luas_tanam_hektar' => 'required|numeric|min:0.01',
            'bibit_id' => 'required|integer',
            'tanggal_tanam' => 'required|date|before_or_equal:today',
            'estimasi_hari_tanam' => 'required|integer|min:1',
            'pupuk_id' => 'required|integer',
            'tanggal_pemupukan' => 'required|date|after_or_equal:tanggal_tanam|before_or_equal:today',
            'takaran' => 'required|numeric|min:0.01',
        ]);

        $response = $this->sendToApi('put', '/activities/' . $id, $validated);

        if (!$response) {
            return back()->withInput()->with('error', 'Backend laporan tanam belum dapat dihubungi. Silakan coba lagi.');
        }

        if ($response->successful()) {
            return redirect()->route('lapor.tanam')->with('success', $response->json('message'));
        }

        return back()->withInput()->with('error', $response->json('message') ?? 'Laporan tanam gagal diperbarui.');
    }

    public function destroyTanam($id)
    {
        $response = $this->sendToApi('delete', '/activities/' . $id);

        if (!$response) {
            return redirect()->route('lapor.tanam')->with('error', 'Backend laporan tanam belum dapat dihubungi.');
        }

        return redirect()->route('lapor.tanam')->with(
            $response->successful() ? 'success' : 'error',
            $response->json('message') ?? ($response->successful() ? 'Laporan tanam dihapus.' : 'Laporan tanam gagal dihapus.')
        );
    }

    public function riwayatPanen(Request $request)
    {
        $token = session('token');

        if (!$token) {
            return redirect('/login')->with(
                'error',
                'Silakan login terlebih dahulu'
            );
        }

        $riwayat = $this->getData('/riwayat-panen', [
            'per_page' => 3,
            'riwayat_page' => $request->query('page', 1),
        ]);

        $riwayatPupuk = $this->getData('/siklus-pupuk', [
            'per_page' => 10,
            'pupuk_page' => $request->query('pupuk_page', 1),
        ]);

        $riwayatLahan = $this->getData('/lahan', [
            'per_page' => 10,
            'page' => $request->query('lahan_page', 1),
        ]);

        return view('partials.sidebar.petani.riwayat-panen', compact('riwayat', 'riwayatPupuk', 'riwayatLahan'));
    }

    public function edit($id)
    {
        $token = session('token');

        if (!$token) {
            return redirect('/login')
                ->with('error', 'Session login habis, silakan login kembali.');
        }

        $detailResponse = $this->sendToApi('get', '/lapor-panen/' . $id);

        if (!$detailResponse || !$detailResponse->successful()) {
            return redirect()->route('riwayat.panen')
                ->with('error', 'Data laporan panen tidak ditemukan atau backend belum dapat dihubungi.');
        }

        $editPanen = $detailResponse->json()['data'] ?? [];

        if (($editPanen['status_verifikasi'] ?? '') !== 'DITOLAK') {
            return redirect()->route('riwayat.panen')
                ->with('error', 'Hanya data pengajuan yang ditolak yang dapat diperbaiki.');
        }

        $lahan = $this->getData('/lahan/dropdown');
        $bibit = $this->getData('/bibit');

        // Format dates to Y-m-d for date picker compatibility
        if (!empty($editPanen['tanggal_tanam'])) {
            $editPanen['tanggal_tanam'] = \Carbon\Carbon::parse($editPanen['tanggal_tanam'])->format('Y-m-d');
        }
        if (!empty($editPanen['tanggal_panen'])) {
            $editPanen['tanggal_panen'] = \Carbon\Carbon::parse($editPanen['tanggal_panen'])->format('Y-m-d');
        }

        return view('partials.sidebar.petani.lapor-panen', compact('lahan', 'bibit', 'editPanen'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tanggal_panen' => 'required',
            'hasil_panen' => 'required',
        ]);

        $token = session('token');

        if (!$token) {
            return redirect()
                ->back()
                ->with('error', 'Token tidak ditemukan. Silakan login ulang.');
        }

        $response = $this->sendToApi('put', '/lapor-panen/' . $id, [
            'tanggal_panen' => $request->tanggal_panen,
            'hasil_panen' => $request->hasil_panen,
        ]);

        if (!$response) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Backend laporan panen belum dapat dihubungi. Silakan coba lagi.');
        }

        if ($response->successful()) {
            return redirect()
                ->route('riwayat.panen')
                ->with('success', 'Laporan panen berhasil diperbaiki dan diajukan ulang.');
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', $response->json('message') ?? 'Gagal memperbarui laporan panen');
    }

    public function storePemupukan(Request $request)
    {
        $request->validate([
            'siklus_tanam_id' => 'required|integer',
            'pupuk_id' => 'required|integer',
            'tanggal_pemupukan' => 'required|date',
            'takaran' => 'required|numeric|min:0.01',
        ]);

        $token = session('token');

        if (!$token) {
            return redirect()
                ->back()
                ->with('error', 'Token tidak ditemukan. Silakan login ulang.');
        }

        $response = $this->sendToApi('post', '/siklus-pupuk', [
            'siklus_tanam_id' => $request->siklus_tanam_id,
            'pupuk_id' => $request->pupuk_id,
            'tanggal_pemupukan' => $request->tanggal_pemupukan,
            'takaran' => $request->takaran,
        ]);

        if (!$response) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Backend pemupukan belum dapat dihubungi. Silakan coba lagi.');
        }

        if ($response->successful()) {
            return redirect()
                ->back()
                ->with('success', 'Catatan pemupukan berhasil disimpan.');
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', $response->json('message') ?? 'Gagal menyimpan catatan pemupukan.');
    }

    public function createLaporPanen()
    {
        $token = session('token');

        if (!$token) {
            return redirect('/login')
                ->with('error', 'Session login habis, silakan login kembali.');
        }

        $lahan = $this->getData('/lahan/dropdown');
        $bibit = $this->getData('/bibit');
        $siklusTanam = $this->getData('/my-siklus-tanam');

        return view('partials.sidebar.petani.lapor-panen', compact(
            'lahan',
            'bibit',
            'siklusTanam'
        ));
    }

    public function storeLaporPanen(Request $request)
    {
        $request->validate([
            'siklus_tanam_id' => 'required',
            'tanggal_panen' => 'required|date',
            'hasil_panen' => 'required|numeric|min:0',
            'estimasi_panen' => 'nullable|integer',
        ]);

        $token = session('token');

        if (!$token) {
            return redirect()
                ->back()
                ->with('error', 'Token tidak ditemukan. Silakan login ulang.');
        }

        $response = $this->sendToApi('post', '/lapor-panen', [
            'siklus_tanam_id' => $request->siklus_tanam_id,
            'tanggal_panen' => $request->tanggal_panen,
            'hasil_panen' => $request->hasil_panen,
            'estimasi_panen' => $request->estimasi_panen,
        ]);

        if (!$response) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Backend laporan panen belum dapat dihubungi. Silakan coba lagi.');
        }

        if ($response->successful()) {
            return redirect()
                ->route('riwayat.panen')
                ->with('success', 'Laporan hasil panen berhasil dikirim dan menunggu verifikasi petugas.');
        }

        return redirect()
            ->back()
            ->withInput()
            ->with('error', $response->json('message') ?? 'Gagal menyimpan laporan hasil panen.');
    }
}
