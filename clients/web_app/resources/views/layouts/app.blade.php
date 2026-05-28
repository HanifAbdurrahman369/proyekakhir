<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — SiTani</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
          rel="stylesheet">

    @vite('resources/css/app.css')
</head>

<body class="font-sans bg-primary-50/40">

    {{-- NAVBAR --}}
    @include('partials.navbar')

    {{-- WRAPPER --}}
    <div class="flex">

        {{-- SIDEBAR --}}
        @include('partials.sidebar.index')

        {{-- MAIN CONTENT --}}
        <main class="flex-1 p-6 min-h-screen">

            @yield('content')

        </main>

    </div>

</body>
</html>