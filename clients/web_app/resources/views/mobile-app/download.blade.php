@extends('layouts.public')

@section('title', 'Mengunduh Aplikasi SiPetani')

@section('content')
<div class="min-h-screen bg-slate-50 flex flex-col items-center justify-center p-6" style="background-image: url('/assets/images/pattern-bg.svg'); background-size: cover; background-position: center;">
    
    <div class="bg-white rounded-[32px] border border-emerald-100 shadow-[0_20px_60px_rgba(4,120,87,0.08)] p-10 max-w-lg w-full text-center relative overflow-hidden">
        
        <!-- Background decoration -->
        <div class="absolute top-0 right-0 -mt-16 -mr-16 w-64 h-64 bg-emerald-50 rounded-full blur-3xl opacity-60 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 -mb-16 -ml-16 w-64 h-64 bg-emerald-50 rounded-full blur-3xl opacity-60 pointer-events-none"></div>

        <div class="relative z-10 flex flex-col items-center">
            
            <div class="w-24 h-24 bg-emerald-100 rounded-[28px] flex items-center justify-center text-emerald-600 mb-6 shadow-sm border border-emerald-200">
                <svg class="w-12 h-12 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
            </div>

            <h1 class="text-3xl font-extrabold text-[#022c22] mb-3 tracking-tight">Login Berhasil!</h1>
            <p class="text-slate-500 mb-8 leading-relaxed">
                Proses pengunduhan aplikasi <strong>SiPetani.apk</strong> akan segera dimulai secara otomatis. Silakan tunggu beberapa saat...
            </p>

            <div class="w-full bg-slate-100 rounded-full h-2 mb-8 overflow-hidden">
                <div class="bg-emerald-500 h-2 rounded-full animate-pulse" style="width: 100%"></div>
            </div>

            <div class="space-y-4 w-full">
                <p class="text-xs text-slate-400">Jika unduhan tidak dimulai otomatis, klik tombol di bawah:</p>
                <a href="{{ route('mobile-app.file') }}" class="block w-full py-3.5 px-6 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-full transition-all shadow-[0_10px_25px_rgba(4,120,87,.2)] text-sm">
                    Mulai Unduh Manual
                </a>
                
                <a href="{{ 
                    match((int) session('role_id')) {
                        1, 5 => '/dashboard-petani',
                        2 => '/dashboard-petugas',
                        default => '/'
                    }
                }}" class="block w-full py-3.5 px-6 bg-slate-50 hover:bg-slate-100 text-slate-600 font-bold rounded-full transition-all border border-slate-200 text-sm">
                    Lanjut ke Dashboard
                </a>
            </div>

        </div>
    </div>
</div>

<script>
    // Trigger download automatically after 1.5 seconds
    setTimeout(function() {
        window.location.href = "{{ route('mobile-app.file') }}";
    }, 1500);
</script>
@endsection
