@extends('layouts.guest')

@section('title', 'Daftar Akun')

@section('left-heading')
<h2 class="text-2xl font-bold text-white leading-snug mb-3">
    Bergabung &<br>Mulai Kelola Data
</h2>

<p class="text-sm mb-8" style="color:rgba(255,255,255,.6); line-height:1.7">
    Daftarkan akun Anda untuk mengakses seluruh fitur sistem informasi pertanian daerah.
</p>

<div class="flex flex-col gap-4">

    <div class="flex items-start gap-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
             style="background:rgba(255,255,255,.12)">

            <svg class="w-4 h-4"
                 style="fill:#BBF451"
                 viewBox="0 0 24 24">

                <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/>
            </svg>
        </div>

        <div>
            <p class="text-sm font-semibold text-white">
                Akun Aman & Terverifikasi
            </p>

            <p class="text-xs"
               style="color:rgba(255,255,255,.55)">
                Data dilindungi dengan enkripsi
            </p>
        </div>
    </div>

    <div class="flex items-start gap-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
             style="background:rgba(255,255,255,.12)">

            <svg class="w-4 h-4"
                 style="fill:#BBF451"
                 viewBox="0 0 24 24">

                <path d="M17 12h-5v5h5v-5zM16 1v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-1V1h-2zm3 18H5V8h14v11z"/>
            </svg>
        </div>

        <div>
            <p class="text-sm font-semibold text-white">
                Akses Penuh Fitur
            </p>

            <p class="text-xs"
               style="color:rgba(255,255,255,.55)">
                Semua modul tersedia setelah verifikasi
            </p>
        </div>
    </div>

</div>
@endsection

@section('content')

<h1 class="text-xl font-bold text-gray-800 mb-1">
    Buat Akun Baru
</h1>

<p class="text-sm text-gray-400 mb-6">
    Isi data diri Anda dengan lengkap
</p>

@if ($errors->any())
<div class="flex items-start gap-2 bg-red-50 border border-red-200 text-red-600 px-3 py-2.5 mb-4 rounded-lg text-xs">

    <svg class="w-4 h-4 mt-0.5 shrink-0"
         viewBox="0 0 24 24"
         fill="currentColor">

        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10
        10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
    </svg>

    <ul class="list-disc list-inside space-y-0.5">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form action="{{ route('register') }}"
      method="POST"
      class="space-y-4">

    @csrf

    <div class="space-y-1.5">

        <label class="block text-xs font-medium text-gray-600">
            Nama Lengkap
        </label>

        <div class="relative">

            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">

                <svg class="w-4 h-4"
                     viewBox="0 0 24 24"
                     fill="currentColor">

                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
            </span>

            <input type="text"
                   name="nama_lengkap"
                   value="{{ old('nama_lengkap') }}"
                   placeholder="Nama lengkap Anda"
                   class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent transition placeholder-gray-300 @error('nama_lengkap') border-red-400 @enderror"
                   required>
        </div>
    </div>

    <div class="space-y-1.5">

        <label class="block text-xs font-medium text-gray-600">
            Email
        </label>

        <div class="relative">

            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">

                <svg class="w-4 h-4"
                     viewBox="0 0 24 24"
                     fill="currentColor">

                    <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
            </span>

            <input type="email"
                   name="email"
                   value="{{ old('email') }}"
                   placeholder="nama@email.com"
                   class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent transition placeholder-gray-300 @error('email') border-red-400 @enderror"
                   required>
        </div>
    </div>

    <div class="space-y-1.5">

        <label class="block text-xs font-medium text-gray-600">
            Nomor Handphone
        </label>

        <div class="relative">

            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">

                <svg class="w-4 h-4"
                     viewBox="0 0 24 24"
                     fill="currentColor">

                    <path d="M6.62 10.79a15.053 15.053 0 006.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1C10.07 21 3 13.93 3 5c0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.24.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                </svg>
            </span>

            <input type="text"
                   name="nomor_handphone"
                   value="{{ old('nomor_handphone') }}"
                   placeholder="08xxxxxxxxxx"
                   class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent transition placeholder-gray-300 @error('nomor_handphone') border-red-400 @enderror"
                   required>
        </div>
    </div>

    <div class="space-y-1.5">

        <label class="block text-xs font-medium text-gray-600">
            Alamat
        </label>

        <div class="relative">

            <span class="absolute left-3 top-3 text-gray-400">

                <svg class="w-4 h-4"
                     viewBox="0 0 24 24"
                     fill="currentColor">

                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
            </span>

            <textarea name="alamat"
                      rows="3"
                      placeholder="Masukkan alamat lengkap"
                      class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent transition placeholder-gray-300 @error('alamat') border-red-400 @enderror"
                      required>{{ old('alamat') }}</textarea>
        </div>
    </div>

    <div class="space-y-1.5">

        <label class="block text-xs font-medium text-gray-600">
            Password
        </label>

        <div class="relative">

            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">

                <svg class="w-4 h-4"
                     viewBox="0 0 24 24"
                     fill="currentColor">

                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                </svg>
            </span>

            <input type="password"
                   name="password"
                   placeholder="Min. 8 karakter"
                   class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent transition placeholder-gray-300 @error('password') border-red-400 @enderror"
                   required>
        </div>
    </div>

    <div class="space-y-1.5">

        <label class="block text-xs font-medium text-gray-600">
            Konfirmasi Password
        </label>

        <div class="relative">

            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">

                <svg class="w-4 h-4"
                     viewBox="0 0 24 24"
                     fill="currentColor">

                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/>
                </svg>
            </span>

            <input type="password"
                   name="password_confirmation"
                   placeholder="Ulangi password Anda"
                   class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent transition placeholder-gray-300"
                   required>
        </div>
    </div>

    <button type="submit"
            class="w-full bg-primary-500 hover:bg-primary-600 active:bg-primary-700 text-white font-semibold py-2.5 rounded-lg transition text-sm shadow-sm shadow-primary-200">

        Daftar Sekarang
    </button>

</form>

<p class="text-center text-xs text-gray-400 mt-5">

    Sudah punya akun?

    <a href="{{ route('login') }}"
       class="text-primary-600 font-semibold hover:text-primary-800">

        Masuk di sini
    </a>
</p>

@endsection