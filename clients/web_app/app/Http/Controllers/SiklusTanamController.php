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
        return env('GATEWAY_URL', 'http://127.0.0.1:8003');
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

        $roleId = (int) session('role_id');
        $currentMonth = (int) now()->format('n');
        $isKelompokTaniAllowed = ($currentMonth >= 1 && $currentMonth <= 9);
        $isBrigadePanganAllowed = in_array($currentMonth, [10, 11, 12, 1], true);
        $hasOwnLand = ($roleId === 5 && (session('total_lahan') ?? 0) > 0);
        $isAllowed = ($roleId === 1 && $isKelompokTaniAllowed) || ($roleId === 5 && $isBrigadePanganAllowed) || $hasOwnLand;

        if (!$isAllowed) {
            $msg = $roleId === 5 
                ? 'Masa tanam Brigade Pangan hanya diperbolehkan pada bulan Oktober - Januari.' 
                : 'Masa tanam Kelompok Tani hanya diperbolehkan pada bulan Januari - September.';
            return redirect('/dashboard-petani')->with('error', $msg);
        }

        $lahanResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/lahan/dropdown');

        $bibitResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/bibit');

        $pupukResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/jenis-pupuk');

        $siklusResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/my-siklus-tanam');

        $lahan = [];
        $bibit = [];
        $pupuk = [];
        $siklusTanam = [];

        if ($lahanResponse->successful()) {
            $lahan = $lahanResponse->json()['data'] ?? [];
        }

        if ($bibitResponse->successful()) {
            $bibit = $bibitResponse->json()['data'] ?? [];
        }

        if ($pupukResponse->successful()) {
            $pupuk = $pupukResponse->json()['data'] ?? [];
        }

        if ($siklusResponse->successful()) {
            $siklusTanam = $siklusResponse->json()['data'] ?? [];
        }

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

        $roleId = (int) session('role_id');
        $currentMonth = (int) now()->format('n');
        $isKelompokTaniAllowed = ($currentMonth >= 1 && $currentMonth <= 9);
        $isBrigadePanganAllowed = in_array($currentMonth, [10, 11, 12, 1], true);
        $hasOwnLand = ($roleId === 5 && (session('total_lahan') ?? 0) > 0);
        $isAllowed = ($roleId === 1 && $isKelompokTaniAllowed) || ($roleId === 5 && $isBrigadePanganAllowed) || $hasOwnLand;

        if (!$isAllowed) {
            $msg = $roleId === 5 
                ? 'Gagal: Saat ini bukan jadwal masa tanam Brigade Pangan (Oktober - Januari).' 
                : 'Gagal: Saat ini bukan jadwal masa tanam Kelompok Tani (Januari - September).';
            return redirect()->back()->with('error', $msg);
        }

        /**
         * KIRIM KE MICROSERVICE DENGAN BEARER TOKEN
         */
        $response = Http::withToken($token)
            ->post($this->gatewayUrl() . '/api/activities', [
                'lahan_id' => $request->lahan_id,
                'luas_tanam_hektar' => $request->luas_tanam_hektar,
                'bibit_id' => $request->bibit_id,
                'tanggal_tanam' => $request->tanggal_tanam,
                'estimasi_hari_tanam' => $request->estimasi_hari_tanam,
                'pupuk_id' => $request->pupuk_id,
                'tanggal_pemupukan' => $request->tanggal_pemupukan,
                'takaran' => $request->takaran,
            ]);

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

        $roleId = (int) session('role_id');
        $currentMonth = (int) now()->format('n');
        $isKelompokTaniAllowed = ($currentMonth >= 1 && $currentMonth <= 9);
        $isBrigadePanganAllowed = in_array($currentMonth, [10, 11, 12, 1], true);
        $hasOwnLand = ($roleId === 5 && (session('total_lahan') ?? 0) > 0);
        $isAllowed = ($roleId === 1 && $isKelompokTaniAllowed) || ($roleId === 5 && $isBrigadePanganAllowed) || $hasOwnLand;

        if (!$isAllowed) {
            $msg = $roleId === 5 
                ? 'Masa tanam Brigade Pangan hanya diperbolehkan pada bulan Oktober - Januari.' 
                : 'Masa tanam Kelompok Tani hanya diperbolehkan pada bulan Januari - September.';
            return redirect('/dashboard-petani')->with('error', $msg);
        }

        $detail = Http::withToken($token)->acceptJson()->get($this->gatewayUrl() . '/api/activities/' . $id);
        if (!$detail->successful()) {
            return redirect()->route('lapor.tanam')->with('error', $detail->json('message') ?? 'Laporan tanam tidak ditemukan.');
        }

        $lahanResponse = Http::withToken($token)->acceptJson()->get($this->gatewayUrl() . '/api/lahan/dropdown');
        $bibitResponse = Http::withToken($token)->acceptJson()->get($this->gatewayUrl() . '/api/bibit');
        $pupukResponse = Http::withToken($token)->acceptJson()->get($this->gatewayUrl() . '/api/jenis-pupuk');
        $siklusResponse = Http::withToken($token)->acceptJson()->get($this->gatewayUrl() . '/api/my-siklus-tanam');

        return view('partials.sidebar.petani.lapor-tanam', [
            'editTanam' => $detail->json('data'),
            'lahan' => $lahanResponse->successful() ? ($lahanResponse->json('data') ?? []) : [],
            'bibit' => $bibitResponse->successful() ? ($bibitResponse->json('data') ?? []) : [],
            'pupuk' => $pupukResponse->successful() ? ($pupukResponse->json('data') ?? []) : [],
            'siklusTanam' => $siklusResponse->successful() ? ($siklusResponse->json('data') ?? []) : [],
        ]);
    }

    public function updateTanam(Request $request, $id)
    {
        $roleId = (int) session('role_id');
        $currentMonth = (int) now()->format('n');
        $isKelompokTaniAllowed = ($currentMonth >= 1 && $currentMonth <= 9);
        $isBrigadePanganAllowed = in_array($currentMonth, [10, 11, 12, 1], true);
        $hasOwnLand = ($roleId === 5 && (session('total_lahan') ?? 0) > 0);
        $isAllowed = ($roleId === 1 && $isKelompokTaniAllowed) || ($roleId === 5 && $isBrigadePanganAllowed) || $hasOwnLand;

        if (!$isAllowed) {
            $msg = $roleId === 5 
                ? 'Gagal: Saat ini bukan jadwal masa tanam Brigade Pangan (Oktober - Januari).' 
                : 'Gagal: Saat ini bukan jadwal masa tanam Kelompok Tani (Januari - September).';
            return redirect()->back()->with('error', $msg);
        }

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

        $response = Http::withToken(session('token'))
            ->acceptJson()
            ->put($this->gatewayUrl() . '/api/activities/' . $id, $validated);

        if ($response->successful()) {
            return redirect()->route('lapor.tanam')->with('success', $response->json('message'));
        }

        return back()->withInput()->with('error', $response->json('message') ?? 'Laporan tanam gagal diperbarui.');
    }

    public function destroyTanam($id)
    {
        $roleId = (int) session('role_id');
        $currentMonth = (int) now()->format('n');
        $isKelompokTaniAllowed = ($currentMonth >= 1 && $currentMonth <= 9);
        $isBrigadePanganAllowed = in_array($currentMonth, [10, 11, 12, 1], true);
        $hasOwnLand = ($roleId === 5 && (session('total_lahan') ?? 0) > 0);
        $isAllowed = ($roleId === 1 && $isKelompokTaniAllowed) || ($roleId === 5 && $isBrigadePanganAllowed) || $hasOwnLand;

        if (!$isAllowed) {
            $msg = $roleId === 5 
                ? 'Gagal: Saat ini bukan jadwal masa tanam Brigade Pangan (Oktober - Januari).' 
                : 'Gagal: Saat ini bukan jadwal masa tanam Kelompok Tani (Januari - September).';
            return redirect()->back()->with('error', $msg);
        }

        $response = Http::withToken(session('token'))
            ->acceptJson()
            ->delete($this->gatewayUrl() . '/api/activities/' . $id);

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

        $response = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/riwayat-panen', [
                'per_page' => 3,
                'riwayat_page' => $request->query('page', 1)
            ]);

        $riwayat = [];
        if ($response->successful()) {
            $riwayat = $response->json()['data'] ?? [];
        } else {
            session()->flash('error', 'Gagal mengambil data riwayat panen.');
        }

        $pupukResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/siklus-pupuk', [
                'per_page' => 10,
                'pupuk_page' => $request->query('pupuk_page', 1)
            ]);

        $riwayatPupuk = [];
        if ($pupukResponse->successful()) {
            $riwayatPupuk = $pupukResponse->json()['data'] ?? [];
        }

        // Fetch Riwayat Lahan Baru
        $lahanResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/lahan', [
                'per_page' => 10,
                'page' => $request->query('lahan_page', 1)
            ]);

        $riwayatLahan = [];
        if ($lahanResponse->successful()) {
            $riwayatLahan = $lahanResponse->json()['data'] ?? [];
        }

        return view('partials.sidebar.petani.riwayat-panen', compact('riwayat', 'riwayatPupuk', 'riwayatLahan'));
    }

    public function edit($id)
    {
        $token = session('token');

        if (!$token) {
            return redirect('/login')
                ->with('error', 'Session login habis, silakan login kembali.');
        }

        $lahanResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/lahan/dropdown');

        $bibitResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/bibit');

        $detailResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/lapor-panen/' . $id);

        if (!$detailResponse->successful()) {
            return redirect()->route('riwayat.panen')
                ->with('error', 'Data laporan panen tidak ditemukan.');
        }

        $editPanen = $detailResponse->json()['data'] ?? [];

        if (($editPanen['status_verifikasi'] ?? '') !== 'DITOLAK') {
            return redirect()->route('riwayat.panen')
                ->with('error', 'Hanya data pengajuan yang ditolak yang dapat diperbaiki.');
        }

        $lahan = [];
        $bibit = [];

        if ($lahanResponse->successful()) {
            $lahan = $lahanResponse->json()['data'] ?? [];
        }

        if ($bibitResponse->successful()) {
            $bibit = $bibitResponse->json()['data'] ?? [];
        }

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

        $response = Http::withToken($token)
            ->put($this->gatewayUrl() . '/api/lapor-panen/' . $id, [
                'tanggal_panen' => $request->tanggal_panen,
                'hasil_panen' => $request->hasil_panen,
            ]);

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

        $response = Http::withToken($token)
            ->post($this->gatewayUrl() . '/api/siklus-pupuk', [
                'siklus_tanam_id' => $request->siklus_tanam_id,
                'pupuk_id' => $request->pupuk_id,
                'tanggal_pemupukan' => $request->tanggal_pemupukan,
                'takaran' => $request->takaran,
            ]);

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

        $lahanResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/lahan/dropdown');

        $bibitResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/bibit');

        $siklusResponse = Http::withToken($token)
            ->acceptJson()
            ->get($this->gatewayUrl() . '/api/my-siklus-tanam');

        $lahan = [];
        $bibit = [];
        $siklusTanam = [];

        if ($lahanResponse->successful()) {
            $lahan = $lahanResponse->json()['data'] ?? [];
        }

        if ($bibitResponse->successful()) {
            $bibit = $bibitResponse->json()['data'] ?? [];
        }

        if ($siklusResponse->successful()) {
            $siklusTanam = $siklusResponse->json()['data'] ?? [];
        }

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

        $response = Http::withToken($token)
            ->post($this->gatewayUrl() . '/api/lapor-panen', [
                'siklus_tanam_id' => $request->siklus_tanam_id,
                'tanggal_panen' => $request->tanggal_panen,
                'hasil_panen' => $request->hasil_panen,
                'estimasi_panen' => $request->estimasi_panen,
            ]);

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
