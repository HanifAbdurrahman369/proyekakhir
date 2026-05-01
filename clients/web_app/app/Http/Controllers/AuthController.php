<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'g-recaptcha-response' => 'required'
        ]);

        $response = Http::post(
            'http://localhost:8001/api/login',
            [
                'email' => $request->email,
                'password' => $request->password,
                'g-recaptcha-response' => $request->input('g-recaptcha-response')
            ]
        );

        if ($response->successful()) {

            session([
                'token' => $response->json()['token']
            ]);

            return redirect('/');
        }

        return back()->withErrors([
            'login' => 'Login gagal'
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


