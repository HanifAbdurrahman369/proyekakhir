<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Page</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite('resources/css/app.css')
</head>

<body class="bg-white">

    <!-- NAVBAR -->
    <nav class="flex items-center justify-between px-8 py-4 border-color-primary-500">
        <div class="flex items-center gap-6">
            <img src="{{ asset('storage/logo.png') }}" alt="Logo SIG-PALA" class="w-15 h-14">
            <p class="text-slate-700 font-regular text-3xl">SIG-PALA</p>
        </div>

        <div class="flex justify-end items-center gap-12">
            <a href="/" class="text-slate-700 hover:text-primary-600">DATA STATISTIK</a>
            <a href="#features" class="text-slate-700 hover:text-primary-600">MAP</a>
            <a href="/login" class="bg-primary-500 hover:bg-primary-600 text-white px-4 py-2 rounded">
                Login
            </a>
        </div>
    </nav>

    <!-- CONTENT -->
    <main>
        @yield('content')
    </main>

    <!-- FOOTER -->
    <footer class="flex p-11 flex-col gap-6 rounded-sm bg-primary-50">
        <p class="text-slate-700 font-regular text-4xl">SIG-PALA</p>
        <p class="text-slate-700 font-regular text-xl">SISTEM INFORMASI GEOGRAFIS PRODUKTIVITAS PADA LAHAN RAWA BATOLA</p>
        <p class="text-slate-700 font-regular text-lg">Jl. Jend Sudirman No.74, Ulu Benteng, Kec. Marabahan, Kabupaten Barito Kuala, Kalimantan Selatan 70513</p>
        <p class="text-slate-700 font-regular text-lg">Telpon: 0511-6701895</p>
        <p class="text-slate-700 font-regular text-lg">Email: distantph@baritokualakab.go.id</p>
        
        <div class="flex p-2.5 justify-center items-center gap-2.5">
            <p class="text-slate-700 font-regular text-xl text-center">Dinas Pertanian, Tanaman Pangan dan Hortikultura</p>
        </div>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

</body>
</html>