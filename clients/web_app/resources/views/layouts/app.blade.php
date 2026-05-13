<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') — SiTani</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite('resources/css/app.css')
</head>

<body class="font-sans bg-primary-50/40 min-h-screen">

    {{-- 1. Panggil Navbar Global --}}
    @include('partials.navbar')

    {{-- Layout: Sidebar + Main --}}
    <div class="flex min-h-[calc(100vh-56px)]">

        {{-- 2. Panggil Pengatur Sidebar --}}
        @include('partials.sidebar.index')

        {{-- 3. Main Content --}}
        <main class="flex-1 p-6 overflow-y-auto">
            @yield('content')
        </main>

    </div>

</body>
</html>