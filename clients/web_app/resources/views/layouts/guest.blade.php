<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dinas Pertanian') — SiTani</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite('resources/css/app.css')

    <style>
        .auth-right::before {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(94,165,0,.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(94,165,0,.06) 1px, transparent 1px);
            background-size: 24px 24px;
        }
        .auth-left::before {
            content: '';
            position: absolute;
            top: -60px; left: -60px;
            width: 220px; height: 220px;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
            pointer-events: none;
        }
        .auth-left::after {
            content: '';
            position: absolute;
            bottom: -80px; right: -50px;
            width: 260px; height: 260px;
            border-radius: 50%;
            background: rgba(0,0,0,.12);
            pointer-events: none;
        }
    </style>
</head>

<body class="font-sans min-h-screen bg-gray-50">

    <div class="min-h-screen flex">

        {{-- LEFT: Branding Panel --}}
        <div class="auth-left hidden lg:flex flex-col justify-between flex-1 relative overflow-hidden px-10 py-10"
             style="background: linear-gradient(160deg, #497D00 0%, #35530E 100%);">

            <div class="relative z-10">
                {{-- Logo --}}
                <div class="flex items-center gap-3 mb-12">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:rgba(255,255,255,.18);">
                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2s4-4-3 1C7 9 7 15 7 15s0-3 3-5.31C13.77 7.73 17 8 17 8Z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium" style="color:rgba(255,255,255,.6)">Sistem Informasi</p>
                        <p class="text-sm font-bold text-white">Dinas Pertanian</p>
                    </div>
                </div>

                {{-- Per-page headline & features --}}
                @yield('left-heading')
            </div>

            <p class="relative z-10 text-xs" style="color:rgba(255,255,255,.35)">
                &copy; {{ date('Y') }} Dinas Pertanian &mdash; All rights reserved.
            </p>
        </div>

        {{-- RIGHT: Form Panel --}}
        <div class="auth-right relative w-full lg:w-auto lg:flex-none lg:min-w-[340px] xl:min-w-[380px] flex items-center justify-center px-8 py-12 bg-gray-50">
            <div class="relative z-10 w-full max-w-sm">

                {{-- Mobile logo --}}
                <div class="flex items-center gap-2.5 mb-8 lg:hidden">
                    <div class="w-9 h-9 rounded-lg bg-primary-600 flex items-center justify-center shadow">
                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2s4-4-3 1C7 9 7 15 7 15s0-3 3-5.31C13.77 7.73 17 8 17 8Z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-primary-700 font-medium leading-none">Sistem Informasi</p>
                        <p class="text-sm font-bold text-primary-900 leading-tight">Dinas Pertanian</p>
                    </div>
                </div>

                @yield('content')

                <p class="text-center text-xs text-primary-700/50 mt-8 font-medium">
                    🌾 Sistem Informasi Dinas Pertanian
                </p>
            </div>
        </div>

    </div>

</body>
</html>