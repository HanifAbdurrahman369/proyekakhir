<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dinas Pertanian') — SiTani</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite('resources/css/app.css')

    <style>
        * { font-family: 'Poppins', sans-serif; }

        body {
            background:
                radial-gradient(circle at top left, rgba(187, 244, 81, .22), transparent 34%),
                radial-gradient(circle at bottom right, rgba(62, 125, 0, .18), transparent 36%),
                linear-gradient(135deg, #f7fbf2 0%, #edf7e6 48%, #f9fcf7 100%);
        }

        .auth-grid {
            background-image:
                linear-gradient(rgba(72, 125, 0, .055) 1px, transparent 1px),
                linear-gradient(90deg, rgba(72, 125, 0, .055) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        .auth-card {
            box-shadow: 0 24px 70px rgba(53, 83, 14, .15);
        }

        .auth-input:focus {
            border-color: #66A80F;
            box-shadow: 0 0 0 4px rgba(102, 168, 15, .13);
        }
    </style>
</head>

<body class="min-h-screen text-slate-800">
    <main class="auth-grid min-h-screen flex items-center justify-center px-5 py-10">
        <section class="w-full max-w-[460px]">
            <div class="text-center mb-7">
                <div class="mx-auto w-14 h-14 rounded-2xl flex items-center justify-center mb-4"
                     style="background: linear-gradient(135deg, #5EA500, #35530E); box-shadow: 0 14px 34px rgba(94,165,0,.28);">
                    <svg class="w-7 h-7 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17 8C8 10 5.9 16.17 3.82 21.34L5.71 22l1-2.3A4.49 4.49 0 0 0 8 20C19 20 22 3 22 3c-1 2-8 2-8 2s4-4-3 1C7 9 7 15 7 15s0-3 3-5.31C13.77 7.73 17 8 17 8Z"/>
                    </svg>
                </div>

                <p class="text-sm font-semibold tracking-wide" style="color:#497D00;">
                    Sistem Informasi Dinas Pertanian
                </p>
                <h1 class="text-2xl font-bold text-slate-900 mt-1">
                    @yield('page-heading', 'Selamat Datang')
                </h1>
                <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                    @yield('page-subheading', 'Kelola data pertanian secara lebih aman, rapi, dan terintegrasi.')
                </p>
            </div>

            <div class="auth-card bg-white/90 backdrop-blur-xl border border-white rounded-[28px] px-6 sm:px-8 py-7 sm:py-8">
                @yield('content')
            </div>

            <p class="text-center text-xs mt-6 font-medium" style="color:#497D00;">
                🌾 {{ date('Y') }} — SIG-PALA
            </p>
        </section>
    </main>

    @stack('scripts')
</body>
</html>