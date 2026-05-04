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

            $data = $response->json();

            if (!isset($data['user']['role_id'])) {
                return back()->withErrors([
                    'login' => 'Role user tidak ditemukan'
                ]);
            }

            session([
                'token' => $data['token'],
                'user' => $data['user'],
                'role_id' => $data['user']['role_id']
            ]);

            switch ($data['user']['role_id']) {
                case 1:
                    return redirect('/dashboard-petani');

                case 2:
                    return redirect('/dashboard-petugas');

                case 3:
                    return redirect('/dashboard-pejabat');

                case 4:
                    return redirect('/dashboard-admin');

                default:
                    return redirect('/');
            }
        }

        if (!$response->successful()) {

            $error = $response->json();

            return back()->withErrors([
                'login' => $error['message'] ?? 'Login gagal'
            ])->withInput();
        }
    }

    public function logout()
    {
        session()->forget([
            'token',
            'user',
            'role_id'
        ]);

        session()->flush();

        return redirect('/')->with(
            'success',
            'Logout berhasil'
        );
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

public function forgotPassword()
{
    return view('auth.forgot-password');
}

public function sendResetLink(Request $request)
{
    $request->validate([
        'email' => 'required|email'
    ]);

    $response = Http::post(
        'http://localhost:8001/api/forgot-password',
        [
            'email' => $request->email
        ]
    );

    if ($response->successful()) {
        return back()->with('status', 'Link reset password dikirim ke email');
    }

    return back()->withErrors([
        'email' => 'Email tidak ditemukan'
    ]);
}
}


