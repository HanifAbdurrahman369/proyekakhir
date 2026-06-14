<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\ConnectionException;

class AuthController extends Controller
{
    protected function gatewayUrl(): string
    {
        return env('GATEWAY_URL', 'http://127.0.0.1:8003');
    }

    private function generateMathCaptcha(Request $request): void
    {
        $angkaPertama = random_int(1, 20);
        $angkaKedua = random_int(1, 20);

        $request->session()->put('math_captcha_question', "{$angkaPertama} + {$angkaKedua}");
        $request->session()->put('math_captcha_answer', $angkaPertama + $angkaKedua);
    }

    public function showLogin(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Captcha selalu diperbarui ketika halaman login dibuka / refresh
        |--------------------------------------------------------------------------
        */
        $this->generateMathCaptcha($request);

        return view('auth.login');
    }

    public function login(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validasi input dasar login
        |--------------------------------------------------------------------------
        */
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'math_captcha_answer' => 'required|numeric',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'math_captcha_answer.required' => 'Jawaban verifikasi penjumlahan wajib diisi.',
            'math_captcha_answer.numeric' => 'Jawaban verifikasi harus berupa angka.',
        ]);

        if ($validator->fails()) {
            $this->generateMathCaptcha($request);

            return back()
                ->withErrors($validator)
                ->withInput($request->except(['password', 'math_captcha_answer']));
        }

        /*
        |--------------------------------------------------------------------------
        | Validasi captcha penjumlahan
        |--------------------------------------------------------------------------
        | Jika salah, captcha langsung diganti otomatis.
        |--------------------------------------------------------------------------
        */
        $jawabanBenar = (int) $request->session()->get('math_captcha_answer');
        $jawabanUser = (int) $request->input('math_captcha_answer');

        if ($jawabanUser !== $jawabanBenar) {
            $this->generateMathCaptcha($request);

            return back()
                ->withErrors([
                    'login' => 'Jawaban verifikasi penjumlahan salah. Silakan jawab pertanyaan baru.'
                ])
                ->withInput($request->except(['password', 'math_captcha_answer']));
        }


        /*
        |--------------------------------------------------------------------------
        | Kirim login ke backend melalui API Gateway
        |--------------------------------------------------------------------------
        | Tidak lagi mengirim g-recaptcha-response.
        |--------------------------------------------------------------------------
        */
        try {
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->post($this->gatewayUrl() . '/api/login', [
                    'email' => $request->email,
                    'password' => $request->password,
                ]);

        } catch (\Throwable $e) {
            $this->generateMathCaptcha($request);

            return back()
                ->withErrors([
                    'login' => 'Koneksi ke backend terputus: ' . $e->getMessage()
                ])
                ->withInput($request->except(['password', 'math_captcha_answer']));
        }

        /*
        |--------------------------------------------------------------------------
        | Jika login berhasil
        |--------------------------------------------------------------------------
        */
        if ($response->successful()) {
            $data = $response->json();

            if (!isset($data['user']['role_id'])) {
                $this->generateMathCaptcha($request);

                return back()
                    ->withErrors([
                        'login' => 'Role user tidak terdeteksi di sistem.'
                    ])
                    ->withInput($request->except(['password', 'math_captcha_answer']));
            }

            session([
                'token' => $data['token'],
                'user' => $data['user'],
                'role_id' => $data['user']['role_id'],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Captcha dihapus setelah login berhasil
            |--------------------------------------------------------------------------
            */
            $request->session()->forget([
                'math_captcha_question',
                'math_captcha_answer',
            ]);

            switch ((int) $data['user']['role_id']) {
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

        /*
        |--------------------------------------------------------------------------
        | Jika email salah, password salah, atau backend menolak login
        | Captcha wajib diganti otomatis.
        |--------------------------------------------------------------------------
        */
        $this->generateMathCaptcha($request);

        $responseData = $response->json();
        $errorMsg = 'Koneksi ke backend terputus.';

        if (isset($responseData['message'])) {
            $errorMsg = $responseData['message'];
        } elseif ($response->serverError()) {
            $errorMsg = 'Terjadi kesalahan fatal di Auth Service.';
        }

        return back()
            ->withErrors([
                'login' => $errorMsg
            ])
            ->withInput($request->except(['password', 'math_captcha_answer']));
    }

    public function logout()
    {
        session()->forget([
            'token',
            'user',
            'role_id',
            'math_captcha_question',
            'math_captcha_answer',
        ]);

        session()->flush();

        return redirect('/')->with('success', 'Logout berhasil');
    }

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

    public function forgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $response = Http::withoutVerifying()
            ->post($this->gatewayUrl() . '/api/forgot-password', [
                'email' => $request->email
            ]);

        if ($response->successful()) {
            return back()->with('status', 'Link reset password dikirim ke email');
        }

        // Ambil pesan error asli dari API/Gateway jika ada
        $errorMsg = 'Email tidak ditemukan';
        $responseData = $response->json();
        if (isset($responseData['message'])) {
            $errorMsg = $responseData['message'];
        }

        return back()->withErrors([
            'email' => $errorMsg
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed'
        ], [
            'password.min' => 'Password minimal harus 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.'
        ]);

        $response = Http::withoutVerifying()
            ->post($this->gatewayUrl() . '/api/forget-password', $request->all());

        if ($response->successful()) {
            return redirect('/login')->with('success', 'Password berhasil direset');
        }

        $errorMsg = 'Reset password gagal. Silakan coba lagi.';
        $responseData = $response->json();

        if (isset($responseData['errors']) && is_array($responseData['errors'])) {
            return back()->withErrors($responseData['errors'])->withInput($request->except('password'));
        }

        if (isset($responseData['message'])) {
            $errorMsg = $responseData['message'];
        }

        return back()->withErrors([
            'reset' => $errorMsg
        ])->withInput($request->except('password'));
    }
}