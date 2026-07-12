@extends('layouts.guest')

@section('title', 'Lupa Password')

@section('left-heading')
<h2 class="text-2xl font-bold text-white leading-snug mb-3">
    Lupa Password?<br>Tenang, Kami Bantu
</h2>
<p class="text-sm mb-8" style="color:rgba(255,255,255,.6); line-height:1.7">
    Masukkan email terdaftar Anda dan kami akan mengirimkan tautan untuk mereset password dengan aman.
</p>
<div class="flex flex-col gap-4">
    <div class="flex items-start gap-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:rgba(255,255,255,.12)">
            <svg class="w-4 h-4" style="fill:#BBF451" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-white">Cek Kotak Masuk Email</p>
            <p class="text-xs" style="color:rgba(255,255,255,.55)">Link reset dikirim dalam hitungan menit</p>
        </div>
    </div>
    <div class="flex items-start gap-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:rgba(255,255,255,.12)">
            <svg class="w-4 h-4" style="fill:#BBF451" viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z"/></svg>
        </div>
        <div>
            <p class="text-sm font-semibold text-white">Link Berlaku 60 Menit</p>
            <p class="text-xs" style="color:rgba(255,255,255,.55)">Segera reset sebelum kadaluwarsa</p>
        </div>
    </div>
</div>
@endsection

@section('content')

<h1 class="text-xl font-bold text-gray-800 mb-1">Reset Password</h1>
<p class="text-sm text-gray-400 mb-6">Masukkan email akun Anda</p>

@if (session('status'))
<div class="flex items-start gap-2 bg-primary-50 border border-primary-200 text-primary-800 px-3 py-2.5 mb-4 rounded-lg text-xs">
    <svg class="w-4 h-4 mt-0.5 shrink-0 text-primary-500" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
    <span>{{ session('status') }}</span>
</div>
@endif

@if ($errors->has('email'))
<div class="flex items-start gap-2 bg-red-50 border border-red-200 text-red-600 px-3 py-2.5 mb-4 rounded-lg text-xs">
    <svg class="w-4 h-4 mt-0.5 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
    <span>{{ $errors->first('email') }}</span>
</div>
@endif

<p class="text-xs text-gray-400 leading-relaxed mb-5">
    Kami akan mengirimkan email berisi tautan untuk mereset password Anda. Pastikan email yang dimasukkan sudah terdaftar di sistem.
</p>

<form action="{{ route('password.email') }}" method="POST" class="space-y-4">
    @csrf

    <div class="space-y-1.5">
        <label class="block text-xs font-medium text-gray-600">Alamat Email</label>
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
            </span>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com"
                class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent transition placeholder-gray-300 @error('email') border-red-400 @enderror"
                required autofocus>
        </div>
    </div>

    <button type="submit"
        class="w-full bg-primary-500 hover:bg-primary-600 active:bg-primary-700 text-white font-semibold py-2.5 rounded-lg transition text-sm shadow-sm shadow-primary-200">
        Kirim Tautan Reset Password
    </button>
</form>

<div class="flex items-center justify-center gap-1.5 mt-5">
    <a href="{{ route('login') }}" class="inline-flex items-center gap-1 text-xs text-primary-600 hover:text-primary-800 font-medium transition">
        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
        Kembali ke halaman login
    </a>
</div>

@endsection