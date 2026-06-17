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
        return Http::withToken(session('token'))->acceptJson()->withoutVerifying();
    }

    public function index()
    {
        $response = $this->api()->get($this->gatewayUrl() . '/api/users');
        $data = $response->json();
        
        // Membaca format data secara fleksibel, baik itu di dalam ['data'] atau array langsung
        $users = isset($data['data']) ? $data['data'] : (is_array($data) ? $data : []);
        
        return view('dashboard.admin', compact('users'));
    }

    public function store(Request $request)
    {
        $response = $this->api()->post($this->gatewayUrl() . '/api/users', $request->all());

        if ($response->successful()) {
            return redirect('/dashboard-admin')->with('success', 'Pengguna berhasil ditambahkan.');
        }

        // Ambil pesan error riil dari database/backend
        $error = $response->json('message') ?? $response->json('error') ?? 'Gagal menambahkan pengguna (Cek validasi).';
        return back()->with('error', $error)->withInput();
    }

    public function update(Request $request, $id)
    {
        $response = $this->api()->put($this->gatewayUrl() . '/api/users/' . $id, $request->all());

        if ($response->successful()) {
            return redirect('/dashboard-admin')->with('success', 'Data pengguna berhasil diperbarui.');
        }
        
        $error = $response->json('message') ?? 'Gagal memperbarui data pengguna.';
        return back()->with('error', $error)->withInput();
    }

    public function destroy($id)
    {
        $response = $this->api()->delete($this->gatewayUrl() . '/api/users/' . $id);
        return redirect('/dashboard-admin')->with('success', 'Pengguna berhasil dihapus.');
    }
}