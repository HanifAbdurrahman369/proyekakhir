<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    protected function gatewayUrl(): string
    {
        return env('GATEWAY_URL', 'http://127.0.0.1:8005');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'g-recaptcha-response' => 'required'
        ], [
            'g-recaptcha-response.required' => 'Mohon centang verifikasi reCAPTCHA.'
        ]);

        $response = Http::withoutVerifying()->post(
            $this->gatewayUrl() . '/api/login',
            [
                'email' => $request->email,
                'password' => $request->password,
                'g-recaptcha-response' => $request->input('g-recaptcha-response')
            ]
        );

        if ($response->successful()) {
            $data = $response->json();

            if (!isset($data['user']['role_id'])) {
                return back()->withErrors(['login' => 'Role user tidak terdeteksi di sistem'])->withInput();
            }

            session([
                'token' => $data['token'],
                'user' => $data['user'],
                'role_id' => $data['user']['role_id']
            ]);

            switch ($data['user']['role_id']) {
                case 1: return redirect('/dashboard-petani');
                case 2: return redirect('/dashboard-petugas');
                case 3: return redirect('/dashboard-pejabat');
                case 4: return redirect('/dashboard-admin');
                default: return redirect('/');
            }
        }

        // TRANSPARENT ERROR CATCHING (Menangkap pesan asli dari backend)
        $responseData = $response->json();
        $errorMsg = 'Koneksi ke backend terputus.'; // Default

        if (isset($responseData['message'])) {
            $errorMsg = $responseData['message']; // Mengambil pesan "Password salah", "Email salah", dll
        } elseif ($response->serverError()) {
            $errorMsg = 'Terjadi kesalahan fatal (Error 500) di Auth Service.';
        }

        return back()->withErrors([
            'login' => $errorMsg
        ])->withInput();
    }

    public function logout()
    {
        session()->forget(['token', 'user', 'role_id']);
        session()->flush();
        return redirect('/')->with('success', 'Logout berhasil');
    }

    // Fitur registrasi dan reset password tetap menggunakan format bypass
    public function register(Request $request)
    {
        $response = Http::post($this->gatewayUrl() . '/api/register', [
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

    public function forgotPassword() { return view('auth.forgot-password'); }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $response = Http::withoutVerifying()->post($this->gatewayUrl() . '/api/forgot-password', ['email' => $request->email]);
        if ($response->successful()) return back()->with('status', 'Link reset password dikirim ke email');
        return back()->withErrors(['email' => 'Email tidak ditemukan']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate(['token' => 'required', 'email' => 'required|email', 'password' => 'required|min:6|confirmed']);
        $response = Http::withoutVerifying()->post($this->gatewayUrl() . '/api/forget-password', $request->all());
        if ($response->successful()) return redirect('/login')->with('success', 'Password berhasil direset');
        return back()->withErrors(['reset' => 'Reset password gagal']);
    }
}