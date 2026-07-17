<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\ConnectionException;

class AuthController extends Controller
{
    private const MOBILE_APP_DOWNLOAD_ROLES = [1, 2, 5];

    protected function gatewayUrl(): string
    {
        return rtrim(env('GATEWAY_URL', env('API_GATEWAY_URL', 'http://127.0.0.1:8003')), '/');
    }

    private function redirectByRole(int $roleId)
    {
        return match ($roleId) {
            1, 5 => redirect('/dashboard-petani'),
            2 => redirect('/dashboard-petugas'),
            3 => redirect('/dashboard-pejabat'),
            4 => redirect('/dashboard-admin'),
            default => redirect('/'),
        };
    }

    private function redirectAfterSuccessfulLogin(Request $request, int $roleId)
    {
        if ($request->session()->pull('pending_mobile_app_download', false)) {
            if (in_array($roleId, self::MOBILE_APP_DOWNLOAD_ROLES, true)) {
                return redirect()->route('mobile-app.download');
            }

            return $this->redirectByRole($roleId)
                ->with('error', 'Unduhan aplikasi mobile hanya tersedia untuk Kelompok Tani, Brigade Pangan, dan Petugas.');
        }

        return $this->redirectByRole($roleId);
    }

    private function generateMathCaptcha(Request $request): void
    {
        $angkaPertama = random_int(1, 9);
        $angkaKedua = random_int(1, 9);

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
            'login_id' => 'required|string',
            'password' => 'required|string',
            'math_captcha_answer' => 'required|numeric',
        ], [
            'login_id.required' => 'NIK / NIP wajib diisi.',
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
                    'math_captcha_answer' => 'penjumlahan salah'
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
            $response = Http::withHeaders(['Connection' => 'close'])
                ->withoutVerifying()
                ->timeout(10)
                ->post($this->gatewayUrl() . '/api/login', [
                    'login_id' => $request->login_id,
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

            return $this->redirectAfterSuccessfulLogin($request, (int) $data['user']['role_id']);
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
            
            if (stripos($errorMsg, 'nik atau nip tidak ditemukan') !== false || stripos($errorMsg, 'tidak ditemukan di sistem') !== false) {
                return back()->withErrors(['login_id' => 'NIK/NIP salah'])->withInput($request->except(['password', 'math_captcha_answer']));
            }
            if (stripos($errorMsg, 'password yang anda masukkan salah') !== false || stripos($errorMsg, 'password salah') !== false || stripos($errorMsg, 'salah') !== false) {
                return back()->withErrors(['password' => 'password salah'])->withInput($request->except(['password', 'math_captcha_answer']));
            }
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
        $validator = Validator::make($request->all(), [
            'nik' => 'required|string|size:16',
            'email' => 'required|email',
            'jenis_kelompok' => 'required|in:kelompok_tani,brigade_pangan',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'nik.required' => 'NIK wajib diisi.',
            'nik.size' => 'NIK harus 16 digit.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format Gmail tidak valid.',
            'jenis_kelompok.required' => 'Silakan pilih Kelompok Tani atau Brigade Pangan.',
            'jenis_kelompok.in' => 'Pilihan keanggotaan tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        try {
            $response = Http::withHeaders(['Connection' => 'close'])
                ->withoutVerifying()
                ->timeout(10)
                ->post($this->gatewayUrl() . '/api/register', [
                    'nik' => $request->nik,
                    'email' => $request->email,
                    'jenis_kelompok' => $request->jenis_kelompok,
                    'password' => $request->password,
                    'password_confirmation' => $request->password_confirmation,
                ]);
        } catch (\Throwable $e) {
            return back()
                ->withErrors([
                    'register' => 'Koneksi ke backend terputus: ' . $e->getMessage()
                ])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        if ($response->successful()) {
            $loginResponse = Http::withHeaders(['Connection' => 'close'])
                ->withoutVerifying()
                ->timeout(10)
                ->post($this->gatewayUrl() . '/api/login', [
                    'login_id' => $request->nik,
                    'password' => $request->password,
                ]);

            if ($loginResponse->successful()) {
                $data = $loginResponse->json();

                session([
                    'token' => $data['token'],
                    'user' => $data['user'],
                    'role_id' => $data['user']['role_id'],
                ]);

                return $this->redirectAfterSuccessfulLogin($request, (int) $data['user']['role_id'])
                    ->with('success', 'Registrasi berhasil. Selamat datang di dashboard ' . str_replace('_', ' ', $request->jenis_kelompok) . '.');
            }

            return redirect('/login')->with('success', 'Registrasi berhasil, silakan login.');
        }

        $responseData = $response->json();
        $message = $responseData['message'] ?? 'Gagal melakukan registrasi.';
        $errors = ['register' => $message];

        if (!empty($responseData['errors']) && is_array($responseData['errors'])) {
            $errors = array_merge($errors, $responseData['errors']);
        }

        return back()
            ->withErrors($errors)
            ->withInput($request->except(['password', 'password_confirmation']));
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

        try {
            $response = Http::withHeaders(['Connection' => 'close'])->withoutVerifying()
                ->timeout(15)
                ->acceptJson()
                ->post($this->gatewayUrl() . '/api/forgot-password', [
                    'email' => $request->email
                ]);
        } catch (\Throwable $e) {
            return back()->withErrors([
                'email' => 'Layanan reset password belum dapat dihubungi. Silakan coba lagi beberapa saat.',
            ])->withInput($request->only('email'));
        }

        if ($response->successful()) {
            return back()->with('status', 'Link reset password sudah dikirim ke email jika akun terdaftar.');
        }

        // Ambil pesan error asli dari API/Gateway jika ada
        $errorMsg = 'Email tidak ditemukan';
        $responseData = $response->json();
        if (isset($responseData['message'])) {
            $errorMsg = $responseData['message'];
        }

        return back()->withErrors([
            'email' => $errorMsg
        ])->withInput($request->only('email'));
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

        try {
            $response = Http::withHeaders(['Connection' => 'close'])->withoutVerifying()
                ->timeout(15)
                ->acceptJson()
                ->post($this->gatewayUrl() . '/api/reset-password', $request->all());
        } catch (\Throwable $e) {
            return back()->withErrors([
                'reset' => 'Layanan reset password belum dapat dihubungi. Silakan coba lagi beberapa saat.',
            ])->withInput($request->except(['password', 'password_confirmation']));
        }

        if ($response->successful()) {
            return redirect('/login')->with('success', 'Password berhasil direset');
        }

        $errorMsg = 'Reset password gagal. Silakan coba lagi.';
        $responseData = $response->json();

        if (isset($responseData['errors']) && is_array($responseData['errors'])) {
            return back()->withErrors($responseData['errors'])->withInput($request->except(['password', 'password_confirmation']));
        }

        if (isset($responseData['message'])) {
            $errorMsg = $responseData['message'];
        }

        return back()->withErrors([
            'reset' => $errorMsg
        ])->withInput($request->except(['password', 'password_confirmation']));
    }

    public function profile()
    {
        $token = session('token');
        if (!$token) {
            return redirect('/login')->with('error', 'Session login habis, silakan login kembali.');
        }

        try {
            $response = Http::withHeaders(['Connection' => 'close'])->withToken($token)
                ->acceptJson()
                ->withoutVerifying()
                ->timeout(15)
                ->get($this->gatewayUrl() . '/api/auth/profile');
        } catch (\Throwable $e) {
            $response = null;
        }

        $user = $response?->successful()
            ? ($response->json('user') ?? session('user', []))
            : session('user', []);

        $kecamatan = [];
        $kelurahan = [];
        
        if ((int) ($user['role_id'] ?? session('role_id')) === 2) {
            try {
                $kecamatan = Http::get($this->gatewayUrl() . '/api/kecamatan')->json()['data'] ?? [];
                $kelurahan = Http::get($this->gatewayUrl() . '/api/kelurahan')->json()['data'] ?? [];
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return view('auth.profile', [
            'user' => $user,
            'kecamatan' => $kecamatan,
            'kelurahan' => $kelurahan
        ]);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:150',
            'email' => 'required|email',
            'no_hp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'wilayah_kecamatan_id' => 'nullable|integer',
            'wilayah_kelurahan_id' => 'nullable|integer',
        ]);

        $token = session('token');
        if (!$token) {
            return redirect('/login')->with('error', 'Session login habis, silakan login kembali.');
        }

        try {
            $response = Http::withHeaders(['Connection' => 'close'])->withToken($token)
                ->acceptJson()
                ->withoutVerifying()
                ->timeout(15)
                ->put($this->gatewayUrl() . '/api/auth/profile', $validated);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['profile' => 'Layanan profil belum dapat dihubungi. Silakan coba lagi beberapa saat.']);
        }

        if ($response->successful()) {
            $user = $response->json('user') ?? [];
            session([
                'user' => $user,
                'role_id' => $user['role_id'] ?? session('role_id'),
            ]);

            return back()->with('success', 'Profil berhasil diperbarui.');
        }

        $responseData = $response->json();
        if (isset($responseData['errors']) && is_array($responseData['errors'])) {
            return back()->withInput()->withErrors($responseData['errors']);
        }

        return back()
            ->withInput()
            ->withErrors(['profile' => $responseData['message'] ?? 'Profil gagal diperbarui.']);
    }
}
