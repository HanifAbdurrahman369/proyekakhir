<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AdminUserController extends Controller
{
    private $apiUrl = 'http://127.0.0.1:8002/api/users';

    // Helper untuk HTTP Request dengan Token
    private function api()
    {
        return Http::withToken(session('token'))->withoutVerifying();
    }

public function index()
{
    $response = $this->api()->get($this->apiUrl);
    $users = $response->successful() ? $response->json()['data'] : [];
    
    // Semua fungsi CRUD (Index, Create, Edit) sekarang merujuk ke satu file blade ini
    return view('dashboard.admin', compact('users'));
}

    public function create()
    {
        return view('dashboard.admin.users.create');
    }

    public function store(Request $request)
    {
        $response = $this->api()->post($this->apiUrl, $request->all());

        if ($response->successful()) {
            return redirect('/admin/users')->with('success', 'Pengguna berhasil ditambahkan.');
        }
        return back()->with('error', 'Gagal menambahkan pengguna. Pastikan email belum terdaftar.')->withInput();
    }

    public function edit($id)
    {
        $response = $this->api()->get($this->apiUrl . '/' . $id);
        if ($response->failed()) return redirect('/admin/users')->with('error', 'Data tidak ditemukan.');
        
        $user = $response->json()['data'];
        return view('dashboard.admin.users.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $response = $this->api()->put($this->apiUrl . '/' . $id, $request->all());

        if ($response->successful()) {
            return redirect('/admin/users')->with('success', 'Data pengguna berhasil diperbarui.');
        }
        return back()->with('error', 'Gagal memperbarui data.')->withInput();
    }

    public function destroy($id)
    {
        $response = $this->api()->delete($this->apiUrl . '/' . $id);
        return redirect('/admin/users')->with('success', 'Pengguna berhasil dihapus.');
    }
}