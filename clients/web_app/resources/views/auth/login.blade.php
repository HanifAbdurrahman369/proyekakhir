@extends('layouts.guest')

@section('title', 'Login')

@section('left-heading')
<h2 class="text-2xl font-bold text-white leading-snug mb-3">
    Kelola Data Pertanian<br>Lebih Mudah
</h2>
<p class="text-sm mb-8" style="color:rgba(255,255,255,.6); line-height:1.7">
    Platform digital terpadu untuk mendukung produktivitas dan pengawasan sektor pertanian daerah.
</p>
<div class="flex flex-col gap-4">
    <div class="flex items-start gap-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:rgba(255,255,255,.12)">
            <svg class="w-4 h-4" style="fill:#BBF451" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 3c1.93 0 3.5 1.57 3.5 3.5S13.93 13 12 13s-3.5-1.57-3.5-3.5S10.07 6 12 6zm7 13H5v-.23c0-.62.28-1.2.76-1.58C7.47 15.82 9.64 15 12 15s4.53.82 6.24 2.19c.48.38.76.97.76 1.58V19z"/></svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-white">Data Petani Terintegrasi</p>
            <p class="text-xs" style="color:rgba(255,255,255,.55)">Kelola profil & lahan dengan mudah</p>
        </div>
    </div>
    <div class="flex items-start gap-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:rgba(255,255,255,.12)">
            <svg class="w-4 h-4" style="fill:#BBF451" viewBox="0 0 24 24"><path d="M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z"/></svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-white">Monitoring Produksi</p>
            <p class="text-xs" style="color:rgba(255,255,255,.55)">Pantau hasil panen secara real-time</p>
        </div>
    </div>
    <div class="flex items-start gap-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:rgba(255,255,255,.12)">
            <svg class="w-4 h-4" style="fill:#BBF451" viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-white">Laporan & Analitik</p>
            <p class="text-xs" style="color:rgba(255,255,255,.55)">Ekspor data dalam berbagai format</p>
        </div>
    </div>
</div>
@endsection

@section('content')

<h1 class="text-xl font-bold text-gray-800 mb-1">Masuk Akun</h1>
<p class="text-sm text-gray-400 mb-6">Selamat datang kembali 👋</p>

@if ($errors->has('login'))
<div class="flex items-start gap-2 bg-red-50 border border-red-200 text-red-600 px-3 py-2.5 mb-4 rounded-lg text-xs">
    <svg class="w-4 h-4 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
    <span>{{ $errors->first('login') }}</span>
</div>
@endif

<form action="/login" method="POST" class="space-y-4">
    @csrf

    <div class="space-y-1.5">
        <label class="block text-xs font-medium text-gray-600">Email</label>
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
            </span>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com"
                class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent transition placeholder-gray-300 @error('email') border-red-400 @enderror"
                required>
        </div>
    </div>

    <div class="space-y-1.5">
        <div class="flex items-center justify-between">
            <label class="block text-xs font-medium text-gray-600">Password</label>
            <a href="{{ route('password.request') }}" class="text-xs text-primary-600 hover:text-primary-800 font-medium">Lupa password?</a>
        </div>
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
            </span>
            <input type="password" name="password" placeholder="Masukkan password"
                class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent transition placeholder-gray-300"
                required>
        </div>
    </div>

    <div class="flex justify-center pt-1">
        <div class="g-recaptcha" data-sitekey="{{ env('CAPTCHA_SITE_KEY') }}"></div>
    </div>

    <button type="submit"
        class="w-full bg-primary-500 hover:bg-primary-600 active:bg-primary-700 text-white font-semibold py-2.5 rounded-lg transition text-sm shadow-sm shadow-primary-200">
        Masuk
    </button>
</form>

<p class="text-center text-xs text-gray-400 mt-5">
    Belum punya akun?
    <a href="{{ route('register') }}" class="text-primary-600 font-semibold hover:text-primary-800">Daftar sekarang</a>
</p>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>

@endsection