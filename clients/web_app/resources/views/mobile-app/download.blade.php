@extends('layouts.public')

@section('title', 'Mengunduh Aplikasi SiPetani')

@section('content')
<div class="min-h-screen bg-slate-50 flex flex-col items-center justify-center p-6">
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 max-w-md w-full text-center">
        
        <div class="mx-auto w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-600 mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-slate-800 mb-2">Mengunduh Aplikasi...</h1>
        <p class="text-slate-500 mb-6 text-sm">
            Mohon tunggu, aplikasi <strong>SiPetani.apk</strong> sedang diunduh ke perangkat Anda.
        </p>

        <!-- Hidden iframe to trigger download without changing page -->
        <iframe id="download-frame" src="about:blank" style="display:none;"></iframe>

        <div class="space-y-3 w-full">
            <button onclick="startDownload()" class="block w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg transition-colors text-sm">
                Coba Unduh Ulang
            </button>
            
            <a href="{{ 
                match((int) session('role_id')) {
                    1, 5 => '/dashboard-petani',
                    2 => '/dashboard-petugas',
                    default => '/'
                }
            }}" class="block w-full py-2.5 px-4 bg-white hover:bg-slate-50 text-slate-700 font-semibold rounded-lg border border-slate-200 transition-colors text-sm">
                Lanjut ke Dashboard
            </a>
        </div>
    </div>
</div>

<script>
    function startDownload() {
        document.getElementById('download-frame').src = "{{ route('mobile-app.file') }}";
    }

    // Trigger download automatically on load
    window.onload = function() {
        startDownload();
    };
</script>
@endsection
