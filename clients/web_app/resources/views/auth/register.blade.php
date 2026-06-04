@extends('layouts.guest')

@section('title', 'Daftar Akun')
@section('page-heading', 'Buat Akun Baru')
@section('content')

<div class="flex items-center justify-between mb-6">
    <a href="/"
       class="inline-flex items-center gap-2 text-sm font-semibold text-[#3E7D00] hover:text-[#2f5c12] transition">

        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
            <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
        </svg>

        Kembali ke Dashboard Publik
    </a>
</div>

@if ($errors->any())
<div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-600 px-4 py-3 mb-5 rounded-2xl text-sm">
    <svg class="w-5 h-5 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
    </svg>

    <ul class="list-disc list-inside space-y-1">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('register') }}" method="POST" class="space-y-4">
    @csrf

    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Nama Lengkap</label>
        <input type="text"
               name="nama_lengkap"
               value="{{ old('nama_lengkap') }}"
               placeholder="Nama lengkap Anda"
               class="auth-input w-full px-4 py-3.5 border border-slate-200 rounded-2xl text-sm bg-white outline-none transition placeholder-slate-300 @error('nama_lengkap') border-red-400 @enderror"
               required>
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Email</label>
        <input type="email"
               name="email"
               value="{{ old('email') }}"
               placeholder="nama@email.com"
               class="auth-input w-full px-4 py-3.5 border border-slate-200 rounded-2xl text-sm bg-white outline-none transition placeholder-slate-300 @error('email') border-red-400 @enderror"
               required>
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Nomor Handphone</label>
        <input type="text"
               name="nomor_handphone"
               value="{{ old('nomor_handphone') }}"
               placeholder="08xxxxxxxxxx"
               class="auth-input w-full px-4 py-3.5 border border-slate-200 rounded-2xl text-sm bg-white outline-none transition placeholder-slate-300 @error('nomor_handphone') border-red-400 @enderror"
               required>
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Alamat</label>
        <textarea name="alamat"
                  rows="3"
                  placeholder="Masukkan alamat lengkap"
                  class="auth-input w-full px-4 py-3.5 border border-slate-200 rounded-2xl text-sm bg-white outline-none transition placeholder-slate-300 resize-none @error('alamat') border-red-400 @enderror"
                  required>{{ old('alamat') }}</textarea>
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
        <div class="relative">
            <input id="registerPassword"
                   type="password"
                   name="password"
                   placeholder="Min. 8 karakter"
                   class="auth-input w-full px-4 pr-12 py-3.5 border border-slate-200 rounded-2xl text-sm bg-white outline-none transition placeholder-slate-300 @error('password') border-red-400 @enderror"
                   required>

            <button type="button"
                    onclick="togglePassword('registerPassword', 'registerEyeOpen', 'registerEyeClosed')"
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 transition"
                    aria-label="Lihat password">
                <svg id="registerEyeOpen" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zm0 12.5a5 5 0 110-10 5 5 0 010 10zm0-2a3 3 0 100-6 3 3 0 000 6z"/>
                </svg>
                <svg id="registerEyeClosed" class="w-5 h-5 hidden" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M2.1 3.51L3.51 2.1 21.9 20.49l-1.41 1.41-3.04-3.04A12.7 12.7 0 0112 19.5C7 19.5 2.73 16.39 1 12a13.7 13.7 0 013.11-4.56L2.1 3.51zM12 4.5c5 0 9.27 3.11 11 7.5a13.4 13.4 0 01-3.02 4.46l-3.12-3.12A5 5 0 0010.66 7.14L8.35 4.83A12.8 12.8 0 0112 4.5z"/>
                </svg>
            </button>
        </div>
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-2">Konfirmasi Password</label>
        <div class="relative">
            <input id="registerPasswordConfirmation"
                   type="password"
                   name="password_confirmation"
                   placeholder="Ulangi password Anda"
                   class="auth-input w-full px-4 pr-12 py-3.5 border border-slate-200 rounded-2xl text-sm bg-white outline-none transition placeholder-slate-300"
                   required>

            <button type="button"
                    onclick="togglePassword('registerPasswordConfirmation', 'registerConfirmEyeOpen', 'registerConfirmEyeClosed')"
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 transition"
                    aria-label="Lihat konfirmasi password">
                <svg id="registerConfirmEyeOpen" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zm0 12.5a5 5 0 110-10 5 5 0 010 10zm0-2a3 3 0 100-6 3 3 0 000 6z"/>
                </svg>
                <svg id="registerConfirmEyeClosed" class="w-5 h-5 hidden" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M2.1 3.51L3.51 2.1 21.9 20.49l-1.41 1.41-3.04-3.04A12.7 12.7 0 0112 19.5C7 19.5 2.73 16.39 1 12a13.7 13.7 0 013.11-4.56L2.1 3.51zM12 4.5c5 0 9.27 3.11 11 7.5a13.4 13.4 0 01-3.02 4.46l-3.12-3.12A5 5 0 0010.66 7.14L8.35 4.83A12.8 12.8 0 0112 4.5z"/>
                </svg>
            </button>
        </div>
    </div>

    <button type="submit"
            class="w-full text-white font-bold py-3.5 rounded-2xl transition text-sm mt-2"
            style="background: linear-gradient(135deg, #5EA500, #3E7D00); box-shadow: 0 14px 30px rgba(94,165,0,.25);">
        Daftar Sekarang
    </button>
</form>

<p class="text-center text-sm text-slate-500 mt-6">
    Sudah punya akun?
    <a href="{{ route('login') }}" class="font-bold hover:underline" style="color:#497D00;">
        Masuk di sini
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