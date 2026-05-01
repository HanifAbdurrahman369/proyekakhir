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

            /*
            =====================================
            PASTIKAN ROLE_ID ADA
            =====================================
            */

            if (!isset($data['user']['role_id'])) {
                return back()->withErrors([
                    'login' => 'Role user tidak ditemukan'
                ]);
            }

            /*
            =====================================
            SIMPAN SESSION
            =====================================
            */

            session([
                'token' => $data['token'],
                'user' => $data['user'],
                'role_id' => $data['user']['role_id']
            ]);

            /*
            =====================================
            REDIRECT BERDASARKAN ROLE
            =====================================
            */

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

        return back()->withErrors([
            'login' => 'Login gagal, email atau password salah'
        ]);
    }

    public function logout()
{
    session()->forget([
        'token',
        'user',
        'role_id'
    ]);

    session()->flush();

    return redirect('/login')->with(
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
}


