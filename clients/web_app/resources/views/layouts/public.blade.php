<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Page - SIG-PALA</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- LEAFLET CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite('resources/css/app.css')
</head>

<body class="bg-white font-['Poppins']">

    <!-- NAVBAR -->
    <nav class="flex items-center justify-between px-8 py-4 border-b border-gray-100 shadow-sm">
        <div class="flex items-center gap-6">
            <img src="{{ asset('storage/logo.png') }}" alt="Logo SIG-PALA" class="w-15 h-14">
            <p class="text-slate-800 font-bold text-3xl tracking-wide">SIG-PALA</p>
        </div>

        <div class="flex justify-end items-center gap-12 font-medium">
            <a href="{{ url('/') }}" class="text-slate-600 hover:text-primary-600 transition-colors">DATA STATISTIK</a>
            
            <!-- PERUBAHAN: Tautan MAP mengarah ke halaman Full Map -->
            <a href="{{ route('map.full') }}" class="text-slate-600 hover:text-primary-600 transition-colors">MAP</a>
            
            <a href="/login" class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2.5 rounded-lg shadow-sm transition-all hover:shadow">
                Login
            </a>
        </div>
    </nav>

    <!-- CONTENT -->
    <main>
        @yield('content')
    </main>

    <!-- FOOTER -->
    <footer class="flex p-12 flex-col gap-6 bg-slate-50 border-t border-gray-100 mt-10">
        <div>
            <p class="text-slate-800 font-bold text-4xl mb-2">SIG-PALA</p>
            <p class="text-slate-600 font-medium text-xl">SISTEM INFORMASI GEOGRAFIS PRODUKTIVITAS PADA LAHAN RAWA BATOLA</p>
        </div>
        
        <div class="text-slate-500 font-regular text-base space-y-1 mt-2">
            <p>📍 Jl. Jend Sudirman No.74, Ulu Benteng, Kec. Marabahan, Kabupaten Barito Kuala, Kalimantan Selatan 70513</p>
            <p>📞 Telpon: 0511-6701895</p>
            <p>✉️ Email: distantph@baritokualakab.go.id</p>
        </div>
        
        <div class="flex pt-6 mt-4 border-t border-gray-200 justify-center items-center">
            <p class="text-slate-500 font-medium text-lg text-center">
                &copy; {{ date('Y') }} Dinas Pertanian, Tanaman Pangan dan Hortikultura Kabupaten Barito Kuala
            </p>
        </div>
    </footer>

    <!-- LEAFLET JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

</body>
</html>