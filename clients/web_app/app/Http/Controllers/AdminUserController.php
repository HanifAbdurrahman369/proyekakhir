<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AdminUserController extends Controller
{
    protected function gatewayUrl(): string
    {
        return rtrim(env('GATEWAY_URL', env('API_GATEWAY_URL', 'http://127.0.0.1:8003')), '/');
    }

    private function api()
    {
        // acceptJson() memaksa backend merespon JSON, bukan redirect HTML
        return Http::withHeaders(['Connection' => 'close'])->withToken(session('token'))->acceptJson()->withoutVerifying()->timeout(15)->connectTimeout(5);
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
        if (!$response) {
            return 'Backend belum dapat dihubungi.';
        }

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

    private function getRows(string $endpoint): array
    {
        try {
            $response = $this->api()->get($this->gatewayUrl() . '/api/' . ltrim($endpoint, '/'));
            \Log::info("getRows {$endpoint} status: " . $response->status());
            if (!$response->successful()) \Log::error("getRows {$endpoint} failed", ['body' => $response->body()]);
            return $response->successful() ? $this->responseRows($response) : [];
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
    }

    private function send(string $method, string $endpoint, array $payload = [])
    {
        try {
            return $this->api()->{$method}($this->gatewayUrl() . '/api/' . ltrim($endpoint, '/'), $payload);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    public function index()
    {
        $users = $this->getRows('/users');
        $kecamatan = $this->getRows('/kecamatan');
        $kelurahan = $this->getRows('/kelurahan');
        $komunitas = $this->getRows('/master/tables/komunitas');
        
        return view('dashboard.admin', compact('users', 'kecamatan', 'kelurahan', 'komunitas'));
    }

    public function dashboard()
    {
        $users = $this->getRows('/users');
        $komunitas = $this->getRows('/master/tables/komunitas');
        $lahan = $this->getRows('/lahan');
        $panen = $this->getRows('/activities');

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
        $response = $this->send('post', '/users', $request->all());

        if ($response?->successful()) {
            return redirect('/admin/users')->with('success', 'Pengguna berhasil ditambahkan.');
        }

        $error = $this->errorMessage($response, 'Gagal menambahkan pengguna (Cek validasi).');
        return back()->with('error', $error)->withInput();
    }

    public function update(Request $request, $id)
    {
        $response = $this->send('put', '/users/' . $id, $request->all());

        if ($response?->successful()) {
            return redirect('/admin/users')->with('success', 'Data pengguna berhasil diperbarui.');
        }
        
        $error = $this->errorMessage($response, 'Gagal memperbarui data pengguna.');
        return back()->with('error', $error)->withInput();
    }

    public function destroy($id)
    {
        $response = $this->send('delete', '/users/' . $id);

        if ($response?->successful()) {
            return redirect('/admin/users')->with('success', 'Pengguna berhasil dihapus.');
        }

        return redirect('/admin/users')->with('error', $this->errorMessage($response, 'Pengguna gagal dihapus.'));
    }
}
