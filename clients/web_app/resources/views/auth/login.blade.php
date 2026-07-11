@extends('layouts.guest')

@section('title', 'Login')
@section('page-heading', 'Masuk Akun')
@section('content')

<div class="flex items-center justify-between mb-6">
    <a href="/"
       class="inline-flex items-center gap-2 text-sm font-semibold text-[#047857] hover:text-[#2f5c12] transition">

        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
        </svg>

        Kembali ke Dashboard Publik
    </a>
</div>

@if (session('success'))
<div class="flex items-start gap-3 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 mb-5 rounded-2xl text-sm">
    <svg class="w-5 h-5 mt-0.5 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
    </svg>
    <span>{{ session('success') }}</span>
</div>
@endif

@if ($errors->has('login'))
<div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-600 px-4 py-3 mb-5 rounded-2xl text-sm">
    <svg class="w-5 h-5 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
    </svg>
    <span>{{ $errors->first('login') }}</span>
</div>
@endif

<form action="/login" method="POST" class="space-y-5">
    @csrf

    {{-- Email --}}
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">
            Email
        </label>

        <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
            </span>

            <input type="email"
                   name="email"
                   value="{{ old('email') }}"
                   placeholder="nama@email.com"
                   class="auth-input w-full pl-12 pr-4 py-3.5 border border-slate-200 rounded-2xl text-sm bg-white outline-none transition placeholder-slate-300 @error('email') border-red-400 @enderror"
                   required>
        </div>

        @error('email')
            <p class="text-xs text-red-500 mt-2">{{ $message }}</p>
        @enderror
    </div>

    {{-- Password --}}
    <div>
        <div class="flex items-center justify-between mb-2">
            <label class="block text-sm font-semibold text-slate-700">
                Password
            </label>

            <a href="{{ route('password.request') }}"
               class="text-xs font-semibold hover:underline"
               style="color:#497D00;">
                Lupa password?
            </a>
        </div>

        <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-3 0H9V6c0-1.66 1.34-3 3-3s3 1.34 3 3v2z"/>
                </svg>
            </span>

            <input id="loginPassword"
                   type="password"
                   name="password"
                   placeholder="Masukkan password"
                   class="auth-input w-full pl-12 pr-12 py-3.5 border border-slate-200 rounded-2xl text-sm bg-white outline-none transition placeholder-slate-300 @error('password') border-red-400 @enderror"
                   required>

            <button type="button"
                    onclick="togglePassword('loginPassword', 'loginEyeOpen', 'loginEyeClosed')"
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 transition"
                    aria-label="Lihat password">

                <svg id="loginEyeOpen" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zm0 12.5a5 5 0 110-10 5 5 0 010 10zm0-2a3 3 0 100-6 3 3 0 000 6z"/>
                </svg>

                <svg id="loginEyeClosed" class="w-5 h-5 hidden" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M2.1 3.51L3.51 2.1 21.9 20.49l-1.41 1.41-3.04-3.04A12.7 12.7 0 0112 19.5C7 19.5 2.73 16.39 1 12a13.7 13.7 0 013.11-4.56L2.1 3.51zM12 4.5c5 0 9.27 3.11 11 7.5a13.4 13.4 0 01-3.02 4.46l-3.12-3.12A5 5 0 0010.66 7.14L8.35 4.83A12.8 12.8 0 0112 4.5z"/>
                </svg>
            </button>
        </div>

        @error('password')
            <p class="text-xs text-red-500 mt-2">{{ $message }}</p>
        @enderror
    </div>

    {{-- Captcha Penjumlahan --}}
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">
            Verifikasi Keamanan
        </label>

        <div class="rounded-2xl border border-[#dfeccc] bg-[#f7fced] px-4 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="flex items-center gap-3 flex-1">
                    <div class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0"
                         style="background:linear-gradient(135deg,#10b981,#047857); color:white;">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-8 14H9v-4H5v-2h4V7h2v4h4v2h-4v4zm8-2h-3v-2h3v2zm0-4h-3V9h3v2z"/>
                        </svg>
                    </div>

                    <div>
                        <p class="text-xs text-slate-500 font-medium">
                            Jawab pertanyaan berikut:
                        </p>
                        <p class="text-lg font-extrabold text-[#14280b] tracking-wide">
                            {{ session('math_captcha_question', '0 + 0') }} = ?
                        </p>
                    </div>
                </div>

                <input type="number"
                       name="math_captcha_answer"
                       value=""
                       autocomplete="off"
                       class="auth-input w-full sm:w-32 px-4 py-3 border border-slate-200 rounded-2xl text-sm bg-white outline-none transition placeholder-slate-300 text-center font-bold @error('math_captcha_answer') border-red-400 @enderror"
                       required>
            </div>
       </div>

        @error('math_captcha_answer')
            <p class="text-xs text-red-500 mt-2">{{ $message }}</p>
        @enderror
    </div>

    {{-- Submit --}}
    <button type="submit"
            class="w-full text-white font-bold py-3.5 rounded-2xl transition text-sm"
            style="background: linear-gradient(135deg, #10b981, #047857); box-shadow: 0 14px 30px rgba(94,165,0,.25);">
        Masuk
    </button>
</form>

<p class="text-center text-sm text-slate-500 mt-6">
    Belum punya akun?
    <a href="{{ route('register') }}"
       class="font-bold hover:underline"
       style="color:#497D00;">
        Daftar sekarang
    </a>
</p>

@push('scripts')
<script>
    function togglePassword(inputId, eyeOpenId, eyeClosedId) {
        const input = document.getElementById(inputId);
        const eyeOpen = document.getElementById(eyeOpenId);
        const eyeClosed = document.getElementById(eyeClosedId);

        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';

        eyeOpen.classList.toggle('hidden', isPassword);
        eyeClosed.classList.toggle('hidden', !isPassword);
    }
</script>
@endpush

@endsection