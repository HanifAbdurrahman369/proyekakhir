<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — SiPetani</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite('resources/css/app.css')

    <style>
        :root {
            --emerald-950:#022c22;
            --emerald-900:#064e3b;
            --emerald-800:#065f46;
            --emerald-700:#047857;
            --emerald-600:#059669;
            --emerald-500:#10b981;
            --emerald-100:#d1fae5;
            --emerald-50:#ecfdf5;
            --line:#e7efd8;
            --ink:#172033;
            --muted:#7d8799;
            --surface:#ffffff;
            --soft:#f8fbf4;
        }

        * { font-family: 'Poppins', sans-serif; }

        html { scroll-behavior: smooth; }

        body {
            background:
                radial-gradient(circle at top left, rgba(16,185,129,.14), transparent 32%),
                radial-gradient(circle at bottom right, rgba(4,120,87,.11), transparent 35%),
                linear-gradient(135deg, #ecfdf5 0%, #d1fae5 42%, #ffffff 100%);
            color: var(--ink);
        }

        .sipetani-shell {
            min-height: calc(100vh - 72px);
        }

        .sipetani-content {
            width: 100%;
            min-width: 0;
        }

        .sipetani-page {
            max-width: 1520px;
            margin: 0 auto;
        }

        .glass-card {
            background: rgba(255,255,255,.86);
            border: 1px solid rgba(231,239,216,.92);
            box-shadow: 0 18px 50px rgba(32,60,16,.07);
            backdrop-filter: blur(14px);
        }

        .soft-card,
        .bg-white.rounded-xl,
        .bg-white.rounded-2xl {
            background: rgba(255,255,255,.92) !important;
            border-color: rgba(231,239,216,.95) !important;
            box-shadow: 0 16px 42px rgba(32,60,16,.065) !important;
        }

        table thead,
        .bg-primary-50 {
            background: linear-gradient(135deg, #f4fbe9, #eef8dd) !important;
        }

        table th {
            color: var(--emerald-900) !important;
            font-size: .72rem !important;
            letter-spacing: .045em !important;
        }

        table td {
            font-size: .82rem !important;
        }

        input, select, textarea {
            border-radius: 14px !important;
            border-color: #dfe9d1 !important;
            font-size: .84rem !important;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--emerald-500) !important;
            box-shadow: 0 0 0 4px rgba(101,189,0,.13) !important;
            outline: none !important;
        }

        .text-primary-900 { color: var(--emerald-950) !important; }
        .text-primary-800 { color: var(--emerald-800) !important; }
        .text-primary-700 { color: var(--emerald-700) !important; }
        .text-primary-600 { color: var(--emerald-600) !important; }
        .bg-primary-600 { background: var(--emerald-700) !important; }
        .bg-primary-700 { background: var(--emerald-800) !important; }
        .bg-primary-800 { background: var(--emerald-900) !important; }
        .bg-primary-900 { background: var(--emerald-950) !important; }
        .border-primary-100 { border-color: var(--line) !important; }

        .btn-green {
            background: linear-gradient(135deg, var(--emerald-600), var(--emerald-800));
            color: white;
            box-shadow: 0 14px 34px rgba(62,125,0,.22);
        }

        .btn-green:hover {
            filter: brightness(.98);
            transform: translateY(-1px);
        }

        .mobile-backdrop {
            background: rgba(20,40,11,.44);
            backdrop-filter: blur(6px);
        }

        @media (max-width: 1023px) {
            .sipetani-shell { min-height: calc(100vh - 64px); }
            .sipetani-page { padding: 18px !important; }
            table { min-width: 760px; }
        }

        @media print {
            #sidebarBackdrop,
            .sipetani-sidebar,
            nav,
            button,
            a[href="/logout"] { display:none !important; }

            .sipetani-content,
            .sipetani-page { padding: 0 !important; margin: 0 !important; max-width: none !important; }
            body { background: white !important; }
        }
    </style>

    @stack('styles')
</head>

<body class="min-h-screen antialiased">
    @include('partials.navbar')

    <div id="sidebarBackdrop" class="mobile-backdrop fixed inset-0 z-40 hidden lg:hidden" onclick="closeSidebar()"></div>

    <div class="sipetani-shell flex">
        @include('partials.sidebar.index')

        <main class="sipetani-content flex-1 min-w-0">
            <div class="sipetani-page px-5 sm:px-6 lg:px-8 py-6 lg:py-8">
                @yield('content')
            </div>
        </main>
    </div>

    <script>
        function openSidebar() {
            const sidebar = document.getElementById('appSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (sidebar) sidebar.classList.remove('-translate-x-full');
            if (backdrop) backdrop.classList.remove('hidden');
        }

        function closeSidebar() {
            const sidebar = document.getElementById('appSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (sidebar) sidebar.classList.add('-translate-x-full');
            if (backdrop) backdrop.classList.add('hidden');
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') closeSidebar();
        });

        // Auto-wrap tables to prevent horizontal overflow on mobile
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll("table").forEach(function(table) {
                const parent = table.parentElement;
                if (parent && !parent.classList.contains("overflow-x-auto")) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'overflow-x-auto w-full custom-scrollbar';
                    parent.insertBefore(wrapper, table);
                    wrapper.appendChild(table);
                }
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
