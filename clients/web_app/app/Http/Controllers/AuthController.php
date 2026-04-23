<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // kirim ke microservice auth
        $response = Http::post('http://localhost:8001/api/login', [
            'email' => $request->email,
            'password' => $request->password,
        ]);

        // cek response
        if ($response->successful()) {

            $token = $response->json()['token'];

            // simpan token ke session (frontend client)
            session(['token' => $token]);

            return redirect('/');

        }

        return back()->withErrors([
            'login' => 'Email atau password salah'
        ]);
    }

    public function register(Request $request)
    {
        $response = Http::post('http://localhost:8001/api/register', [
            'nama_lengkap' => $request->nama_lengkap,
            'email' => $request->email,
            'password' => $request->password,
            'password_confirmation' => $request->password_confirmation,
            'no_hp' => $request->nomor_handphone,
            'alamat' => $request->alamat,
        ]);

        if ($response->successful()) {

            return redirect('/login')->with('success', 'Registrasi berhasil, silakan login');

        }

        return back()->withErrors([
            'register' => 'Gagal melakukan registrasi'
        ]);
    }
}


