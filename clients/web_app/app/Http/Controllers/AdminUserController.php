<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AdminUserController extends Controller
{
    protected function gatewayUrl(): string
    {
        return env('GATEWAY_URL', 'http://127.0.0.1:8003');
    }

    private function api()
    {
        // acceptJson() memaksa backend merespon JSON, bukan redirect HTML
        return Http::withHeaders(['Connection' => 'close'])->withToken(session('token'))->acceptJson()->withoutVerifying()->timeout(10);
    }

    private function responseRows($response): array
    {
        $data = $response->json();

        if (!is_array($data)) {
            return [];
        }

        return $data['data'] ?? $data['rows'] ?? $data;
    }

    private function errorMessage($response, string $fallback): string
    {
        $errors = $response->json('errors');

        if (is_array($errors)) {
            $messages = [];

            foreach ($errors as $fieldErrors) {
                foreach ((array) $fieldErrors as $message) {
                    $messages[] = $message;
                }
            }

            if (!empty($messages)) {
                return implode(' ', $messages);
            }
        }

        return $response->json('message') ?? $response->json('error') ?? $fallback;
    }

    public function index()
    {
        $response = $this->api()->get($this->gatewayUrl() . '/api/users');
        $kecamatanResponse = $this->api()->get($this->gatewayUrl() . '/api/kecamatan');
        $kelurahanResponse = $this->api()->get($this->gatewayUrl() . '/api/kelurahan');
        $komunitasResponse = $this->api()->get($this->gatewayUrl() . '/api/master/tables/komunitas');
        
        // Membaca format data secara fleksibel, baik itu di dalam ['data'] atau array langsung
        $users = $response->successful() ? $this->responseRows($response) : [];
        $kecamatan = $kecamatanResponse->successful() ? $this->responseRows($kecamatanResponse) : [];
        $kelurahan = $kelurahanResponse->successful() ? $this->responseRows($kelurahanResponse) : [];
        $komunitas = $komunitasResponse->successful() ? $this->responseRows($komunitasResponse) : [];
        
        return view('dashboard.admin', compact('users', 'kecamatan', 'kelurahan', 'komunitas'));
    }

    public function dashboard()
    {
        // Panggil endpoint dari API Gateway untuk mendapatkan rekapitulasi data
        $usersResponse = $this->api()->get($this->gatewayUrl() . '/api/users');
        $komunitasResponse = $this->api()->get($this->gatewayUrl() . '/api/master/tables/komunitas');
        $lahanResponse = $this->api()->get($this->gatewayUrl() . '/api/lahan');
        $panenResponse = $this->api()->get($this->gatewayUrl() . '/api/activities');

        $users = $usersResponse->successful() ? $this->responseRows($usersResponse) : [];
        $komunitas = $komunitasResponse->successful() ? $this->responseRows($komunitasResponse) : [];
        $lahan = $lahanResponse->successful() ? $this->responseRows($lahanResponse) : [];
        $panen = $panenResponse->successful() ? $this->responseRows($panenResponse) : [];

        // Hitung statistik user
        $roleCounts = collect($users)->countBy('role_id');
        $stats = [
            'total_users' => count($users),
            'total_petani' => $roleCounts->get(1, 0) + $roleCounts->get(5, 0),
            'total_petugas' => $roleCounts->get(2, 0),
            'total_pejabat_admin' => $roleCounts->get(3, 0) + $roleCounts->get(4, 0),
            'total_komunitas' => count($komunitas),
            'total_lahan' => count($lahan),
            'total_panen' => count($panen)
        ];

        return view('dashboard.dashboard_admin', compact('stats', 'users', 'komunitas', 'lahan'));
    }

    public function store(Request $request)
    {
        $response = $this->api()->post($this->gatewayUrl() . '/api/users', $request->all());

        if ($response->successful()) {
            return redirect('/admin/users')->with('success', 'Pengguna berhasil ditambahkan.');
        }

        $error = $this->errorMessage($response, 'Gagal menambahkan pengguna (Cek validasi).');
        return back()->with('error', $error)->withInput();
    }

    public function update(Request $request, $id)
    {
        $response = $this->api()->put($this->gatewayUrl() . '/api/users/' . $id, $request->all());

        if ($response->successful()) {
            return redirect('/admin/users')->with('success', 'Data pengguna berhasil diperbarui.');
        }
        
        $error = $this->errorMessage($response, 'Gagal memperbarui data pengguna.');
        return back()->with('error', $error)->withInput();
    }

    public function destroy($id)
    {
        $response = $this->api()->delete($this->gatewayUrl() . '/api/users/' . $id);
        return redirect('/admin/users')->with('success', 'Pengguna berhasil dihapus.');
    }
}
