@extends('layouts.public')

@section('content')
<div class="relative w-full h-screen">

    <img src="{{ asset('storage/bg.png') }}"
         class="absolute inset-0 w-full h-full object-cover"
         alt="bg">

    <div class="absolute inset-0 bg-black/50"></div>

    <div class="relative z-10 flex h-full flex-col items-center justify-center text-center px-6">
        <p class="text-white font-regular text-2xl">SELAMAT DATANG DI</p>
        <p class="text-white font-regular text-4xl font-bold">SIG-PALA</p>
        <p class="text-white font-regular text-lg max-w-xl">
            SISTEM INFORMASI GEOGRAFIS PRODUKTIVITAS PADA LAHAN RAWA BATOLA
        </p>
    </div>

</div>

<div class="flex w-full py-8 px-2.5 flex-col items-center gap-10">

    <p class="text-slate-700 text-3xl text-center">MAP</p>

    <div id="map" class="w-full max-w-4xl h-[500px] rounded-xl shadow"></div>

</div>

<div class="flex w-full py-8 px-4 flex-col items-center gap-10">

    <p class="text-slate-700 text-3xl text-center">DATA STATISTIK</p>

    <!-- GRID CARD -->
    <div class="grid md:grid-cols-3 gap-6 w-full max-w-6xl">

        <!-- CARD 1 -->
        <div class="bg-primary-100 p-6 rounded-xl shadow">
            <p class="text-gray-600 text-sm">Total Kecamatan</p>
            <p class="text-2xl font-bold text-primary-700">17</p>
        </div>

        <!-- CARD 2 -->
        <div class="bg-primary-100 p-6 rounded-xl shadow">
            <p class="text-gray-600 text-sm">Total Kelurahan</p>
            <p class="text-2xl font-bold text-primary-700">201</p>
        </div>

        <!-- CARD 3 -->
        <div class="bg-primary-100 p-6 rounded-xl shadow">
            <p class="text-gray-600 text-sm">Total Hasil Panen</p>
            <p class="text-2xl font-bold text-primary-700">78.542 Ton</p>
        </div>

    </div>

    <!-- TABLE DATA -->
    <div class="w-full max-w-6xl bg-white rounded-xl shadow overflow-hidden">

        <table class="w-full text-left">

            <thead class="bg-primary-500 text-white">
                <tr>
                    <th class="p-3">Kecamatan</th>
                    <th class="p-3">Kelurahan</th>
                    <th class="p-3">Hasil Panen (Ton)</th>
                </tr>
            </thead>

            <tbody class="text-gray-700">

                <tr class="border-b">
                    <td class="p-3">Alalak</td>
                    <td class="p-3">Handil Bakti</td>
                    <td class="p-3">5.200</td>
                </tr>

                <tr class="border-b">
                    <td class="p-3">Anjir Muara</td>
                    <td class="p-3">Anjir Pasar</td>
                    <td class="p-3">6.100</td>
                </tr>

                <tr class="border-b">
                    <td class="p-3">Mandastana</td>
                    <td class="p-3">Tabing Rimbah</td>
                    <td class="p-3">4.850</td>
                </tr>

                <tr class="border-b">
                    <td class="p-3">Marabahan</td>
                    <td class="p-3">Ulu Benteng</td>
                    <td class="p-3">7.300</td>
                </tr>

                <tr class="border-b">
                    <td class="p-3">Tabukan</td>
                    <td class="p-3">Karya Maju</td>
                    <td class="p-3">3.950</td>
                </tr>

            </tbody>

        </table>

    </div>

</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {

        var map = L.map('map').setView([-3.0715, 114.6425], 10);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        L.marker([-3.0715, 114.6425])
            .addTo(map)
            .bindPopup('Kabupaten Barito Kuala')
            .openPopup();

    });
</script>

@endsection