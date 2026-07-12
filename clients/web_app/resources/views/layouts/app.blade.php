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
            --emerald-600:#047857;
            --emerald-500:#047857;
            --emerald-100:#d1fae5;
            --emerald-50:#ecfdf5;
            --brand-green:#047857;
            --brand-green-hover:#065f46;
            --brand-green-soft:#ecfdf5;
            --brand-green-line:#d1fae5;
            --line:#d1fae5;
            --ink:#172033;
            --muted:#7d8799;
            --surface:#ffffff;
            --soft:#f8fdf9;
            --card-radius:24px;
        }

        * { font-family: 'Poppins', sans-serif; }

        html { scroll-behavior: smooth; }

        body {
            background:
                radial-gradient(circle at top left, rgba(4,120,87,.12), transparent 32%),
                radial-gradient(circle at bottom right, rgba(4,120,87,.10), transparent 35%),
                linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 42%, #ffffff 100%);
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
            border: 1px solid rgba(209,250,229,.92);
            border-radius: var(--card-radius) !important;
            box-shadow: 0 18px 50px rgba(4,120,87,.075);
            backdrop-filter: blur(14px);
        }

        .soft-card,
        .admin-section > .bg-white,
        .admin-section .glass-card,
        .bg-white.rounded-md,
        .bg-white.rounded-lg,
        .bg-white.rounded-xl,
        .bg-white.rounded-2xl {
            background: rgba(255,255,255,.92) !important;
            border-color: rgba(209,250,229,.95) !important;
            border-radius: var(--card-radius) !important;
            box-shadow: 0 16px 42px rgba(4,120,87,.06) !important;
        }

        table thead,
        .bg-primary-50 {
            background: linear-gradient(135deg, #ecfdf5, #f0fdf4) !important;
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
            border-color: #d1fae5 !important;
            font-size: .84rem !important;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--brand-green) !important;
            box-shadow: 0 0 0 4px rgba(4,120,87,.13) !important;
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

        .text-\[\#65bd00\],
        .text-\[\#4f9a00\],
        .text-\[\#047857\],
        .text-\[\#2f5c12\],
        .text-emerald-600,
        .text-emerald-700,
        .text-emerald-800 {
            color: var(--brand-green) !important;
        }

        .text-\[\#203c10\],
        .text-\[\#14280b\] {
            color: var(--emerald-950) !important;
        }

        .bg-\[\#65bd00\],
        .bg-\[\#047857\],
        .bg-emerald-500,
        .bg-emerald-600,
        .bg-emerald-700 {
            background-color: var(--brand-green) !important;
        }

        .hover\:bg-\[\#65bd00\]:hover,
        .hover\:bg-\[\#047857\]:hover,
        .hover\:bg-\[\#2f5c12\]:hover,
        .hover\:bg-\[\#24480e\]:hover,
        .hover\:bg-\[\#1a3809\]:hover,
        .hover\:bg-emerald-600:hover,
        .hover\:bg-emerald-700:hover {
            background-color: var(--brand-green-hover) !important;
        }

        .bg-\[\#2f5c12\],
        .bg-\[\#203c10\],
        .bg-\[\#24480e\],
        .bg-\[\#1a3809\],
        .bg-\[\#14280b\],
        .bg-emerald-800 {
            background-color: var(--emerald-800) !important;
        }

        .bg-\[\#edf8dc\],
        .bg-\[\#f7fced\],
        .hover\:bg-\[\#edf8dc\]:hover,
        .hover\:bg-\[\#e2f2cc\]:hover,
        .bg-emerald-50,
        .bg-emerald-100,
        .hover\:bg-emerald-100:hover,
        .hover\:bg-emerald-200:hover,
        .bg-emerald-500\/10 {
            background-color: var(--brand-green-soft) !important;
        }

        .border-\[\#dfeccc\],
        .border-\[\#e7efd8\],
        .border-emerald-100,
        .border-emerald-200,
        .border-emerald-700 {
            border-color: var(--brand-green-line) !important;
        }

        .from-\[\#65bd00\],
        .from-\[\#047857\] {
            --tw-gradient-from: var(--brand-green) var(--tw-gradient-from-position) !important;
            --tw-gradient-to: rgba(4,120,87,0) var(--tw-gradient-to-position) !important;
            --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to) !important;
        }

        .to-\[\#65bd00\],
        .to-\[\#047857\] {
            --tw-gradient-to: var(--brand-green-hover) var(--tw-gradient-to-position) !important;
        }

        .rounded-md,
        .rounded-lg,
        .rounded-xl,
        .rounded-2xl {
            border-radius: max(var(--tw-radius, 0px), 14px) !important;
        }

        .rounded-\[22px\],
        .rounded-\[24px\],
        .rounded-\[26px\],
        .rounded-\[28px\] {
            border-radius: var(--card-radius) !important;
        }

        .btn-green {
            background: linear-gradient(135deg, var(--emerald-600), var(--emerald-800));
            color: white;
            box-shadow: 0 14px 34px rgba(4,120,87,.22);
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
