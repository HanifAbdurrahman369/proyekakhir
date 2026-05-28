@extends('layouts.app')

@section('title', 'Peta Posisiku')

@section('content')

<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>

<div class="max-w-7xl mx-auto bg-white p-6 rounded-xl shadow">

    {{-- HEADER --}}
    <div class="mb-6">

        <h1 class="text-2xl font-bold text-primary-900">
            Peta Posisiku
        </h1>

        <p class="text-sm text-gray-500 mt-1">
            Menampilkan posisi lokasi Anda secara realtime berdasarkan koordinat GPS perangkat.
        </p>

    </div>

    {{-- ALERT --}}
    <div id="location-alert"
         class="hidden mb-4 p-4 rounded-lg border text-sm">
    </div>

    {{-- INFO CARD --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

        <div class="bg-green-50 border border-green-100 rounded-xl p-4">

            <p class="text-xs uppercase tracking-wider text-green-700 font-semibold">
                Latitude
            </p>

            <p id="latitude"
               class="text-lg font-bold text-green-900 mt-1">
                -
            </p>

        </div>

        <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">

            <p class="text-xs uppercase tracking-wider text-blue-700 font-semibold">
                Longitude
            </p>

            <p id="longitude"
               class="text-lg font-bold text-blue-900 mt-1">
                -
            </p>

        </div>

        <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-4">

            <p class="text-xs uppercase tracking-wider text-yellow-700 font-semibold">
                Akurasi GPS
            </p>

            <p id="accuracy"
               class="text-lg font-bold text-yellow-900 mt-1">
                -
            </p>

        </div>

    </div>

    {{-- MAP --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200">

        <div id="map"
             class="w-full h-[600px]">
        </div>

    </div>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>

    let map;
    let marker;
    let circle;

    /*
    |--------------------------------------------------------------------------
    | INIT MAP
    |--------------------------------------------------------------------------
    */

    map = L.map('map').setView([-2.5489, 118.0149], 5);

    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            attribution: '&copy; OpenStreetMap'
        }
    ).addTo(map);

    /*
    |--------------------------------------------------------------------------
    | ALERT HELPER
    |--------------------------------------------------------------------------
    */

    function showAlert(message, type = 'success')
    {
        const alertBox = document.getElementById('location-alert');

        alertBox.classList.remove('hidden');

        if(type === 'success') {

            alertBox.className =
                'mb-4 p-4 rounded-lg border text-sm bg-green-100 text-green-700 border-green-200';

        } else {

            alertBox.className =
                'mb-4 p-4 rounded-lg border text-sm bg-red-100 text-red-700 border-red-200';
        }

        alertBox.innerHTML = message;
    }

    /*
    |--------------------------------------------------------------------------
    | GET USER LOCATION
    |--------------------------------------------------------------------------
    */

    if (navigator.geolocation) {

        navigator.geolocation.getCurrentPosition(

            function(position) {

                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const accuracy = position.coords.accuracy;

                /*
                |--------------------------------------------------------------------------
                | UPDATE CARD
                |--------------------------------------------------------------------------
                */

                document.getElementById('latitude').innerText =
                    lat.toFixed(6);

                document.getElementById('longitude').innerText =
                    lng.toFixed(6);

                document.getElementById('accuracy').innerText =
                    accuracy.toFixed(2) + ' meter';

                /*
                |--------------------------------------------------------------------------
                | SET MAP VIEW
                |--------------------------------------------------------------------------
                */

                map.setView([lat, lng], 17);

                /*
                |--------------------------------------------------------------------------
                | MARKER
                |--------------------------------------------------------------------------
                */

                marker = L.marker([lat, lng]).addTo(map);

                marker.bindPopup(`
                    <div class="text-sm">
                        <strong>Posisi Anda</strong>
                        <br>
                        Latitude: ${lat.toFixed(6)}
                        <br>
                        Longitude: ${lng.toFixed(6)}
                    </div>
                `).openPopup();

                /*
                |--------------------------------------------------------------------------
                | ACCURACY CIRCLE
                |--------------------------------------------------------------------------
                */

                circle = L.circle([lat, lng], {

                    radius: accuracy,

                    color: '#16a34a',

                    fillColor: '#22c55e',

                    fillOpacity: 0.2

                }).addTo(map);

                /*
                |--------------------------------------------------------------------------
                | SUCCESS ALERT
                |--------------------------------------------------------------------------
                */

                showAlert(
                    'Lokasi berhasil ditemukan dan ditampilkan pada peta.'
                );

            },

            function(error) {

                let message = 'Gagal mendapatkan lokasi pengguna.';

                switch(error.code) {

                    case error.PERMISSION_DENIED:
                        message = 'Akses lokasi ditolak oleh pengguna.';
                        break;

                    case error.POSITION_UNAVAILABLE:
                        message = 'Informasi lokasi tidak tersedia.';
                        break;

                    case error.TIMEOUT:
                        message = 'Permintaan lokasi melebihi batas waktu.';
                        break;
                }

                showAlert(message, 'error');

            },

            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );

    } else {

        showAlert(
            'Browser tidak mendukung geolocation.',
            'error'
        );
    }

</script>

@endsection
