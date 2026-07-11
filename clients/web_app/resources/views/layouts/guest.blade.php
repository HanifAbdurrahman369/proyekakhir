<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dinas Pertanian, Tanaman Pangan dan Holtikultura') — SiPetani</title>

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
                <div class="mx-auto w-16 h-16 flex items-center justify-center mb-4">
                    <img src="{{ asset('storage/logo.png') }}" class="w-full h-full object-contain" alt="Logo">
                </div>

                <p class="text-sm font-semibold tracking-wide" style="color:#497D00;">
                    Sistem Informasi Pemetaan Padi Dinas Pertanian Batola
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
                {{ date('Y') }} - SiPetani
            </p>
        </section>
    </main>

    @stack('scripts')
</body>
</html>
