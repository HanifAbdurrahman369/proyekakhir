@extends('layouts.guest')

@section('title', 'Reset Password')

@section('left-heading')
<h2 class="text-2xl font-bold text-white leading-snug mb-3">
    Reset Password<br>Akun Anda
</h2>

<p class="text-sm mb-8" style="color:rgba(255,255,255,.6); line-height:1.7">
    Masukkan password baru untuk akun Anda. Gunakan kombinasi password yang kuat agar akun tetap aman.
</p>

<div class="flex flex-col gap-4">

    <div class="flex items-start gap-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
             style="background:rgba(255,255,255,.12)">

            <svg class="w-4 h-4"
                 style="fill:#BBF451"
                 viewBox="0 0 24 24">

                <path d="M12 17a2 2 0 002-2v-3a2 2 0 10-4 0v3a2 2 0 002 2zm6-9h-1V6a5 5 0 00-10 0v2H6a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V10a2 2 0 00-2-2zm-8-2a3 3 0 016 0v2H10V6z"/>
            </svg>
        </div>

        <div>
            <p class="text-sm font-semibold text-white">
                Password Aman
            </p>

            <p class="text-xs"
               style="color:rgba(255,255,255,.55)">
                Gunakan kombinasi huruf, angka, dan simbol
            </p>
        </div>
    </div>

    <div class="flex items-start gap-3">
        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
             style="background:rgba(255,255,255,.12)">

            <svg class="w-4 h-4"
                 style="fill:#BBF451"
                 viewBox="0 0 24 24">

                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10
                10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8
                s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
            </svg>
        </div>

        <div>
            <p class="text-sm font-semibold text-white">
                Keamanan Akun
            </p>

            <p class="text-xs"
               style="color:rgba(255,255,255,.55)">
                Jangan bagikan password kepada siapa pun
            </p>
        </div>
    </div>

</div>
@endsection

@section('content')

<h1 class="text-xl font-bold text-gray-800 mb-1">
    Buat Password Baru
</h1>

<p class="text-sm text-gray-400 mb-6">
    Silakan masukkan password baru Anda
</p>

@if ($errors->has('reset'))
<div class="flex items-start gap-2 bg-red-50 border border-red-200 text-red-600 px-3 py-2.5 mb-4 rounded-lg text-xs">

    <svg class="w-4 h-4 mt-0.5 shrink-0"
         viewBox="0 0 24 24"
         fill="currentColor">

        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10
        10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
    </svg>

    <span>{{ $errors->first('reset') }}</span>
</div>
@endif

<form action="{{ route('password.update') }}"
      method="POST"
      class="space-y-4">

    @csrf

    <input type="hidden"
           name="token"
           value="{{ $token }}">

    <div class="space-y-1.5">

        <label class="block text-xs font-medium text-gray-600">
            Alamat Email
        </label>

        <div class="relative">

            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">

                <svg class="w-4 h-4"
                     viewBox="0 0 24 24"
                     fill="currentColor">

                    <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16
                    c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
            </span>

            <input type="email"
                   name="email"
                   value="{{ request()->email }}"
                   {{ request()->email ? 'readonly' : '' }}
                   class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm {{ request()->email ? 'bg-gray-100' : 'bg-white focus:ring-2 focus:ring-primary-400 focus:border-transparent transition' }} focus:outline-none"
                   required>
        </div>
    </div>

    <div class="space-y-1.5">

        <label class="block text-xs font-medium text-gray-600">
            Password Baru
        </label>

        <div class="relative">

            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">

                <svg class="w-4 h-4"
                     viewBox="0 0 24 24"
                     fill="currentColor">

                    <path d="M12 17a2 2 0 002-2v-3a2 2 0 10-4 0v3a2 2 0 002 2zm6-9h-1V6a5 5 0 00-10 0v2H6a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V10a2 2 0 00-2-2zm-8-2a3 3 0 016 0v2H10V6z"/>
                </svg>
            </span>

            <input type="password"
                   name="password"
                   placeholder="Masukkan password baru"
                   class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent transition"
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

                    <path d="M12 17a2 2 0 002-2v-3a2 2 0 10-4 0v3a2 2 0 002 2zm6-9h-1V6a5 5 0 00-10 0v2H6a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V10a2 2 0 00-2-2zm-8-2a3 3 0 016 0v2H10V6z"/>
                </svg>
            </span>

            <input type="password"
                   name="password_confirmation"
                   placeholder="Konfirmasi password baru"
                   class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-primary-400 focus:border-transparent transition"
                   required>
        </div>
    </div>

    <button type="submit"
            class="w-full bg-primary-500 hover:bg-primary-600 active:bg-primary-700 text-white font-semibold py-2.5 rounded-lg transition text-sm shadow-sm shadow-primary-200">

        Reset Password
    </button>
</form>

<div class="flex items-center justify-center gap-1.5 mt-5">

    <a href="{{ route('login') }}"
       class="inline-flex items-center gap-1 text-xs text-primary-600 hover:text-primary-800 font-medium transition">

        <svg class="w-3.5 h-3.5"
             viewBox="0 0 24 24"
             fill="currentColor">

            <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8
            1.41-1.41L7.83 13H20v-2z"/>
        </svg>

        Kembali ke halaman login
    </a>
</div>

<!-- Mobile Redirect Modal Overlay -->
<div id="mobile-redirect-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900 bg-opacity-80 backdrop-blur-sm">
    <div class="bg-white rounded-2xl p-6 max-w-sm w-full shadow-2xl border border-gray-100 flex flex-col items-center text-center">
        <!-- Icon Phone with Animation -->
        <div class="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center text-green-600 mb-4 animate-bounce">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
            </svg>
        </div>
        
        <h3 class="text-lg font-bold text-gray-800 mb-2">Buka di Aplikasi SiTani</h3>
        <p class="text-xs text-gray-500 mb-6 leading-relaxed">
            Sistem mendeteksi Anda menggunakan perangkat mobile. Buka aplikasi untuk mengatur ulang password secara langsung dengan lebih mudah dan aman.
        </p>
        
        <!-- Button Buka Aplikasi -->
        <a href="sitani://reset-password/{{ $token }}?email={{ urlencode(request()->email) }}" 
           class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-xl text-sm transition mb-3 shadow-md shadow-green-100 block text-center">
            Buka Aplikasi Mobile
        </a>
        
        <!-- Button Tetap di Browser -->
        <button type="button" onclick="dismissMobileModal()" 
                class="w-full bg-gray-50 hover:bg-gray-100 text-gray-600 font-semibold py-2.5 px-4 rounded-xl text-xs transition border border-gray-200">
            Tetap di Browser Web
        </button>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        if (isMobile) {
            // Tampilkan modal
            var modal = document.getElementById("mobile-redirect-modal");
            if (modal) {
                modal.classList.remove("hidden");
            }
            
            // Coba redirect otomatis setelah 800ms
            setTimeout(function() {
                window.location.href = "sitani://reset-password/{{ $token }}?email={{ urlencode(request()->email) }}";
            }, 800);
        }
    });

    function dismissMobileModal() {
        var modal = document.getElementById("mobile-redirect-modal");
        if (modal) {
            modal.classList.add("hidden");
        }
    }
</script>

@endsection
